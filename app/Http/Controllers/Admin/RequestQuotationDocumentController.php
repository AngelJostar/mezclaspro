<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RequestQuotation;
use App\Models\RequestQuotationDocument;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RequestQuotationDocumentController extends Controller
{
    public function index(Request $request, RequestQuotation $quotation)
    {
        abort_unless($quotation->canBeViewedBy($request->user()), 403);
        return response()->json([
            'can_upload' => $quotation->canAttachRequestBy($request->user()),
            'preparation_url' => $quotation->canStartPreparationBy($request->user())
                ? route('admin.solicitudes.cotizacion.preparation', $quotation) : null,
            'documents' => $quotation->documents()->orderByDesc('id')->get()->map(fn ($document) => $this->documentData($quotation, $document)),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, RequestQuotation $quotation)
    {
        abort_unless($quotation->canAttachRequestBy($request->user()), 403);
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'upload_key' => ['required', 'uuid'],
        ]);
        $existing = RequestQuotationDocument::where('upload_key', $data['upload_key'])->first();
        if ($existing) return $this->saved($request, $quotation, $existing);

        $file = $request->file('file');
        $path = $file->store('request-quotations/'.$quotation->id.'/documents', 'local');
        if (!$path) throw ValidationException::withMessages(['file' => 'No fue posible guardar el archivo. Intenta de nuevo.']);
        try {
            $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
            $name = preg_replace('/[\x00-\x1f\x7f]/u', '', $name);
            $document = $quotation->documents()->create([
                'uploaded_by' => $request->user()->id, 'upload_key' => $data['upload_key'],
                'path' => $path, 'original_name' => Str::limit($name ?: 'Solicitud', 250, ''),
                'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
            ]);
        } catch (UniqueConstraintViolationException $error) {
            Storage::disk('local')->delete($path);
            $document = RequestQuotationDocument::where('upload_key', $data['upload_key'])->first();
            if (!$document) throw $error;
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }
        return $this->saved($request, $quotation, $document);
    }

    public function download(Request $request, RequestQuotation $quotation, RequestQuotationDocument $document)
    {
        abort_unless($quotation->canBeViewedBy($request->user()), 403);
        abort_unless((int) $document->request_quotation_id === (int) $quotation->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        return Storage::disk('local')->download($document->path,
            'Solicitud-'.$quotation->folio.'-'.$document->id.'.'.pathinfo($document->path, PATHINFO_EXTENSION),
            ['Content-Type' => $document->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    private function saved(Request $request, RequestQuotation $quotation, RequestQuotationDocument $document)
    {
        abort_unless((int) $document->request_quotation_id === (int) $quotation->id
            && (int) $document->uploaded_by === (int) $request->user()->id, 403);
        return response()->json(['message' => 'Solicitud guardada en '.$quotation->folio.'.',
            'preparation_url' => $quotation->fresh()->canStartPreparationBy($request->user())
                ? route('admin.solicitudes.cotizacion.preparation', $quotation) : null,
            'document' => $this->documentData($quotation, $document), 'count' => $quotation->documents()->count()]);
    }

    private function documentData(RequestQuotation $quotation, RequestQuotationDocument $document): array
    {
        return ['id' => $document->id, 'name' => $document->original_name, 'mime_type' => $document->mime_type,
            'size' => $document->size, 'created_at' => $document->created_at->format('d/m/Y H:i'),
            'url' => route('admin.solicitudes.cotizacion.documents.download', [$quotation, $document])];
    }
}
