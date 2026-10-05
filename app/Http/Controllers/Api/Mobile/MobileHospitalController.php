<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Nutricionales\Solicitud as NutritionRequest;
use App\Models\Oncologicos\SolicitudOnco;
use App\Models\User;
use App\Models\HospitalQuotationRequest;
use App\Models\RequestQuotation;
use App\Services\HospitalMobileCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileHospitalController extends Controller
{
    private function hospitalUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user?->is_active && $this->isHospitalUser($user), 403, 'Acceso hospitalario requerido.');
        return $user;
    }

    public function catalog(Request $request, HospitalMobileCatalog $catalog)
    {
        $user = $this->hospitalUser($request);
        $data = $request->validate(['category' => 'required|in:oncologicos,nutricionales,antibioticos']);
        return response()->json(['products' => $catalog->products($user->hospital, $data['category'])]);
    }

    public function wizardCatalog(Request $request, \App\Services\RequestQuotationCaptureService $capture)
    {
        $user = $this->hospitalUser($request);
        $data = $request->validate(['category' => 'required|in:oncologicos,nutricionales,antibioticos']);
        $catalog = $capture->commercialCatalog($user, $data + ['hospital_id' => $user->hospital_id], true);
        return response()->json($catalog + ['hospital' => ['id' => $user->hospital_id, 'name' => $user->hospital->name],
            'seller' => $user->hospital->salespeople()->activeSalespeople()->orderBy('users.id')->get(['users.id', 'users.name', 'users.lastname'])
                ->map(fn ($seller) => ['id' => $seller->id, 'name' => trim($seller->name.' '.$seller->lastname)])]);
    }

    private function wizardData(Request $request): array
    {
        $user = $this->hospitalUser($request);
        $form = \App\Http\Requests\StoreRequestQuotationRequest::createFrom($request);
        $data = $request->validate($form->rules());
        abort_unless(($data['flow'] ?? null) === 'commercial' && (int) $data['hospital_id'] === (int) $user->hospital_id, 403);
        if ($data['no_commercial_relationship'] || isset($data['seller_id'])) throw ValidationException::withMessages(['hospital_id' => 'La solicitud utiliza las relaciones y la lista asignadas a tu hospital.']);
        foreach ($data['items'] as $index => $item) {
            if (array_key_exists('unit_price_override', $item)) throw ValidationException::withMessages(["items.$index.unit_price_override" => 'El hospital no puede modificar sus precios asignados.']);
        }
        return $data;
    }

    public function wizardPreview(Request $request, \App\Services\RequestQuotationCaptureService $capture)
    {
        $result = $capture->capture($request->user(), $this->wizardData($request), true, true);
        return response()->json(['pricing_snapshot' => $result['pricing_snapshot'], 'pricing_token' => $result['pricing_token'],
            'document' => \App\Support\QuotationDocument::metadata($result['pricing_snapshot'])]);
    }

    public function wizardStore(Request $request, \App\Services\RequestQuotationCaptureService $capture)
    {
        $data = $this->wizardData($request);
        $user = $request->user();
        $existing = HospitalQuotationRequest::where('submission_key', $data['submission_key'])->first();
        if ($existing) {
            abort_unless((int) $existing->created_by === (int) $user->id && (int) $existing->hospital_id === (int) $user->hospital_id, 403);
            return response()->json($existing->load(['hospital', 'seller', 'quotation'])->summary());
        }
        $result = $capture->capture($user, $data, false, true);
        try {
            $record = DB::transaction(function () use ($user, $data, $result) {
                $seller = $user->hospital->salespeople()->activeSalespeople()->orderBy('users.id')->first();
                $items = collect($result['clinical_data']['items'])->map(fn ($item) => ['id' => $item['presentation_id'], 'presentation_id' => $item['presentation_id'],
                    'name' => $item['product_name'], 'presentation' => $item['presentation_name'], 'unit' => $item['unit'],
                    'quantity' => $item['bottle_count'] ?? $item['concentration'], 'mixture_number' => $item['mixture_number']])->all();
                $record = HospitalQuotationRequest::create(['hospital_id' => $user->hospital_id, 'created_by' => $user->id, 'seller_id' => $seller?->id,
                    'submission_key' => $data['submission_key'], 'category' => $data['category'], 'items' => $items,
                    'patient_name' => $result['patient_name'], 'observations' => $data['observations'] ?? null,
                    'capture_data' => ['clinical_data' => $result['clinical_data'], 'pricing_snapshot' => $result['pricing_snapshot']]]);
                $recipients = $user->hospital->salespeople()->activeSalespeople()->get();
                if ($user->hospital->laboratory_id) {
                    $central = User::where('is_active', true)->whereHas('personnelProfile', fn ($q) => $q->where('laboratory_id', $user->hospital->laboratory_id)->where('employment_status', 'hired'))
                        ->get()->filter(fn ($u) => !$u->isSalesperson() && !$u->hasAnyRole(['Cliente', 'Institucion']) && RequestQuotation::canCreate($u, $record->category));
                    $recipients = $recipients->concat($central);
                }
                foreach ($recipients->unique('id') as $recipient) {
                    if ($recipient->isBlockedByOrganization()) continue;
                    $recipient->notifications()->create(['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'hospital_quotation_request',
                        'data' => ['kind' => 'quotation_request_received', 'quotation_request_id' => $record->id,
                            'title' => 'Nueva solicitud de cotización', 'message' => $record->folio.' · '.$user->hospital->name,
                            'url' => route('admin.solicitudes.cotizacion.hospital-requests.index')]]);
                }
                return $record;
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $record = HospitalQuotationRequest::where('submission_key', $data['submission_key'])->firstOrFail();
            abort_unless((int) $record->created_by === (int) $user->id, 403);
        }
        return response()->json($record->load(['hospital', 'seller', 'quotation'])->summary(), 201);
    }

    public function wizardAttachment(Request $request, HospitalQuotationRequest $hospitalRequest)
    {
        $user = $this->hospitalUser($request);
        abort_unless((int) $hospitalRequest->hospital_id === (int) $user->hospital_id && (int) $hospitalRequest->created_by === (int) $user->id && !$hospitalRequest->quotation_id, 403);
        $request->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120']);
        $path = $request->file('file')->store('hospital-quotation-requests', 'local');
        try { $hospitalRequest->update(['attachment_path' => $path]); } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        return response()->json($hospitalRequest->summary());
    }

    public function quotationRequests(Request $request)
    {
        $user = $this->hospitalUser($request);
        return response()->json(HospitalQuotationRequest::where('hospital_id', $user->hospital_id)
            ->with(['hospital', 'seller', 'quotation'])->latest('id')->paginate(20)->through(fn ($r) => $r->summary()));
    }

    public function storeQuotationRequest(Request $request, HospitalMobileCatalog $catalog)
    {
        $user = $this->hospitalUser($request);
        // Multipart requests encode the structured items as JSON.
        if (is_string($request->input('items'))) $request->merge(['items' => json_decode($request->input('items'), true)]);
        $data = $request->validate([
            'submission_key' => 'required|uuid', 'category' => 'required|in:oncologicos,nutricionales,antibioticos',
            'patient_name' => 'nullable|string|max:255', 'observations' => 'nullable|string|max:2000',
            'items' => 'required_without:attachment|array|max:50', 'items.*' => 'array:presentation_id,quantity',
            'items.*.presentation_id' => 'required|integer|min:1|distinct',
            'items.*.quantity' => 'required|numeric|min:0.0001|max:1000000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        $existing = HospitalQuotationRequest::where('submission_key', $data['submission_key'])->first();
        if ($existing) {
            abort_unless((int) $existing->created_by === (int) $user->id && (int) $existing->hospital_id === (int) $user->hospital_id, 403);
            return response()->json($existing->summary());
        }
        $products = collect($catalog->products($user->hospital, $data['category']))->keyBy('id');
        $items = [];
        foreach ($data['items'] ?? [] as $i => $item) {
            $product = $products->get($item['presentation_id']);
            if (!$product) throw ValidationException::withMessages(["items.$i.presentation_id" => 'El insumo no pertenece al catálogo vigente del hospital.']);
            $items[] = $product + ['quantity' => (float) $item['quantity']];
        }
        if (!$items && !$request->hasFile('attachment')) throw ValidationException::withMessages(['items' => 'Agrega insumos o una fotografía de la receta.']);
        $path = $request->file('attachment')?->store('hospital-quotation-requests', 'local');
        try {
            $seller = $user->hospital->salespeople()->activeSalespeople()->orderBy('users.id')->first();
            $record = HospitalQuotationRequest::create([
                'hospital_id' => $user->hospital_id, 'created_by' => $user->id, 'seller_id' => $seller?->id,
                'submission_key' => $data['submission_key'], 'category' => $data['category'], 'items' => $items,
                'patient_name' => $data['patient_name'] ?? null, 'observations' => $data['observations'] ?? null,
                'attachment_path' => $path,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            if ($path) Storage::disk('local')->delete($path);
            $record = HospitalQuotationRequest::where('submission_key', $data['submission_key'])->first();
            if (!$record) throw $e;
            abort_unless((int) $record->created_by === (int) $user->id, 403);
        } catch (\Throwable $e) {
            if ($path) Storage::disk('local')->delete($path);
            throw $e;
        }
        return response()->json($record->load(['hospital', 'seller', 'quotation'])->summary(), 201);
    }

    private function visibleQuotations(User $user)
    {
        return RequestQuotation::where('hospital_id', $user->hospital_id)->where('status', '<>', 'borrador');
    }

    public function quotations(Request $request)
    {
        $user = $this->hospitalUser($request);
        return response()->json($this->visibleQuotations($user)->with('seller')->latest('id')->paginate(20)->through(fn ($q) => [
            'id' => $q->id, 'folio' => $q->folio, 'category' => $q->category, 'status' => $q->status_label,
            'total' => $q->total, 'seller' => $q->seller_name, 'created_at' => $q->created_at?->toIso8601String(),
        ]));
    }

    public function quotation(Request $request, int $quotation)
    {
        $user = $this->hospitalUser($request);
        $q = $this->visibleQuotations($user)->with('seller')->find($quotation);
        abort_unless($q, 403);
        return response()->json(['id' => $q->id, 'folio' => $q->folio, 'category' => $q->category, 'status' => $q->status_label,
            'seller' => $q->seller_name, 'total' => $q->total, 'pricing_snapshot' => $q->pricing_snapshot,
            'document' => \App\Support\QuotationDocument::metadata($q->pricing_snapshot ?? [], $q->created_at)]);
    }

    public function orders(Request $request)
    {
        $user = $this->hospitalUser($request);
        $onco = DB::table('solicitud_oncos')->where('hospital_id', $user->hospital_id)->whereIn('tipo_solicitud', ['oncologicos', 'antibioticos'])
            ->select(['id', DB::raw('tipo_solicitud as category'), DB::raw('estado as status'), 'created_at', DB::raw('fecha_entrega as delivery_at')]);
        $nutrition = DB::table('solicituds')->where(fn ($q) => $q->where('hospital_id', $user->hospital_id)
            ->orWhere(fn ($q) => $q->whereNull('hospital_id')->whereIn('user_id', User::where('hospital_id', $user->hospital_id)->select('id'))))
            ->select(['id', DB::raw("'nutricionales' as category"), DB::raw('estado as status'), 'created_at', DB::raw('NULL as delivery_at')]);
        return response()->json($onco->unionAll($nutrition)->orderByDesc('created_at')->paginate(20)
            ->through(fn ($o) => (array) $o + ['folio' => ($o->category === 'nutricionales' ? 'NPT-' : 'ONC-').$o->id]));
    }

    public function order(Request $request, string $type, int $id)
    {
        $user = $this->hospitalUser($request);
        if ($type === 'nutricionales') {
            $record = NutritionRequest::where(fn ($q) => $q->where('hospital_id', $user->hospital_id)->orWhere(fn ($q) => $q->whereNull('hospital_id')
                ->whereHas('user', fn ($u) => $u->where('hospital_id', $user->hospital_id))))->with(['solicitud_detail', 'quotation.seller'])->find($id);
        } else {
            $record = SolicitudOnco::where('hospital_id', $user->hospital_id)->where('tipo_solicitud', $type)->with(['mezclas.medicamentos', 'quotation.seller'])->find($id);
        }
        abort_unless($record, 403);
        return response()->json(['id' => $record->id, 'folio' => ($type === 'nutricionales' ? 'NPT-' : 'ONC-').$id, 'category' => $type,
            'status' => $record->estado ?: 'pendiente', 'created_at' => $record->created_at?->toIso8601String(),
            'delivery_at' => $type === 'nutricionales' ? $record->solicitud_detail?->fecha_hora_entrega : $record->fecha_entrega?->toIso8601String(),
            'seller' => $record->quotation?->seller_name, 'quotation' => $record->quotation?->folio,
            'mixtures' => $type === 'nutricionales' ? [['name' => 'Nutrición parenteral', 'status' => $record->estado]]
                : $record->mezclas->map(fn ($m) => ['name' => $m->medicamentos->pluck('nombre_medicamento')->filter()->join(', ') ?: 'Mezcla', 'status' => $m->estado])->all()]);
    }
    public function dashboard(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('hospital:id,name,short_name,is_active,access_is_active');

        $this->hospitalUser($request);

        $hospitalId = (int) $user->hospital_id;
        $oncoPending = SolicitudOnco::query()
            ->where('hospital_id', $hospitalId)
            ->whereIn('tipo_solicitud', ['oncologicos', 'antibioticos'])
            ->where(function ($query) {
                $query->whereNull('estado')->orWhere('estado', 'pendiente');
            })
            ->count();

        $nutritionPending = NutritionRequest::query()
            ->where(fn ($q) => $q->where('hospital_id', $hospitalId)->orWhere(fn ($q) => $q->whereNull('hospital_id')
                ->whereHas('user', fn ($u) => $u->where('hospital_id', $hospitalId))))
            ->where(function ($query) {
                $query->whereNull('estado')->orWhere('estado', 'pendiente');
            })
            ->count();

        return response()->json([
            'hospital' => [
                'id' => $user->hospital->id,
                'name' => $user->hospital->name,
                'short_name' => $user->hospital->short_name,
            ],
            'requests' => [
                'oncology_pending' => $oncoPending,
                'nutrition_pending' => $nutritionPending,
                'pending_total' => $oncoPending + $nutritionPending,
            ],
        ]);
    }

    private function isHospitalUser(User $user): bool
    {
        return (bool) $user->hospital_id
            && ! $user->isBlockedByOrganization()
            && $user->hasAnyRole(['Cliente', 'Institucion'])
            && $user->hospital
            && $user->hospital->is_active
            && $user->hospital->access_is_active;
    }
}
