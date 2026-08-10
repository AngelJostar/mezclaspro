<?php

namespace Tests\Feature;

use App\Models\ExternalMixtureRequest;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExternalMixtureDocumentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_a_private_document_idempotently(): void
    {
        Storage::fake('local');
        $user = User::query()->create([
            'name' => 'Dr. Sam',
            'lastname' => 'Integracion',
            'username' => 'drsam.documents',
            'password' => bcrypt('secret'),
            'is_active' => true,
        ]);
        Sanctum::actingAs($user, ['requests:documents']);
        $hospital = Hospital::query()->create([
            'external_code' => 'HOSP-DOC-1',
            'name' => 'Hospital Documentos',
            'adress' => 'Direccion',
            'is_active' => true,
        ]);
        $external = ExternalMixtureRequest::query()->create([
            'remote_request_id' => (string) Str::uuid(),
            'local_external_id' => (string) Str::uuid(),
            'hospital_id' => $hospital->id,
            'catalog_type' => 'npt',
            'status' => 'pending',
            'payload_hash' => str_repeat('a', 64),
            'payload' => [],
            'received_at' => now(),
        ]);
        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF";

        $first = $this->postJson('/api/internal/v1/mixture-requests/'.$external->remote_request_id.'/documents', [
            'type' => 'authorization',
            'document' => UploadedFile::fake()->createWithContent('autorizacion.pdf', $pdf),
        ]);
        $second = $this->postJson('/api/internal/v1/mixture-requests/'.$external->remote_request_id.'/documents', [
            'type' => 'authorization',
            'document' => UploadedFile::fake()->createWithContent('autorizacion.pdf', $pdf),
        ]);

        $first->assertCreated()->assertJsonPath('data.type', 'authorization');
        $second->assertOk()->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertDatabaseCount('external_mixture_documents', 1);
        $document = $external->documents()->sole();
        Storage::disk('local')->assertExists($document->path);
        $this->assertStringNotContainsString('autorizacion.pdf', $document->path);

        $download = $this->get('/api/internal/v1/mixture-requests/'.$external->remote_request_id.'/documents/'.$document->id);

        $download->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff');
        $this->assertSame($pdf, $download->streamedContent());
    }

    public function test_it_does_not_download_a_document_from_another_request(): void
    {
        Storage::fake('local');
        $user = User::query()->create([
            'name' => 'Dr. Sam',
            'lastname' => 'Integracion',
            'username' => 'drsam.documents.cross',
            'password' => bcrypt('secret'),
            'is_active' => true,
        ]);
        Sanctum::actingAs($user, ['requests:documents']);
        $hospital = Hospital::query()->create([
            'external_code' => 'HOSP-DOC-2',
            'name' => 'Hospital Documentos 2',
            'adress' => 'Direccion',
            'is_active' => true,
        ]);
        $first = ExternalMixtureRequest::query()->create([
            'remote_request_id' => (string) Str::uuid(), 'local_external_id' => (string) Str::uuid(),
            'hospital_id' => $hospital->id, 'catalog_type' => 'npt', 'status' => 'pending',
            'payload_hash' => str_repeat('b', 64), 'payload' => [], 'received_at' => now(),
        ]);
        $second = ExternalMixtureRequest::query()->create([
            'remote_request_id' => (string) Str::uuid(), 'local_external_id' => (string) Str::uuid(),
            'hospital_id' => $hospital->id, 'catalog_type' => 'npt', 'status' => 'pending',
            'payload_hash' => str_repeat('c', 64), 'payload' => [], 'received_at' => now(),
        ]);
        $document = $first->documents()->create([
            'type' => 'authorization', 'original_name' => 'private.pdf', 'mime_type' => 'application/pdf',
            'size' => 3, 'sha256' => hash('sha256', 'pdf'), 'disk' => 'local',
            'path' => 'private/document.pdf', 'uploaded_at' => now(),
        ]);
        Storage::disk('local')->put($document->path, 'pdf');

        $this->get('/api/internal/v1/mixture-requests/'.$second->remote_request_id.'/documents/'.$document->id)
            ->assertNotFound();
    }

    public function test_remission_endpoint_requires_a_materialized_request_with_a_remission(): void
    {
        $user = User::query()->create([
            'name' => 'Dr. Sam', 'lastname' => 'Integracion', 'username' => 'drsam.remission',
            'password' => bcrypt('secret'), 'is_active' => true,
        ]);
        Sanctum::actingAs($user, ['requests:read']);
        $hospital = Hospital::query()->create([
            'external_code' => 'HOSP-REM-1', 'name' => 'Hospital Remision',
            'adress' => 'Direccion', 'is_active' => true,
        ]);
        $external = ExternalMixtureRequest::query()->create([
            'remote_request_id' => (string) Str::uuid(), 'local_external_id' => (string) Str::uuid(),
            'hospital_id' => $hospital->id, 'catalog_type' => 'npt', 'status' => 'pending',
            'payload_hash' => str_repeat('d', 64), 'payload' => [], 'received_at' => now(),
        ]);

        $this->get('/api/internal/v1/mixture-requests/'.$external->remote_request_id.'/remission')
            ->assertNotFound();
    }
}
