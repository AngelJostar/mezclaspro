<?php

namespace Tests\Feature;

use App\Mail\RequestQuotationMail;
use App\Models\RequestQuotation;
use App\Models\User;
use App\Support\QuotationMessage;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\RequestQuotationData;
use Tests\TestCase;

class RequestQuotationEmailTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = RequestQuotationData::seed();
        $this->actingAs($this->user);
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.example.test', 'mail.mailers.smtp.url' => null]);
        Mail::fake();
    }

    private function sendQuote(int $id = 1, array $data = [])
    {
        return $this->postJson(route('admin.solicitudes.cotizacion.email', $id), $data + ['email' => 'destino@example.test', 'note' => 'Buenos dias.']);
    }

    public function test_email_is_sent_directly_with_saved_amounts_and_without_changing_the_workflow(): void
    {
        $quote = RequestQuotation::findOrFail(1);
        $quote->forceFill([
            'clinical_data' => ['diagnosis' => 'Dato clinico privado', 'doctor_license' => 'CEDULA-PRIVADA'],
            'attachment_path' => 'private/signature.pdf',
            'pricing_snapshot' => ['lines' => [[
                'description' => 'Producto de prueba', 'presentation' => 'Frasco', 'quantity' => 2,
                'unit' => 'mg', 'unit_price' => 25.125, 'mixtures' => 2, 'vat' => 0, 'total' => 100.50,
            ]]],
        ])->save();
        $before = $quote->getAttributes();
        $this->sendQuote(1, ['total' => 1, 'summary' => 'Importe falsificado'])->assertOk();
        Mail::assertSent(RequestQuotationMail::class, function ($mail) {
            $this->assertTrue($mail->hasTo('destino@example.test'));
            $this->assertStringContainsString('Buenos dias.', $mail->contentText);
            $this->assertStringContainsString('COT-000001', $mail->contentText);
            $this->assertStringContainsString('en formato PDF', $mail->contentText);
            foreach (['Paciente de prueba', 'Dato clinico privado', 'CEDULA-PRIVADA', 'signature.pdf', 'Importe falsificado'] as $private) {
                $this->assertStringNotContainsString($private, $mail->contentText);
            }
            $this->assertSame([], $mail->attachments, 'No private signatures or filesystem attachments');
            $this->assertCount(1, $mail->rawAttachments);
            $attachment = $mail->rawAttachments[0];
            $this->assertSame('COT-000001.pdf', $attachment['name']);
            $this->assertSame('application/pdf', $attachment['options']['mime']);
            $this->assertStringStartsWith('%PDF-', $attachment['data']);
            return true;
        });
        Mail::assertSentCount(1);
        $this->assertSame($before, $quote->fresh()->getAttributes());
    }

    public function test_recipient_and_note_are_validated_and_html_is_escaped(): void
    {
        foreach (['invalid', 'one@example.test,two@example.test', "one@example.test\r\nBcc: other@example.test", ''] as $email) {
            $this->sendQuote(1, ['email' => $email])->assertUnprocessable()->assertJsonValidationErrors('email');
        }
        $this->sendQuote(1, ['note' => str_repeat('x', 2001)])->assertUnprocessable()->assertJsonValidationErrors('note');
        Mail::assertNothingSent();
        $quote = RequestQuotation::findOrFail(1);
        $quote->price_list_name = '<img src=x onerror=alert(1)>';
        $mail = new RequestQuotationMail($quote, '<script>alert(1)</script>');
        $html = $mail->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('Cotizacion COT-000001 | PROMESA', $mail->subject);
    }

    public function test_hospital_and_category_scopes_are_checked_before_email(): void
    {
        $this->user->syncRoles(Role::findOrCreate('Cliente', 'web'));
        $this->sendQuote(5)->assertForbidden();
        $this->user->syncPermissions(['nutricionales_solicitudes_index']);
        $this->sendQuote(1)->assertForbidden();
        $this->sendQuote(3)->assertOk();
        Mail::assertSentCount(1);
        $this->sendQuote(9999)->assertNotFound();
    }

    public function test_sellers_cannot_send_foreign_quotes_or_assigned_drafts(): void
    {
        $seller = User::create(['name' => 'Vendedor prueba', 'username' => 'mail.seller', 'password' => bcrypt('test-password'), 'is_active' => true]);
        $seller->assignRole(Role::findOrCreate('Vendedor', 'web'));
        RequestQuotation::whereIn('id', [1, 2])->update(['seller_id' => $seller->id]);
        $this->actingAs($seller);
        $this->sendQuote(1)->assertForbidden();
        $this->sendQuote(5)->assertForbidden();
        $this->sendQuote(2)->assertOk();
        Mail::assertSentCount(1);
    }

    public function test_test_transports_and_sandbox_do_not_report_real_delivery(): void
    {
        foreach (['log', 'array', 'failover'] as $mailer) {
            config(['mail.default' => $mailer]);
            $this->sendQuote()->assertStatus(503)->assertJsonFragment(['message' => 'El correo no esta configurado para entregas reales. Solicita configurar el SMTP de produccion; no se envio ningun correo.']);
        }
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'sandbox.smtp.mailtrap.io']);
        $this->sendQuote()->assertStatus(503);
        config(['mail.mailers.smtp.host' => 'smtp.example.test', 'mail.mailers.smtp.url' => 'smtp://sandbox.smtp.mailtrap.io:587']);
        $this->sendQuote()->assertStatus(503);
        Mail::assertNothingSent();
    }

    public function test_transport_failure_is_reported_without_leaking_credentials_or_marking_sent(): void
    {
        $before = RequestQuotation::findOrFail(1)->getAttributes();
        Mail::shouldReceive('to')->once()->with('destino@example.test')->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('smtp-secret-password'));
        $this->sendQuote()->assertStatus(502)->assertDontSee('smtp-secret-password');
        $this->assertSame($before, RequestQuotation::findOrFail(1)->getAttributes());
    }

    public function test_send_dialog_and_pdf_exist_even_without_capture_permission(): void
    {
        $response = $this->get(route('admin.solicitudes.cotizacion.index'))->assertOk()
            ->assertSee('data-quotation-send-dialog', false)->assertDontSee('data-quotation-dialog', false);
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//dialog[@data-quotation-send-dialog]//input[@name="_token"]'));
        foreach ($xpath->query('//button[@data-quotation-send]') as $button) {
            $data = json_decode($button->getAttribute('data-quotation-send'), true, 512, JSON_THROW_ON_ERROR);
            $this->assertArrayNotHasKey('summary', $data);
            $this->assertSame($data['folio'].'.pdf', $data['filename']);
            $this->assertStringEndsWith('/pdf', $data['pdf_url']);
            $this->assertStringEndsWith('/correo', $data['url']);
        }
        $quote = RequestQuotation::findOrFail(1);
        $quote->total = null;
        $this->assertStringContainsString('Total: Sin registrar', QuotationMessage::summary($quote));
    }

    public function test_mail_endpoint_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) $this->sendQuote()->assertOk();
        $this->sendQuote()->assertStatus(429);
        Mail::assertSentCount(10);
    }

    public function test_pdf_failure_prevents_email_delivery(): void
    {
        $this->mock(\App\Services\RequestQuotationPdf::class)
            ->shouldReceive('render')->once()->andThrow(new \RuntimeException('private-render-details'));
        $this->sendQuote()->assertStatus(502)->assertDontSee('private-render-details');
        Mail::assertNothingSent();
    }
}
