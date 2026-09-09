<?php

namespace App\Http\Controllers\Admin;

use Carbon\CarbonImmutable;
use App\Http\Controllers\Controller;
use App\Models\MedicineRemainderMovement;
use App\Models\InspectionWaste;
use App\Models\Nutricionales\MedicineStockMovement;
use App\Models\Oncologicos\MedicineBatchMovement;
use App\Models\User;
use App\Models\WasteAuthorizationRequest;
use App\Services\UserAccessService;
use App\Services\WasteAuthorizationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SuperAdministratorController extends Controller
{
    private const WASTE_MONTH_OPTIONS = [
        '01' => 'Enero',
        '02' => 'Febrero',
        '03' => 'Marzo',
        '04' => 'Abril',
        '05' => 'Mayo',
        '06' => 'Junio',
        '07' => 'Julio',
        '08' => 'Agosto',
        '09' => 'Septiembre',
        '10' => 'Octubre',
        '11' => 'Noviembre',
        '12' => 'Diciembre',
    ];

    private const EXCLUDED_PERSONNEL_ROLES = [
        'Admin',
        'Super Admin',
        'Institucion',
        'Cliente',
    ];

    public function __construct(private readonly UserAccessService $userAccessService)
    {
    }

    public function index(Request $request): View
    {
        $administrators = User::query()
            ->role('Admin')
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'Super Admin'))
            ->with(['hospital:id,name', 'roles:id,name'])
            ->orderBy('name')
            ->orderBy('lastname')
            ->get();

        $initialWasteView = in_array(
            $request->query('waste_view'),
            ['all', 'remanente', 'frasco', 'inspeccion', 'requests'],
            true
        ) ? (string) $request->query('waste_view') : 'all';
        $wasteDateRange = $this->wasteDateRange($request);
        $wasteDateRange['open'] = $wasteDateRange['open'] || $initialWasteView === 'requests';
        $wasteRecords = $this->wasteRecords($wasteDateRange);
        $wasteSummary = [
            'all' => $wasteRecords->count(),
            'remanente' => $wasteRecords->where('type', 'remanente')->count(),
            'frasco' => $wasteRecords->where('type', 'frasco')->count(),
            'inspeccion' => $wasteRecords->where('type', 'inspeccion')->count(),
        ];
        $wasteTotals = [];
        foreach (['all', 'remanente', 'frasco', 'inspeccion'] as $type) {
            $rows = $type === 'all' ? $wasteRecords : $wasteRecords->where('type', $type);
            $wasteTotals[$type] = \App\Support\WasteReportUnits::sum($rows->pluck('units'));
        }
        $wasteMonthOptions = self::WASTE_MONTH_OPTIONS;
        $selectedYears = array_filter([
            $wasteDateRange['from_year'],
            $wasteDateRange['to_year'],
        ]);
        $minimumWasteYear = min([2000, ...$selectedYears]);
        $maximumWasteYear = max([now()->year + 1, ...$selectedYears]);
        $wasteYearOptions = range($maximumWasteYear, $minimumWasteYear);
        $wasteAuthorizationRequests = Schema::hasTable('waste_authorization_requests')
            ? $this->applyWasteDateRange(WasteAuthorizationRequest::query()->with(['requester', 'reviewer']), $wasteDateRange)
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [WasteAuthorizationRequest::STATUS_PENDING])
                ->orderByDesc('created_at')
                ->get()
            : collect();
        $pendingWasteAuthorizationCount = $wasteAuthorizationRequests
            ->where('status', WasteAuthorizationRequest::STATUS_PENDING)
            ->count();
        $requestedUnitTotals = \App\Support\WasteReportUnits::sum($wasteAuthorizationRequests->map(fn ($item) => [
            'frascos' => (float) $item->quantity_containers, 'mL' => (float) $item->quantity_ml,
        ]));

        return view('admin.superadministrator.index', compact(
            'administrators',
            'wasteRecords',
            'wasteSummary',
            'wasteTotals',
            'requestedUnitTotals',
            'wasteDateRange',
            'wasteMonthOptions',
            'wasteYearOptions',
            'wasteAuthorizationRequests',
            'pendingWasteAuthorizationCount',
            'initialWasteView'
        ));
    }

    public function dismiss(Request $request, User $administrator): RedirectResponse
    {
        $this->ensureSuperAdministrator($request);

        DB::transaction(function () use ($administrator) {
            $lockedAdministrator = User::query()
                ->lockForUpdate()
                ->findOrFail($administrator->id);

            if (! $lockedAdministrator->hasRole('Admin') || $lockedAdministrator->hasRole('Super Admin')) {
                throw ValidationException::withMessages([
                    'administrator' => 'El usuario seleccionado no puede ser destituido desde este apartado.',
                ]);
            }

            $lockedAdministrator->removeRole('Admin');
            $this->userAccessService->block($lockedAdministrator);
        });

        return redirect()
            ->route('admin.superadministrator.index')
            ->with('status', 'El administrador fue destituido y sus accesos quedaron bloqueados.');
    }

    public function appoint(Request $request, User $personnel): RedirectResponse
    {
        $this->ensureSuperAdministrator($request);

        DB::transaction(function () use ($personnel) {
            $lockedPersonnel = User::query()
                ->lockForUpdate()
                ->findOrFail($personnel->id);

            if ($lockedPersonnel->hasAnyRole(self::EXCLUDED_PERSONNEL_ROLES)) {
                throw ValidationException::withMessages([
                    'personnel' => 'El usuario seleccionado no es personal elegible para este nombramiento.',
                ]);
            }

            $lockedPersonnel->syncRoles(['Admin']);
            $lockedPersonnel->forceFill([
                'is_active' => true,
                'remember_token' => Str::random(60),
            ])->save();
        });

        return redirect()
            ->route('admin.superadministrator.index')
            ->with('status', 'El personal seleccionado ahora tiene acceso de administrador.');
    }

    public function approveWasteRequest(
        Request $request,
        WasteAuthorizationRequest $wasteRequest,
        WasteAuthorizationService $wasteAuthorizationService
    ): RedirectResponse {
        $this->ensureSuperAdministrator($request);
        $data = $request->validate([
            'review_notes' => 'nullable|string|max:500',
        ]);

        $wasteAuthorizationService->approve(
            $wasteRequest,
            (int) $request->user()->id,
            $data['review_notes'] ?? null
        );

        return redirect()
            ->route('admin.superadministrator.index', [
                'waste_open' => 1,
                'waste_view' => 'requests',
            ])
            ->with('status', 'La solicitud de merma fue autorizada y el inventario quedó actualizado.');
    }

    public function rejectWasteRequest(
        Request $request,
        WasteAuthorizationRequest $wasteRequest,
        WasteAuthorizationService $wasteAuthorizationService
    ): RedirectResponse {
        $this->ensureSuperAdministrator($request);
        $data = $request->validate([
            'review_notes' => 'required|string|max:500',
        ]);

        $wasteAuthorizationService->reject(
            $wasteRequest,
            (int) $request->user()->id,
            $data['review_notes']
        );

        return redirect()
            ->route('admin.superadministrator.index', [
                'waste_open' => 1,
                'waste_view' => 'requests',
            ])
            ->with('status', 'La solicitud de merma fue rechazada sin modificar el inventario.');
    }

    private function ensureSuperAdministrator(Request $request): void
    {
        abort_unless($request->user()?->hasRole('Super Admin'), 403);
    }

    private function wasteRecords(array $dateRange): Collection
    {
        $records = collect();

        if (Schema::hasTable('inspection_wastes')) {
            $records = $records->concat($this->applyWasteDateRange(InspectionWaste::query(), $dateRange)
                ->get()->map(fn (InspectionWaste $waste) => $this->mapInspectionWaste($waste)));
        }

        if (Schema::hasTable('medicine_remainder_movements') && Schema::hasTable('medicine_remainders')) {
            $remainderWasteQuery = MedicineRemainderMovement::query()
                ->with([
                    'remainder.oncologicPresentation.catalog',
                    'remainder.nutritionPresentation.catalog',
                    'remainder.oncologicBatch',
                    'remainder.nutritionStock',
                    'remainder.laboratory',
                    'remainder.warehouse',
                    'user',
                ])
                ->where('movement_type', 'descarte');

            $remainderWaste = $this->applyWasteDateRange($remainderWasteQuery, $dateRange)
                ->get()
                ->map(fn (MedicineRemainderMovement $movement): array => $this->mapRemainderWaste($movement));

            $records = $records->concat($remainderWaste);
        }

        if (Schema::hasTable('medicine_batch_movements')) {
            $oncologicContainerWasteQuery = MedicineBatchMovement::query()
                ->with([
                    'batch.presentation.catalog',
                    'batch.laboratory',
                    'batch.warehouse',
                    'laboratory',
                    'warehouse',
                    'user',
                ])
                ->where('movement_type', 'merma')
                ->where(function ($query) {
                    $query->whereNull('reference_type')
                        ->orWhere('reference_type', '!=', 'MermaRemanente');
                });

            $oncologicContainerWaste = $this->applyWasteDateRange($oncologicContainerWasteQuery, $dateRange)
                ->get()
                ->map(fn (MedicineBatchMovement $movement): array => $this->mapOncologicContainerWaste($movement));

            $records = $records->concat($oncologicContainerWaste);
        }

        if (Schema::hasTable('medicine_stock_movements')) {
            $nutritionContainerWasteQuery = MedicineStockMovement::query()
                ->with([
                    'stock.presentation.catalog',
                    'stock.laboratory',
                    'stock.warehouse',
                    'warehouse',
                    'user',
                ])
                ->where('tipo', 'merma');

            $nutritionContainerWaste = $this->applyWasteDateRange($nutritionContainerWasteQuery, $dateRange)
                ->get()
                ->map(fn (MedicineStockMovement $movement): array => $this->mapNutritionContainerWaste($movement));

            $records = $records->concat($nutritionContainerWaste);
        }

        return $records
            ->sortByDesc(fn (array $record): int => $record['occurred_at']?->getTimestamp() ?? 0)
            ->values();
    }

    private function wasteDateRange(Request $request): array
    {
        $fromBoundary = $this->wasteBoundary($request, 'waste_from');
        $toBoundary = $this->wasteBoundary($request, 'waste_to');

        if ($fromBoundary['value'] && $toBoundary['value'] && $fromBoundary['value'] > $toBoundary['value']) {
            [$fromBoundary, $toBoundary] = [$toBoundary, $fromBoundary];
        }

        $from = $fromBoundary['value']
            ? CarbonImmutable::createFromFormat('!Y-m', $fromBoundary['value'], config('app.timezone'))->startOfMonth()
            : null;
        $to = $toBoundary['value']
            ? CarbonImmutable::createFromFormat('!Y-m', $toBoundary['value'], config('app.timezone'))->endOfMonth()
            : null;
        $active = $from !== null || $to !== null;

        return [
            'from_month' => $fromBoundary['month'],
            'from_year' => $fromBoundary['year'],
            'to_month' => $toBoundary['month'],
            'to_year' => $toBoundary['year'],
            'from' => $from,
            'to' => $to,
            'active' => $active,
            'open' => $active || $request->boolean('waste_open', true),
        ];
    }

    private function mapInspectionWaste(InspectionWaste $waste): array
    {
        $snapshot = $waste->snapshot;
        $medications = collect($snapshot['medications'] ?? []);
        $presentations = $medications->flatMap(fn ($medication) => $medication['presentaciones_usadas'] ?? []);
        $materials = $presentations->map(fn ($presentation) =>
            ($presentation['presentacion_snapshot'] ?? 'Presentación').' · Lote '.($presentation['lote_usado'] ?? '-')
            .' · '.($presentation['volumen_usado_ml'] ?? 0).' mL');
        if (!empty($snapshot['diluent'])) {
            $materials->push('Diluyente: '.($snapshot['diluent']['presentacion'] ?? 'Presentación')
                .' · Lote '.($snapshot['diluent']['lote'] ?? '-'));
        }

        return [
            'id' => 'inspeccion-'.$waste->id,
            'type' => 'inspeccion',
            'type_label' => 'Merma de inspección',
            'area' => ($snapshot['category'] ?? '') === 'antibioticos' ? 'Antibióticos' : 'Oncológico',
            'product' => 'Mezcla #'.$waste->mezcla_id.' · Intento '.$waste->production_attempt,
            'presentation' => $medications->map(fn ($medication) =>
                (($medication['denominacion_snapshot'] ?? '') ?: ($medication['nombre_medicamento'] ?? 'Medicamento')))->implode('; '),
            'units' => [
                'mg' => $medications->isNotEmpty() && $medications->every(fn ($medication) => isset($medication['dosis']) && is_numeric($medication['dosis']))
                    ? (float) $medications->sum('dosis') : null,
                'mezclas' => 1.0,
            ],
            'brand' => $medications->pluck('marca_snapshot')->filter()->unique()->implode(', ') ?: '-',
            'lot' => $snapshot['mixture']['lote'] ?? 'Sin lote',
            'purchase_cost_per_ml' => null,
            'purchase_cost_per_bottle' => null,
            'laboratory' => $snapshot['laboratory'] ?? 'Sin central',
            'warehouse' => implode(', ', $snapshot['warehouses'] ?? []) ?: 'Sin almacén',
            'inspection_destination' => 'Solicitud #'.($snapshot['request_id'] ?? '-').' · '.($snapshot['hospital'] ?? 'Sin hospital'),
            'quantity_containers' => 0,
            'quantity_ml' => (float) ($snapshot['mixture']['volumen_dilucion'] ?? 0),
            'quantity_mixtures' => 1,
            'reason' => $waste->reason,
            'user' => $snapshot['reviewer'] ?? '-',
            'occurred_at' => $waste->created_at,
            'inspection_materials' => $materials->all(),
        ];
    }

    private function wasteBoundary(Request $request, string $prefix): array
    {
        $legacyValue = $this->normalizeMonth($request->query($prefix));
        $year = $this->normalizeYear($request->query($prefix.'_year'));
        $month = $this->normalizeMonthNumber($request->query($prefix.'_month'));

        if ($legacyValue) {
            $year ??= (int) substr($legacyValue, 0, 4);
            $month ??= substr($legacyValue, 5, 2);
        }

        return [
            'year' => $year,
            'month' => $month,
            'value' => $year !== null && $month !== null
                ? sprintf('%04d-%s', $year, $month)
                : null,
        ];
    }

    private function normalizeMonth(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return preg_match('/^[1-9]\d{3}-(0[1-9]|1[0-2])$/', $value) === 1
            ? $value
            : null;
    }

    private function normalizeMonthNumber(mixed $value): ?string
    {
        if (! is_scalar($value) || ! preg_match('/^\d{1,2}$/', (string) $value)) {
            return null;
        }

        $month = (int) $value;

        return $month >= 1 && $month <= 12
            ? str_pad((string) $month, 2, '0', STR_PAD_LEFT)
            : null;
    }

    private function normalizeYear(mixed $value): ?int
    {
        if (! is_scalar($value) || ! preg_match('/^[1-9]\d{3}$/', (string) $value)) {
            return null;
        }

        return (int) $value;
    }

    private function applyWasteDateRange(Builder $query, array $dateRange): Builder
    {
        if ($dateRange['from']) {
            $query->where('created_at', '>=', $dateRange['from']);
        }

        if ($dateRange['to']) {
            $query->where('created_at', '<=', $dateRange['to']);
        }

        return $query;
    }

    private function mapRemainderWaste(MedicineRemainderMovement $movement): array
    {
        $remainder = $movement->remainder;
        $isOncologic = $remainder?->domain === 'oncologico';
        $presentation = $isOncologic
            ? $remainder?->oncologicPresentation
            : $remainder?->nutritionPresentation;
        $catalog = $presentation?->catalog;
        $inventorySource = $isOncologic
            ? $remainder?->oncologicBatch
            : $remainder?->nutritionStock;
        $purchaseCosts = $this->purchaseCosts($inventorySource, $presentation, $isOncologic);

        return [
            'id' => 'remanente-'.$movement->id,
            'type' => 'remanente',
            'type_label' => 'Merma de remanente',
            'area' => $isOncologic ? 'Oncológico' : 'Nutricional',
            'product' => $isOncologic
                ? ($catalog?->denominacion ?: 'Producto sin nombre')
                : ($catalog?->denominacion_generica ?: 'Producto sin nombre'),
            'presentation' => $this->presentationLabel($presentation),
            'brand' => $this->brandLabel($presentation, $isOncologic),
            'lot' => $remainder?->lote ?: 'Sin lote',
            ...$purchaseCosts,
            'laboratory' => $remainder?->laboratory?->nombre ?: 'Sin central',
            'warehouse' => $remainder?->warehouse?->name ?: 'Sin almacén',
            'quantity_containers' => 0.0,
            'quantity_ml' => (float) ($movement->quantity_ml ?? 0),
            'units' => $isOncologic
                ? ['mg' => \App\Models\Oncologicos\MedicinePresentation::remainderInMilligramsFrom(
                    $movement->quantity_ml, $presentation?->contentInMilligrams(), $presentation?->volumen_diluyente
                )]
                : ['mL' => (float) ($movement->quantity_ml ?? 0)],
            'reason' => $remainder?->discard_reason ?: ($movement->notes ?: 'Sin motivo registrado'),
            'user' => $this->userLabel($movement->user),
            'occurred_at' => $movement->created_at,
        ];
    }

    private function mapOncologicContainerWaste(MedicineBatchMovement $movement): array
    {
        $batch = $movement->batch;
        $presentation = $batch?->presentation;
        $purchaseCosts = $this->purchaseCosts($batch, $presentation, true);

        return [
            'id' => 'frasco-oncologico-'.$movement->id,
            'type' => 'frasco',
            'type_label' => 'Merma de frasco',
            'area' => 'Oncológico',
            'product' => $presentation?->catalog?->denominacion ?: 'Producto sin nombre',
            'presentation' => $this->presentationLabel($presentation),
            'brand' => $this->brandLabel($presentation, true),
            'lot' => $batch?->lote ?: 'Sin lote',
            ...$purchaseCosts,
            'laboratory' => $movement->laboratory?->nombre ?: ($batch?->laboratory?->nombre ?: 'Sin central'),
            'warehouse' => $movement->warehouse?->name ?: ($batch?->warehouse?->name ?: 'Sin almacén'),
            'quantity_containers' => (float) ($movement->quantity ?? 0),
            'quantity_ml' => (float) ($movement->quantity_ml ?? 0),
            'units' => ['frascos' => (float) ($movement->quantity ?? 0), 'mL' => (float) ($movement->quantity_ml ?? 0)],
            'reason' => $movement->notes ?: 'Sin motivo registrado',
            'user' => $this->userLabel($movement->user),
            'occurred_at' => $movement->created_at,
        ];
    }

    private function mapNutritionContainerWaste(MedicineStockMovement $movement): array
    {
        $stock = $movement->stock;
        $presentation = $stock?->presentation;
        $purchaseCosts = $this->purchaseCosts($stock, $presentation, false);

        return [
            'id' => 'frasco-nutricional-'.$movement->id,
            'type' => 'frasco',
            'type_label' => 'Merma de frasco',
            'area' => 'Nutricional',
            'product' => $presentation?->catalog?->denominacion_generica ?: 'Producto sin nombre',
            'presentation' => $this->presentationLabel($presentation),
            'brand' => $this->brandLabel($presentation, false),
            'lot' => $stock?->lote ?: 'Sin lote',
            ...$purchaseCosts,
            'laboratory' => $stock?->laboratory?->nombre ?: 'Sin central',
            'warehouse' => $movement->warehouse?->name ?: ($stock?->warehouse?->name ?: 'Sin almacén'),
            'quantity_containers' => (float) ($movement->cantidad_frascos ?? 0),
            'quantity_ml' => (float) ($movement->cantidad_ml ?? 0),
            'units' => ['frascos' => (float) ($movement->cantidad_frascos ?? 0), 'mL' => (float) ($movement->cantidad_ml ?? 0)],
            'reason' => $movement->notes ?: 'Sin motivo registrado',
            'user' => $this->userLabel($movement->user),
            'occurred_at' => $movement->created_at,
        ];
    }

    private function presentationLabel(mixed $presentation): string
    {
        $label = trim((string) ($presentation?->presentacion ?? ''));

        return $label !== '' ? $label : 'Sin presentación';
    }

    private function brandLabel(mixed $presentation, bool $isOncologic): string
    {
        $brand = $isOncologic
            ? $presentation?->marca
            : $presentation?->denominacion_comercial;

        $label = trim((string) ($brand ?? ''));

        return $label !== '' ? $label : 'Sin marca';
    }

    private function purchaseCosts(mixed $inventorySource, mixed $presentation, bool $isOncologic): array
    {
        $rawCost = $inventorySource?->getAttribute('costo_unitario');
        $costPerBottle = $rawCost !== null ? (float) $rawCost : null;
        $volumePerBottle = (float) ($isOncologic
            ? ($presentation?->volumen_diluyente ?? 0)
            : ($presentation?->presentacion_ml ?? 0));

        return [
            'purchase_cost_per_ml' => $costPerBottle !== null && $volumePerBottle > 0
                ? $costPerBottle / $volumePerBottle
                : null,
            'purchase_cost_per_bottle' => $costPerBottle,
        ];
    }

    private function userLabel(mixed $user): string
    {
        if (! $user) {
            return 'Sistema';
        }

        $name = trim(($user->name ?? '').' '.($user->lastname ?? ''));

        return $name !== '' ? $name : ($user->username ?: 'Sistema');
    }
}
