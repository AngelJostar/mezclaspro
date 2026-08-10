<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Internal\StoreExternalMixtureDocument;
use App\Models\ExternalMixtureDocument;
use App\Models\ExternalMixtureRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExternalMixtureDocumentController extends Controller
{
    public function show(Request $request, string $remoteRequestId, ExternalMixtureDocument $document): StreamedResponse
    {
        abort_unless($request->user()?->tokenCan('requests:documents') === true, 403, 'El token no tiene permiso para descargar documentos.');

        $mixtureRequest = ExternalMixtureRequest::query()
            ->where('remote_request_id', $remoteRequestId)
            ->firstOrFail();

        abort_unless($document->external_mixture_request_id === $mixtureRequest->id, 404);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function store(StoreExternalMixtureDocument $request, string $remoteRequestId): JsonResponse
    {
        $mixtureRequest = ExternalMixtureRequest::query()->where('remote_request_id', $remoteRequestId)->firstOrFail();
        $file = $request->file('document');
        $sha256 = hash_file('sha256', $file->getRealPath());
        $existing = $mixtureRequest->documents()
            ->where('type', $request->string('type')->toString())
            ->where('sha256', $sha256)
            ->first();

        if ($existing) {
            return response()->json(['data' => $this->resource($existing)]);
        }

        $path = $file->storeAs(
            'integrations/dr-sam/'.$mixtureRequest->remote_request_id,
            Str::uuid().'.'.$file->guessExtension(),
            'local'
        );
        abort_unless($path, 500, 'No fue posible almacenar el documento.');

        $document = $mixtureRequest->documents()->create([
            'type' => $request->string('type')->toString(),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sha256' => $sha256,
            'disk' => 'local',
            'path' => $path,
            'uploaded_at' => now(),
        ]);

        return response()->json(['data' => $this->resource($document)], 201);
    }

    private function resource(ExternalMixtureDocument $document): array
    {
        return [
            'id' => $document->id,
            'type' => $document->type,
            'name' => $document->original_name,
            'mime_type' => $document->mime_type,
            'size' => $document->size,
            'sha256' => $document->sha256,
            'uploaded_at' => $document->uploaded_at?->toIso8601String(),
        ];
    }
}
