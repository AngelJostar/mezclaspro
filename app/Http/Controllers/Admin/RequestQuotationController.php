<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\RequestQuotationsExport;
use App\Http\Requests\StoreRequestQuotationRequest;
use App\Mail\RequestQuotationMail;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\RequestQuotation;
use App\Models\User;
use App\Notifications\QuotationAssigned;
use App\Services\RequestQuotationCaptureService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;

class RequestQuotationController extends Controller
{
    public function email(Request $request, RequestQuotation $quotation)
    {
        abort_unless($quotation->canBeViewedBy($request->user()), 403);
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254', 'not_regex:/[\r\n]/'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $mailer = config('mail.mailers.'.config('mail.default'), []);
        $transport = $mailer['transport'] ?? null;
        $host = strtolower((string) (empty($mailer['url']) ? ($mailer['host'] ?? '') : parse_url($mailer['url'], PHP_URL_HOST)));
        // Test transports must never produce a successful delivery confirmation.
        if (!in_array($transport, ['smtp', 'sendmail', 'ses', 'ses-v2', 'postmark', 'mailgun'], true)
            || ($transport === 'smtp' && in_array($host, ['', 'sandbox.smtp.mailtrap.io', 'smtp.mailtrap.io', 'mailpit', 'mailhog'], true))) {
            return response()->json(['message' => 'El correo no esta configurado para entregas reales. Solicita configurar el SMTP de produccion; no se envio ningun correo.'], 503);
        }

        try {
            Mail::to($data['email'])->send(new RequestQuotationMail($quotation, $data['note'] ?? ''));
        } catch (\Throwable $error) {
            Log::warning('Quotation mail delivery failed.', ['quotation_id' => $quotation->id, 'user_id' => $request->user()->id, 'exception' => get_class($error)]);
            return response()->json(['message' => 'No se pudo confirmar el envio del correo. Revisa la configuracion de correo antes de volver a intentar.'], 502);
        }

        return response()->json(['message' => 'Cotizacion enviada al servidor de correo para su entrega.']);
    }

    public function options(Request $request, RequestQuotationCaptureService $capture)
    {
        $data = $request->validate(['category' => 'required|in:oncologicos,nutricionales', 'hospital_id' => 'required|integer|min:1']);
        return response()->json($capture->catalog($request->user(), $data['category'], (int) $data['hospital_id']));
    }

    public function show(Request $request, RequestQuotation $quotation)
    {
        abort_unless($quotation->canBeViewedBy($request->user()), 403);
        return response()->json([
            'folio' => $quotation->folio, 'clinical_data' => $quotation->clinical_data,
            'pricing_snapshot' => $quotation->pricing_snapshot,
            'seller_id' => $quotation->seller_id, 'seller_name' => $quotation->seller_name,
            'editable' => $quotation->canBeEditedBy($request->user()),
            'update_url' => route('admin.solicitudes.cotizacion.update', $quotation),
            'attachment_url' => $quotation->attachment_path ? route('admin.solicitudes.cotizacion.attachment', $quotation) : null,
        ]);
    }

    public function attachment(Request $request, RequestQuotation $quotation)
    {
        abort_unless($quotation->canBeViewedBy($request->user()), 403);
        abort_unless($quotation->attachment_path && Storage::disk('local')->exists($quotation->attachment_path), 404);
        return Storage::disk('local')->download($quotation->attachment_path,
            'firma-'.$quotation->folio.'.'.pathinfo($quotation->attachment_path, PATHINFO_EXTENSION),
            ['X-Content-Type-Options' => 'nosniff']);
    }

    public function store(StoreRequestQuotationRequest $request, RequestQuotationCaptureService $capture)
    {
        $existing = RequestQuotation::where('submission_key', $request->validated('submission_key'))->first();
        if ($existing) {
            abort_unless((int) $existing->created_by === (int) $request->user()->id && $existing->canBeViewedBy($request->user()), 403);
            return $this->saved($existing);
        }
        $attributes = $capture->capture($request->user(), $request->validated());
        $attributes['seller_id'] = $this->sellerId($request);
        $path = $this->storeAttachment($request);
        try {
            $quotation = DB::transaction(function () use ($attributes, $request, $path) {
                $quotation = RequestQuotation::forceCreate($attributes + [
                    'created_by' => $request->user()->id, 'submission_key' => $request->validated('submission_key'),
                    'status' => $request->validated('action') === 'send' ? 'enviada' : 'borrador',
                    'sent_at' => $request->validated('action') === 'send' ? now() : null, 'attachment_path' => $path,
                ]);
                $this->notifySeller($quotation);
                return $quotation;
            });
        } catch (UniqueConstraintViolationException $error) {
            if ($path) Storage::disk('local')->delete($path);
            $quotation = RequestQuotation::where('submission_key', $request->validated('submission_key'))->first();
            if (!$quotation) throw $error;
            abort_unless((int) $quotation->created_by === (int) $request->user()->id && $quotation->canBeViewedBy($request->user()), 403);
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
        return $this->saved($quotation);
    }

    public function update(StoreRequestQuotationRequest $request, RequestQuotation $quotation, RequestQuotationCaptureService $capture)
    {
        abort_unless($quotation->canBeEditedBy($request->user()), 403);
        $attributes = $capture->capture($request->user(), $request->validated());
        $path = $this->storeAttachment($request);
        $previousPath = null;
        try {
            DB::transaction(function () use ($request, $quotation, $attributes, $path, &$previousPath) {
                $locked = RequestQuotation::lockForUpdate()->findOrFail($quotation->id);
                abort_unless($locked->canBeEditedBy($request->user()), 403);
                $attributes['seller_id'] = $this->sellerId($request, $locked);
                $previousPath = $locked->attachment_path;
                $locked->forceFill($attributes + [
                    'status' => $request->validated('action') === 'send' ? 'enviada' : 'borrador',
                    'sent_at' => $request->validated('action') === 'send' ? now() : null,
                    'attachment_path' => $path ?: ($attributes['category'] === 'oncologicos' ? $previousPath : null),
                ])->save();
                $this->notifySeller($locked);
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
        if ($previousPath && ($path || $attributes['category'] !== 'oncologicos')) Storage::disk('local')->delete($previousPath);
        return $this->saved($quotation->refresh());
    }

    private function sellerId(StoreRequestQuotationRequest $request, ?RequestQuotation $quotation = null): ?int
    {
        $user = $request->user();
        $id = $request->validated('seller_id', $quotation?->seller_id);
        if ($user->isSalesperson()) {
            $assignedId = $quotation ? $quotation->seller_id : $user->id;
            if ($request->exists('seller_id') && (int) $id !== (int) $assignedId) {
                throw ValidationException::withMessages(['seller_id' => 'No puedes cambiar el vendedor asignado.']);
            }
            $id = $assignedId;
        }
        if (!$id) return null;
        // An inactive assignment may remain in a draft, but cannot receive a new submission.
        if ($quotation && (int) $quotation->seller_id === (int) $id && $request->validated('action') === 'save') {
            return (int) $id;
        }
        if (!User::activeSalespeople()->whereKey($id)->exists()) {
            throw ValidationException::withMessages(['seller_id' => 'Selecciona un vendedor activo.']);
        }
        return (int) $id;
    }

    private function notifySeller(RequestQuotation $quotation): void
    {
        if ($quotation->status === 'enviada' && $quotation->seller_id
            && (int) $quotation->seller_id !== (int) $quotation->created_by) {
            User::activeSalespeople()->find($quotation->seller_id)?->notify(new QuotationAssigned($quotation));
        }
    }

    private function storeAttachment(StoreRequestQuotationRequest $request): ?string
    {
        if (!$request->hasFile('attachment')) return null;
        $path = $request->file('attachment')->store('request-quotations', 'local');
        if (!$path) throw ValidationException::withMessages(['attachment' => 'No fue posible guardar el archivo. Intenta de nuevo.']);
        return $path;
    }

    private function saved(RequestQuotation $quotation)
    {
        return response()->json(['folio' => $quotation->folio, 'status' => $quotation->status, 'total' => $quotation->total,
            'redirect_url' => route('admin.solicitudes.cotizacion.index', ['tipo' => $quotation->category, 'buscar' => $quotation->folio])]);
    }

    public function export(Request $request)
    {
        return Excel::download(new RequestQuotationsExport($this->screenData($request)['quotations']), 'Cotizaciones-'.now()->format('Y-m-d').'.xlsx');
    }

    public function authorizeQuotation(Request $request, RequestQuotation $quotation)
    {
        abort_unless($quotation->canBeAuthorizedBy($request->user()), 403);

        DB::transaction(function () use ($quotation, $request) {
            $locked = RequestQuotation::query()->lockForUpdate()->findOrFail($quotation->id);
            if ($locked->status === 'autorizada') {
                return;
            }
            if ($locked->status !== 'enviada' || $locked->total === null || $locked->total < 0) {
                throw ValidationException::withMessages([
                    'quotation' => 'Solo puedes autorizar una cotizacion enviada con un importe registrado.',
                ]);
            }
            $locked->forceFill([
                'status' => 'autorizada', 'authorized_by' => $request->user()->id, 'authorized_at' => now(),
            ])->save();
        });

        return back()->with('quotation_status', 'Cotizacion autorizada por Prodifem.');
    }

    public function index(Request $request)
    {
        return view('admin.solicitudes.cotizacion', $this->screenData($request));
    }

    private function screenData(Request $request): array
    {
        $user = $request->user();
        $isSalesperson = $user->isSalesperson();
        $canViewNutrition = $isSalesperson || $user->can('nutricionales_solicitudes_index');
        $canViewOncology = $isSalesperson || $user->can('oncologicos_solicitudes_index');
        abort_unless($canViewNutrition || $canViewOncology, 403);
        $allowedTypes = array_merge($canViewNutrition ? ['nutricionales'] : [],
            $canViewOncology ? ['oncologicos', 'antibioticos'] : []);

        $selectedType = $request->query('tipo', 'todas');
        if (!in_array($selectedType, ['todas', 'nutricionales', 'oncologicos', 'antibioticos'], true)) {
            $selectedType = 'todas';
        }
        abort_if($selectedType !== 'todas' && !in_array($selectedType, $allowedTypes, true), 403);
        $statusFilter = $request->query('estado', 'todas');
        if (!is_string($statusFilter) || !array_key_exists($statusFilter, RequestQuotation::STATUS_FILTERS)) {
            $statusFilter = 'todas';
        }

        $validated = $request->validate([
            'institucion_id' => ['nullable', 'integer', 'min:1'],
            'hospital_id' => ['nullable', 'integer', 'min:1'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => array_filter(['nullable', 'date_format:Y-m-d', $request->filled('desde') ? 'after_or_equal:desde' : null]),
            'buscar' => ['nullable', 'string', 'max:200'],
        ]);
        $filters = array_merge(['institucion_id' => null, 'hospital_id' => null, 'desde' => null, 'hasta' => null, 'buscar' => ''], $validated);
        $filters['buscar'] = trim($filters['buscar'] ?? '');
        $sortDirection = $request->query('direccion') === 'asc' ? 'asc' : 'desc';
        $sort = $request->query('orden') === 'total' ? 'total' : 'fecha';
        $isHospitalView = $user->hasAnyRole(['Cliente', 'Institucion']);

        $hospitals = Hospital::query()->select('id', 'name')->with('instituciones:id,nombre')
            ->when($isHospitalView, fn ($query) => $query->whereKey($user->hospital_id ?: 0))
            ->orderBy('name')->get();
        $institutions = Institucion::query()->select('id', 'nombre')
            ->whereHas('hospitals', fn ($query) => $query->whereIn('hospitals.id', $hospitals->modelKeys()))
            ->orderBy('nombre')->get();

        $query = RequestQuotation::query()->with(['hospital:id,name', 'institution:id,nombre', 'authorizer:id,name,lastname', 'seller:id,name,lastname'])
            ->forSalesperson($user)
            ->whereIn('category', $selectedType === 'todas' ? $allowedTypes : [$selectedType])
            ->when($isHospitalView, fn ($query) => $query->where('hospital_id', $user->hospital_id ?: 0))
            ->when($filters['institucion_id'], fn ($query, $id) => $query->where('institution_id', $id))
            ->when($filters['hospital_id'], fn ($query, $id) => $query->where('hospital_id', $id))
            ->when($filters['desde'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['hasta'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date));

        if ($filters['buscar'] !== '') {
            $search = $filters['buscar'];
            $folioId = preg_match('/^(?:COT-)?0*([0-9]+)$/i', $search, $matches) ? (int) $matches[1] : null;
            $query->where(function ($query) use ($search, $folioId) {
                // Bound parameters keep free-text search separate from SQL.
                $query->where('patient_name', 'like', '%'.$search.'%');
                if ($folioId !== null) {
                    $query->orWhere('id', $folioId);
                }
            });
        }
        $state = match ($statusFilter) {
            'recibidas', 'enviadas' => 'enviada', 'autorizadas' => 'autorizada', 'preparacion' => 'preparacion', default => null,
        };
        $quotations = $query->when($state, fn ($query, $state) => $query->where('status', $state))
            ->when($statusFilter === 'recibidas', fn ($query) => $query->where('created_by', '<>', $user->id))
            ->orderBy($sort === 'total' ? 'total' : 'created_at', $sortDirection)->orderByDesc('id')->get();
        $filterQuery = array_filter(array_merge($filters, [
            'tipo' => $selectedType, 'estado' => $statusFilter, 'orden' => $sort, 'direccion' => $sortDirection,
        ]), fn ($value) => $value !== null && $value !== '');

        $createTypes = array_values(array_filter(['nutricionales', 'oncologicos'], fn ($type) => RequestQuotation::canCreate($user, $type)));
        $sellers = $isSalesperson ? collect() : User::activeSalespeople()->orderBy('name')->orderBy('lastname')->get(['id', 'name', 'lastname']);
        return compact('quotations', 'hospitals', 'institutions', 'filters', 'createTypes',
            'sellers', 'isSalesperson',
            'selectedType', 'statusFilter', 'sort', 'sortDirection', 'filterQuery', 'canViewNutrition', 'canViewOncology');
    }
}
