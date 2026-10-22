<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{MaintenanceAppointment, MaintenanceDocument, MaintenanceProvider, MaintenanceService};
use App\Models\Oncologicos\Laboratory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaintenanceWorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $section = $filters['seccion'] ?? 'calendario';
        $mode = $filters['vista'] ?? 'semana';
        $date = Carbon::parse($filters['fecha'] ?? now()->toDateString())->startOfDay();
        [$start, $end] = $this->range($date, $mode);
        $laboratories = Laboratory::where('activo', true)->orderBy('nombre')->get();
        $laboratoryId = (int) ($filters['laboratory_id'] ?? 0);
        $allServices = MaintenanceService::with(['serviceProviders', 'laboratory'])->orderBy('service')->get();
        $services = $allServices->filter(fn ($s) => ! $laboratoryId || ! $s->laboratory_id || $s->laboratory_id === $laboratoryId);
        $providers = MaintenanceProvider::with(['services.laboratory', 'documents'])->orderBy('name')->get();
        $visibleServices = $services->filter(fn ($s) =>
            $this->matches($s->service.' SRV-'.str_pad($s->id, 3, '0', STR_PAD_LEFT).' '.$s->id.' '.$s->description, $filters['q'] ?? '') &&
            (empty($filters['tipo']) || $s->type === $filters['tipo']) &&
            (($filters['estado'] ?? 'active') === 'all' || $s->is_active === (($filters['estado'] ?? 'active') !== 'inactive')));
        $visibleProviders = $providers->filter(fn ($p) =>
            (! $laboratoryId || $p->services->isEmpty() || $p->services->contains(fn ($s) => ! $s->laboratory_id || $s->laboratory_id === $laboratoryId)) &&
            $this->matches($p->name.' '.$p->contact_name.' '.$p->email, $filters['q'] ?? '') &&
            (empty($filters['especialidad']) || $p->specialty === $filters['especialidad']) &&
            (($filters['estado'] ?? 'active') === 'all' || $p->is_active === (($filters['estado'] ?? 'active') !== 'inactive')));
        $appointments = $this->appointments($filters, $start, $end)->get();
        $selectedService = $visibleServices->firstWhere('id', $request->integer('seleccion')) ?? $visibleServices->first();
        $selectedProvider = $visibleProviders->firstWhere('id', $request->integer('seleccion')) ?? $visibleProviders->first();
        $selectedAppointment = $appointments->firstWhere('id', $request->integer('seleccion')) ?? $appointments->first();
        return view('admin.maintenance-qualifications.workspace', compact('section', 'mode', 'date', 'start', 'end', 'filters', 'laboratories', 'laboratoryId', 'allServices', 'services', 'providers', 'visibleServices', 'visibleProviders', 'appointments', 'selectedService', 'selectedProvider', 'selectedAppointment'));
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'seccion' => ['sometimes', Rule::in(['calendario', 'servicios', 'proveedores', 'programacion'])],
            'vista' => ['sometimes', Rule::in(['mes', 'semana', 'lista'])],
            'fecha' => ['sometimes', 'date_format:Y-m-d'],
            'laboratory_id' => ['nullable', 'integer', 'exists:laboratories,id'],
            'q' => ['nullable', 'string', 'max:150'],
            'tipo' => ['nullable', 'string', 'max:120'],
            'especialidad' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(['all', 'active', 'inactive', ...array_keys(MaintenanceAppointment::STATUSES)])],
            'seleccion' => ['nullable', 'integer'],
        ]);
    }

    private function range(Carbon $date, string $mode): array
    {
        return $mode === 'mes'
            ? [$date->copy()->startOfMonth()->startOfWeek(), $date->copy()->endOfMonth()->endOfWeek()]
            : [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()];
    }

    private function appointments(array $filters, Carbon $start, Carbon $end)
    {
        return MaintenanceAppointment::with(['service', 'provider', 'laboratory', 'documents'])
            ->whereBetween('starts_at', [$start, $end])
            ->when($filters['laboratory_id'] ?? null, fn ($q, $id) => $q->where('laboratory_id', $id))
            ->when(isset(MaintenanceAppointment::STATUSES[$filters['estado'] ?? '']), fn ($q) => $q->where('status', $filters['estado']))
            ->when($filters['tipo'] ?? null, fn ($q, $type) => $q->whereHas('service', fn ($q) => $q->where('type', $type)))
            ->when($filters['q'] ?? null, function ($q, $text) {
                $q->where(function ($q) use ($text) {
                    $q->where('asset', 'like', '%'.$text.'%')->orWhereHas('service', fn ($s) => $s->where('service', 'like', '%'.$text.'%'));
                    if (preg_match('/^(?:MTO-)?0*(\d+)$/i', $text, $match)) { $q->orWhere('id', $match[1]); }
                });
            })->orderBy('starts_at')->orderBy('id');
    }

    private function matches(string $text, string $search): bool
    {
        return $search === '' || str_contains(mb_strtolower($text), mb_strtolower($search));
    }

    public function saveService(Request $request, ?MaintenanceService $maintenanceService = null)
    {
        $data = $request->validate([
            'service' => ['required', 'string', 'max:255'],
            'laboratory_id' => ['nullable', Rule::exists('laboratories', 'id')->where('activo', true)],
            'type' => ['required', 'string', 'max:120'],
            'frequency' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'applicable_assets' => ['nullable', 'string', 'max:255'],
            'deliverables' => ['nullable', 'string', 'max:255'],
            'report_required' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'provider_ids' => ['present', 'array'],
            'provider_ids.*' => ['integer', 'distinct', 'exists:maintenance_providers,id'],
        ]);
        $service = DB::transaction(function () use ($data, $maintenanceService) {
            $ids = $data['provider_ids']; unset($data['provider_ids']);
            $service = $maintenanceService ?? new MaintenanceService();
            $service->fill($data)->save();
            $service->serviceProviders()->sync($ids);
            return $service;
        });
        return response()->json(['id' => $service->id, 'message' => 'Servicio guardado.']);
    }

    public function saveProvider(Request $request, ?MaintenanceProvider $maintenanceProvider = null)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'coverage' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'service_ids' => ['present', 'array'],
            'service_ids.*' => ['integer', 'distinct', 'exists:maintenance_services,id'],
        ]);
        $provider = DB::transaction(function () use ($data, $maintenanceProvider) {
            $ids = $data['service_ids']; unset($data['service_ids']);
            $provider = $maintenanceProvider ?? new MaintenanceProvider();
            $provider->fill($data)->save();
            $provider->services()->sync($ids);
            return $provider;
        });
        return response()->json(['id' => $provider->id, 'message' => 'Proveedor guardado.']);
    }

    public function saveAppointment(Request $request, ?MaintenanceAppointment $maintenanceAppointment = null)
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer', 'exists:maintenance_services,id'],
            'provider_id' => ['nullable', 'integer', 'exists:maintenance_providers,id'],
            'laboratory_id' => ['required', 'integer', 'exists:laboratories,id'],
            'asset' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'frequency' => ['nullable', 'string', 'max:120'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
            'next_service_at' => ['nullable', 'date_format:Y-m-d', 'after:starts_at'],
            'status' => ['required', Rule::in(array_keys(MaintenanceAppointment::STATUSES))],
            'report_notes' => ['nullable', 'string', 'max:10000'],
        ]);
        $appointment = DB::transaction(function () use ($data, $maintenanceAppointment) {
            $appointment = $maintenanceAppointment ? MaintenanceAppointment::lockForUpdate()->findOrFail($maintenanceAppointment->id) : new MaintenanceAppointment();
            abort_if($appointment->report_status === 'closed', 409, 'El reporte ya está cerrado.');
            $service = MaintenanceService::findOrFail($data['service_id']);
            $this->require($service->is_active || $appointment->service_id === $service->id, 'service_id', 'Selecciona un servicio activo.');
            $this->require(! $service->laboratory_id || $service->laboratory_id === (int) $data['laboratory_id'], 'laboratory_id', 'El servicio pertenece a otra central.');
            $this->require(Laboratory::findOrFail($data['laboratory_id'])->activo || $appointment->laboratory_id === (int) $data['laboratory_id'], 'laboratory_id', 'Selecciona una central activa.');
            if ($data['provider_id'] ?? null) {
                $provider = MaintenanceProvider::findOrFail($data['provider_id']);
                $unchanged = $appointment->provider_id === $provider->id && $appointment->service_id === $service->id;
                $this->require($unchanged || ($provider->is_active && $service->serviceProviders()->whereKey($provider->id)->exists()), 'provider_id', 'Vincula un proveedor activo al servicio antes de programarlo.');
            }
            if ($data['status'] === 'completed' && $service->report_required) {
                $data['report_status'] = $appointment->exists && $appointment->documents()->exists() ? 'review' : 'pending';
            }
            $appointment->fill($data)->save();
            return $appointment;
        });
        return response()->json(['id' => $appointment->id, 'date' => $appointment->starts_at->toDateString(), 'message' => 'Programación guardada.']);
    }

    public function upload(Request $request, string $kind, int $id)
    {
        $data = $request->validate(['file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:20480'], 'description' => ['nullable', 'string', 'max:255']]);
        $owner = $kind === 'provider' ? MaintenanceProvider::findOrFail($id) : MaintenanceAppointment::findOrFail($id);
        $path = $request->file('file')->store('mantenimiento/documentos', 'local');
        abort_unless($path, 500, 'No se pudo guardar el archivo.');
        try {
            DB::transaction(function () use ($owner, $kind, $request, $path, $data) {
                if ($kind === 'appointment') {
                    $owner = MaintenanceAppointment::lockForUpdate()->findOrFail($owner->id);
                    abort_if($owner->report_status === 'closed', 409, 'El reporte ya está cerrado.');
                }
                $owner->documents()->create([
                    'name' => basename($request->file('file')->getClientOriginalName()), 'path' => $path,
                    'mime' => $request->file('file')->getMimeType(), 'size' => $request->file('file')->getSize(),
                    'description' => $data['description'] ?? null,
                ]);
                if ($kind === 'appointment') { $owner->update(['report_status' => 'review']); }
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }
        return response()->json(['message' => 'Archivo guardado.']);
    }

    public function document(Request $request, MaintenanceDocument $maintenanceDocument)
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($maintenanceDocument->path), 404);
        $inline = ! $request->boolean('download') && in_array($maintenanceDocument->mime, ['application/pdf', 'image/jpeg', 'image/png'], true);
        return $disk->response($maintenanceDocument->path, $maintenanceDocument->name, ['Content-Type' => $maintenanceDocument->mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'], $inline ? 'inline' : 'attachment');
    }

    public function closeReport(Request $request, MaintenanceAppointment $maintenanceAppointment)
    {
        DB::transaction(function () use ($request, $maintenanceAppointment) {
            $appointment = MaintenanceAppointment::lockForUpdate()->findOrFail($maintenanceAppointment->id);
            abort_if($appointment->report_status === 'closed', 409, 'El reporte ya está cerrado.');
            $this->require($appointment->status === 'completed', 'status', 'Registra el servicio como realizado antes de cerrar su reporte.');
            $this->require(! $appointment->service->report_required || $appointment->documents()->exists(), 'file', 'Adjunta el reporte requerido antes de cerrar el servicio.');
            $appointment->update(['report_status' => 'closed', 'closed_at' => now(), 'reviewed_by' => $request->user()->id]);
        });
        return response()->json(['message' => 'Reporte revisado y cerrado.']);
    }

    private function require(bool $condition, string $field, string $message): void
    {
        if (! $condition) { throw ValidationException::withMessages([$field => $message]); }
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        [$start, $end] = $this->range(Carbon::parse($filters['fecha'] ?? now()->toDateString()), $filters['vista'] ?? 'semana');
        $rows = $this->appointments($filters, $start, $end)->get();
        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w'); fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Folio', 'Central', 'Servicio', 'Activo', 'Proveedor', 'Fecha y hora', 'Frecuencia', 'Estado', 'Reporte']);
            foreach ($rows as $row) {
                $values = [$row->folio, $row->laboratory->nombre, $row->service->service, $row->asset, $row->provider?->name, $row->starts_at->format('d/m/Y H:i'), $row->frequency, MaintenanceAppointment::STATUSES[$row->status], MaintenanceAppointment::REPORT_STATUSES[$row->report_status]];
                fputcsv($file, array_map(fn ($v) => preg_match('/^[=+@\-\t\r\n]/u', (string) $v) ? "'".$v : $v, $values));
            }
            fclose($file);
        }, 'programacion-mantenimientos.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
