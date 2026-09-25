<?php

namespace Tests\Feature;

use App\Models\RequestQuotation;
use App\Models\RequestQuotationDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\RequestQuotationData;
use Tests\TestCase;

class RequestQuotationDocumentTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = RequestQuotationData::seed();
        foreach (['oncologicos', 'nutricionales'] as $type) {
            $this->user->givePermissionTo(Permission::findOrCreate($type.'_solicitudes_create', 'web'));
        }
        $this->actingAs($this->user);
        Storage::fake('local');
    }

    private function upload(int $quotation = 1, ?UploadedFile $file = null, ?string $key = null)
    {
        return $this->postJson(route('admin.solicitudes.cotizacion.documents.store', $quotation), [
            'file' => $file ?? UploadedFile::fake()->image('solicitud.jpg'), 'upload_key' => $key ?? (string) Str::uuid(),
        ]);
    }

    public function test_documents_are_private_append_only_and_do_not_change_quote_or_existing_signature(): void
    {
        foreach ([1, 2, 3, 4] as $id) {
            $quote = RequestQuotation::findOrFail($id);
            $quote->forceFill(['attachment_path' => 'signature.pdf'])->save();
            Storage::disk('local')->put('signature.pdf', 'original signature');
            $before = $quote->getAttributes();
            $result = $this->upload($id)->assertOk()->assertJsonPath('count', 1);
            $document = RequestQuotationDocument::findOrFail($result->json('document.id'));
            $this->assertSame($quote->id, $document->request_quotation_id);
            $this->assertSame($this->user->id, $document->uploaded_by);
            $this->assertStringStartsWith('request-quotations/'.$id.'/documents/', $document->path);
            Storage::disk('local')->assertExists($document->path);
            $this->upload($id)->assertOk()->assertJsonPath('count', 2);
            $this->assertSame($before, $quote->fresh()->getAttributes());
            $this->assertSame('original signature', Storage::disk('local')->get('signature.pdf'));
            $this->getJson(route('admin.solicitudes.cotizacion.documents.index', $quote))->assertOk()
                ->assertJsonPath('can_upload', true)->assertJsonCount(2, 'documents')->assertDontSee($document->path);
            $this->get($result->json('document.url'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertDownload('Solicitud-'.$quote->folio.'-'.$document->id.'.jpg');
        }
    }

    public function test_retries_keep_one_document_and_keys_cannot_be_reused_for_another_quote(): void
    {
        $key = (string) Str::uuid();
        $first = $this->upload(1, key: $key)->assertOk()->json('document.id');
        $this->upload(1, key: $key)->assertOk()->assertJsonPath('document.id', $first)->assertJsonPath('count', 1);
        $this->upload(2, key: $key)->assertForbidden();
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $this->assertSame(1, RequestQuotationDocument::count());
    }

    public function test_preparation_availability_requires_authorization_documents_and_existing_permissions(): void
    {
        $this->user->givePermissionTo(Permission::findOrCreate('oncologicos_solicitudes_store', 'web'));
        foreach (['Admin', 'Cliente', 'Institucion'] as $role) {
            $this->user->syncRoles(Role::findOrCreate($role, 'web'));
            $quote = RequestQuotation::findOrFail(2);
            $quote->forceFill(['status' => 'enviada', 'authorized_at' => null])->save();
            $this->upload(2)->assertOk()->assertJsonPath('preparation_url', null);
            $quote->forceFill(['status' => 'autorizada', 'authorized_at' => now()])->save();
            $url = route('admin.solicitudes.cotizacion.preparation', $quote);
            $this->getJson(route('admin.solicitudes.cotizacion.documents.index', $quote))->assertOk()
                ->assertJsonPath('preparation_url', $url);
            $this->upload(2)->assertOk()->assertJsonPath('preparation_url', $url);
            $this->getJson(route('admin.solicitudes.cotizacion.documents.index', 3))->assertOk()
                ->assertJsonPath('preparation_url', null);
            $this->user->revokePermissionTo('oncologicos_solicitudes_store');
            $this->getJson(route('admin.solicitudes.cotizacion.documents.index', $quote))->assertOk()
                ->assertJsonPath('preparation_url', null);
            $this->user->givePermissionTo('oncologicos_solicitudes_store');
            $quote->forceFill(['status' => 'preparacion', 'request_id' => 2])->save();
            $this->getJson(route('admin.solicitudes.cotizacion.documents.index', $quote))->assertOk()
                ->assertJsonPath('preparation_url', null);
            $quote->forceFill(['request_id' => null])->save();
        }
    }

    public function test_mime_size_and_required_file_are_validated(): void
    {
        $this->postJson(route('admin.solicitudes.cotizacion.documents.store', 1), [])->assertUnprocessable()->assertJsonValidationErrors(['file', 'upload_key']);
        $disguised = UploadedFile::fake()->createWithContent('disguised.pdf', '<script>alert(1)</script>');
        foreach ([UploadedFile::fake()->image('large.png')->size(5121),
            new UploadedFile($disguised->getPathname(), 'disguised.pdf', 'application/pdf', null, true),
            UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>')] as $file) {
            $this->upload(1, $file)->assertUnprocessable()->assertJsonValidationErrors('file');
        }
        $this->assertSame(0, RequestQuotationDocument::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->upload(1, UploadedFile::fake()->createWithContent('solicitud.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"))
            ->assertOk()->assertJsonPath('document.mime_type', 'application/pdf');
        $this->upload(1, UploadedFile::fake()->image('solicitud.png'))->assertOk()->assertJsonPath('document.mime_type', 'image/png');
    }

    public function test_read_only_users_cannot_upload_but_can_download_authorized_documents(): void
    {
        $url = $this->upload()->assertOk()->json('document.url');
        $this->user->syncPermissions(['oncologicos_solicitudes_index']);
        $this->getJson(route('admin.solicitudes.cotizacion.documents.index', 1))->assertOk()->assertJsonPath('can_upload', false);
        $this->upload()->assertForbidden();
        $this->get($url)->assertOk();
        $this->getJson(route('admin.solicitudes.cotizacion.documents.index', 3))->assertForbidden();
    }

    public function test_documents_enforce_hospital_seller_and_parent_quote_scope(): void
    {
        $result = $this->upload(5)->assertOk();
        $document = RequestQuotationDocument::findOrFail($result->json('document.id'));
        $this->get(route('admin.solicitudes.cotizacion.documents.download', [1, $document]))->assertNotFound();
        $this->user->syncRoles(Role::findOrCreate('Cliente', 'web'));
        $this->getJson(route('admin.solicitudes.cotizacion.documents.index', 5))->assertForbidden();
        $this->get($result->json('document.url'))->assertForbidden();
        $this->upload(5)->assertForbidden();
        $seller = User::create(['name' => 'Vendedor', 'username' => 'document.seller', 'password' => bcrypt('test'), 'is_active' => true]);
        $seller->assignRole(Role::findOrCreate('Vendedor', 'web'));
        RequestQuotation::whereIn('id', [1, 2])->update(['seller_id' => $seller->id]);
        $this->actingAs($seller);
        $this->upload(1)->assertForbidden();
        $this->upload(5)->assertForbidden();
        $this->upload(2)->assertOk();
        $this->getJson(route('admin.solicitudes.cotizacion.documents.index', 9999))->assertNotFound();
    }

    public function test_document_button_is_yellow_without_attachments_and_green_with_saved_documents(): void
    {
        $buttons = function () {
            $response = $this->get(route('admin.solicitudes.cotizacion.index'))->assertOk();
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            return (new \DOMXPath($dom))->query('//button[@data-quotation-documents]');
        };
        foreach ($buttons() as $button) {
            $this->assertStringContainsString('quotation-row-pill--pending', $button->getAttribute('class'));
            $this->assertStringNotContainsString('quotation-row-pill--success', $button->getAttribute('class'));
        }
        $this->upload(1)->assertOk();
        foreach (['Admin', 'Cliente', 'Institucion'] as $role) {
            $this->user->syncRoles(Role::findOrCreate($role, 'web'));
            foreach ($buttons() as $button) {
                $saved = $button->getAttribute('data-folio') === 'COT-000001';
                $this->assertStringContainsString('quotation-row-pill--'.($saved ? 'success' : 'pending'), $button->getAttribute('class'));
                $this->assertStringNotContainsString('quotation-row-pill--'.($saved ? 'pending' : 'success'), $button->getAttribute('class'));
            }
        }
    }

    public function test_missing_files_return_404_and_column_is_after_authorization(): void
    {
        $result = $this->upload()->assertOk();
        $document = RequestQuotationDocument::findOrFail($result->json('document.id'));
        Storage::disk('local')->delete($document->path);
        $this->get($result->json('document.url'))->assertNotFound();
        $response = $this->get(route('admin.solicitudes.cotizacion.index'))->assertOk()->assertSee('data-quotation-documents-dialog', false);
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $headers = $xpath->query('//table[@id="request-quotations-table"]//th');
        $this->assertSame('Solicitud (Foto o Archivo)', trim($headers->item(13)->textContent));
        $this->assertCount(5, $xpath->query('//tr[@data-quotation-row]/td[14]/button[@data-quotation-documents]'));
        $this->assertCount(5, $xpath->query('//button[@data-quotation-documents]/i[@data-quotation-document-icon="camera"]'));
        $this->assertCount(5, $xpath->query('//button[@data-quotation-documents]/i[@data-quotation-document-icon="paperclip"]'));
        $this->assertCount(5, $xpath->query('//button[@data-quotation-documents]/span[text()="/"]'));
    }
}
