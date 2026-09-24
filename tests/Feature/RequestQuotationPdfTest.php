<?php

namespace Tests\Feature;

use App\Models\RequestQuotation;
use App\Models\User;
use App\Services\RequestQuotationPdf;
use App\Support\QuotationDocument;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\RequestQuotationData;
use Tests\TestCase;

class RequestQuotationPdfTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = RequestQuotationData::seed();
        $this->actingAs($this->user);
    }

    public function test_pdf_download_uses_the_folio_and_does_not_change_the_quotation(): void
    {
        $before = RequestQuotation::findOrFail(1)->getAttributes();
        $response = $this->get(route('admin.solicitudes.cotizacion.pdf', 1))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="COT-000001.pdf"')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertSame($before, RequestQuotation::findOrFail(1)->getAttributes());
    }

    public function test_pdf_contains_only_the_saved_commercial_snapshot_and_escapes_content(): void
    {
        $quote = RequestQuotation::findOrFail(1);
        $quote->forceFill([
            'total' => 181.50,
            'clinical_data' => ['diagnosis' => 'Private diagnosis', 'observations' => 'Private observations'],
            'attachment_path' => 'private/signature.pdf',
            'pricing_snapshot' => ['lines' => [
                ['description' => 'Medicamento <script>example</script>', 'presentation' => 'Frasco 100 mg',
                    'quantity' => 2, 'mixtures' => 2, 'unit' => 'mg', 'unit_price' => 25.125, 'vat' => 0, 'total' => 100.50],
                ['description' => 'Servicio de preparacion', 'unit' => 'servicio', 'quantity' => 1,
                    'unit_price' => 50, 'vat' => 8, 'total' => 58],
                ['description' => 'Producto por frasco', 'unit' => 'frasco', 'quantity' => 2,
                    'unit_price' => 10, 'vat' => 3, 'total' => 23],
            ]],
        ]);
        $data = app(RequestQuotationPdf::class)->data($quote);
        $this->assertSame(4.0, $data['lines'][0]['quantity'], 'Quantity must account for all mixtures');
        $this->assertEquals(25.125, $data['lines'][0]['unit_price']);
        $this->assertEquals(100.50, $data['lines'][0]['total']);
        $this->assertSame('Frasco', $data['lines'][2]['unit']);
        $this->assertEquals(11, $data['vat']);
        $this->assertSame('181.50', $data['total']);
        $html = view('admin.solicitudes.quotations.pdf', $data)->render();
        $this->assertStringNotContainsString('<h1>', $html);
        $this->assertStringNotContainsString('Medicamento y servicio de preparaci', $html);
        foreach (['PROMESA', 'COT-000001', 'DIRIGIDA A', '20 de septiembre de 2026', '$25.1250', '$181.50', '$170.50', '$11.00', 'CONSIDERACIONES', '&lt;script&gt;'] as $value) {
            $this->assertStringContainsString($value, $html);
        }
        foreach (['Private diagnosis', 'Private observations', 'Paciente de prueba', 'signature.pdf', '<script>'] as $private) {
            $this->assertStringNotContainsString($private, $html);
        }
        $quote->total = null;
        $html = view('admin.solicitudes.quotations.pdf', app(RequestQuotationPdf::class)->data($quote))->render();
        $this->assertStringContainsString('Sin registrar', $html);
        $this->assertStringNotContainsString('Cero pesos', $html);
    }

    public function test_preview_and_pdf_use_promesa_logo_and_configured_issuer_details(): void
    {
        config(['prodifem.legal_name' => 'Empresa <prueba>', 'prodifem.rfc' => 'RFC-DE-PRUEBA',
            'prodifem.fiscal_address' => 'Domicilio & prueba', 'prodifem.phone' => '5551234567',
            'prodifem.email' => 'prueba@example.test']);
        $previewIssuer = QuotationDocument::issuer();
        $data = app(RequestQuotationPdf::class)->data(RequestQuotation::findOrFail(1));
        $pdfIssuer = $data['issuer'];
        $this->assertSame(asset('img/promesa-logo.png'), $previewIssuer['logo']);
        $this->assertSame('data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/promesa-logo.png'))), $pdfIssuer['logo']);
        unset($previewIssuer['logo'], $pdfIssuer['logo']);
        $this->assertSame($previewIssuer, $pdfIssuer);

        $preview = view('admin.solicitudes.quotations._brand', ['issuer' => QuotationDocument::issuer()])->render();
        $pdf = view('admin.solicitudes.quotations.pdf', $data)->render();
        foreach ([$preview, $pdf] as $html) {
            foreach (['Logotipo de PROMESA', 'Central de Mezclas', 'Empresa &lt;prueba&gt;',
                'RFC-DE-PRUEBA', 'Domicilio &amp; prueba', '5551234567', 'prueba@example.test'] as $value) {
                $this->assertStringContainsString($value, $html);
            }
            $this->assertStringNotContainsString('<prueba>', $html);
        }
        $this->assertStringContainsString('<strong>PROMESA</strong>', $pdf);
        $this->assertStringContainsString('<footer>PROMESA | COT-000001', $pdf);
    }

    public function test_hospital_category_and_seller_access_are_enforced(): void
    {
        $this->user->syncRoles(Role::findOrCreate('Cliente', 'web'));
        $this->get(route('admin.solicitudes.cotizacion.pdf', 5))->assertForbidden();
        $this->user->syncPermissions(['nutricionales_solicitudes_index']);
        $this->get(route('admin.solicitudes.cotizacion.pdf', 1))->assertForbidden();
        $this->get(route('admin.solicitudes.cotizacion.pdf', 3))->assertOk();
        $this->get(route('admin.solicitudes.cotizacion.pdf', 9999))->assertNotFound();

        $seller = User::create(['name' => 'Seller', 'username' => 'pdf.seller', 'password' => bcrypt('test-password'), 'is_active' => true]);
        $seller->assignRole(Role::findOrCreate('Vendedor', 'web'));
        RequestQuotation::whereIn('id', [1, 2])->update(['seller_id' => $seller->id]);
        $this->actingAs($seller);
        $this->get(route('admin.solicitudes.cotizacion.pdf', 1))->assertForbidden();
        $this->get(route('admin.solicitudes.cotizacion.pdf', 5))->assertForbidden();
        $this->get(route('admin.solicitudes.cotizacion.pdf', 2))->assertOk();
    }

    public function test_pdf_is_not_available_without_a_session(): void
    {
        auth()->logout();
        $this->getJson(route('admin.solicitudes.cotizacion.pdf', 1))->assertUnauthorized();
    }
}
