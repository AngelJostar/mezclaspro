<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceService;
use App\Models\MaintenanceServiceQuote;
use App\Models\Oncologicos\Laboratory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class MaintenanceQualificationController extends Controller
{
    private const MONTHS = [
        ['key' => 'ene', 'label' => 'ENE', 'name' => 'Enero', 'column' => 9],
        ['key' => 'feb', 'label' => 'FEB', 'name' => 'Febrero', 'column' => 10],
        ['key' => 'mar', 'label' => 'MAR', 'name' => 'Marzo', 'column' => 11],
        ['key' => 'abr', 'label' => 'ABR', 'name' => 'Abril', 'column' => 12],
        ['key' => 'may', 'label' => 'MAY', 'name' => 'Mayo', 'column' => 13],
        ['key' => 'jun', 'label' => 'JUN', 'name' => 'Junio', 'column' => 14],
        ['key' => 'jul', 'label' => 'JUL', 'name' => 'Julio', 'column' => 15],
        ['key' => 'ago', 'label' => 'AGO', 'name' => 'Agosto', 'column' => 16],
        ['key' => 'sep', 'label' => 'SEP', 'name' => 'Septiembre', 'column' => 17],
        ['key' => 'oct', 'label' => 'OCT', 'name' => 'Octubre', 'column' => 18],
        ['key' => 'nov', 'label' => 'NOV', 'name' => 'Noviembre', 'column' => 19],
        ['key' => 'dic', 'label' => 'DIC', 'name' => 'Diciembre', 'column' => 20],
    ];

    public function index(Request $request)
    {
        $laboratories = Laboratory::query()
            ->select(['id', 'nombre', 'estado', 'direccion', 'activo'])
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $requestedLaboratoryId = $request->integer('laboratory_id');
        $selectedLaboratory = $requestedLaboratoryId > 0
            ? $laboratories->firstWhere('id', $requestedLaboratoryId)
            : null;

        $calendar = $this->loadCalendar();
        $activeFilter = $request->query('only') === 'scheduled' ? 'scheduled' : 'all';

        if ($activeFilter === 'scheduled') {
            $calendar['rows'] = $calendar['scheduled_rows'];
        }

        return view('admin.maintenance-qualifications.index', [
            'laboratories' => $laboratories,
            'selectedLaboratory' => $selectedLaboratory,
            'calendar' => $calendar,
            'activeFilter' => $activeFilter,
        ]);
    }

    public function source()
    {
        $path = storage_path('app/mantenimiento/catalogo-servicios.xlsx');

        abort_unless(is_file($path), 404);

        return response()->download($path, 'Catalogo de Servicios.xlsx');
    }

    public function catalog(Request $request)
    {
        $laboratories = $this->activeLaboratories();
        $selectedLaboratory = $this->selectedLaboratory($request, $laboratories);
        $calendar = $this->loadCalendar();

        if ($calendar['error'] === null) {
            $this->seedCatalogFromCalendar($calendar);
        }

        $services = MaintenanceService::query()
            ->with('laboratory:id,nombre')
            ->where('is_active', true)
            ->when($selectedLaboratory, fn ($query) => $query
                ->where(fn ($nested) => $nested
                    ->whereNull('laboratory_id')
                    ->orWhere('laboratory_id', $selectedLaboratory->id)))
            ->orderBy('id')
            ->get();

        return view('admin.maintenance-qualifications.catalog', [
            'laboratories' => $laboratories,
            'selectedLaboratory' => $selectedLaboratory,
            'services' => $services,
            'calendar' => $calendar,
        ]);
    }

    public function priceList(Request $request)
    {
        $laboratories = $this->activeLaboratories();
        $selectedLaboratory = $this->selectedLaboratory($request, $laboratories);
        $calendar = $this->loadCalendar();

        if ($calendar['error'] === null) {
            $this->seedCatalogFromCalendar($calendar);
        }

        $services = MaintenanceService::query()
            ->with([
                'laboratory:id,nombre',
                'quotes',
            ])
            ->where('is_active', true)
            ->when($selectedLaboratory, fn ($query) => $query
                ->where(fn ($nested) => $nested
                    ->whereNull('laboratory_id')
                    ->orWhere('laboratory_id', $selectedLaboratory->id)))
            ->orderBy('service')
            ->get();

        return view('admin.maintenance-qualifications.price-list', [
            'laboratories' => $laboratories,
            'selectedLaboratory' => $selectedLaboratory,
            'services' => $services,
            'calendar' => $calendar,
        ]);
    }

    public function store(Request $request)
    {
        MaintenanceService::create($this->validatedService($request));

        return redirect()
            ->route('admin.maintenance-qualifications.catalog', $this->catalogRedirectQuery($request))
            ->with('swal', [
                'icon' => 'success',
                'title' => 'Servicio agregado',
                'text' => 'El servicio se agrego al catalogo correctamente.',
            ]);
    }

    public function update(Request $request, MaintenanceService $maintenanceService)
    {
        $maintenanceService->update($this->validatedService($request));

        return redirect()
            ->route('admin.maintenance-qualifications.catalog', $this->catalogRedirectQuery($request))
            ->with('swal', [
                'icon' => 'success',
                'title' => 'Servicio actualizado',
                'text' => 'El servicio se actualizo correctamente.',
            ]);
    }

    public function destroy(Request $request, MaintenanceService $maintenanceService)
    {
        if ($maintenanceService->source_key) {
            $maintenanceService->update(['is_active' => false]);
        } else {
            $maintenanceService->delete();
        }

        return redirect()
            ->route('admin.maintenance-qualifications.catalog', $this->catalogRedirectQuery($request))
            ->with('swal', [
                'icon' => 'success',
                'title' => 'Servicio eliminado',
                'text' => 'El servicio se elimino del catalogo.',
            ]);
    }

    public function storeQuote(Request $request, MaintenanceService $maintenanceService)
    {
        $maintenanceService->quotes()->create($this->validatedQuote($request));

        return redirect()
            ->route('admin.maintenance-qualifications.price-list', $this->priceListRedirectQuery($request))
            ->with('swal', [
                'icon' => 'success',
                'title' => 'Cotizacion agregada',
                'text' => 'La cotizacion se agrego correctamente.',
            ]);
    }

    public function updateQuote(Request $request, MaintenanceServiceQuote $maintenanceServiceQuote)
    {
        $maintenanceServiceQuote->update($this->validatedQuote($request));

        return redirect()
            ->route('admin.maintenance-qualifications.price-list', $this->priceListRedirectQuery($request))
            ->with('swal', [
                'icon' => 'success',
                'title' => 'Cotizacion actualizada',
                'text' => 'La cotizacion se actualizo correctamente.',
            ]);
    }

    public function destroyQuote(Request $request, MaintenanceServiceQuote $maintenanceServiceQuote)
    {
        $maintenanceServiceQuote->delete();

        return redirect()
            ->route('admin.maintenance-qualifications.price-list', $this->priceListRedirectQuery($request))
            ->with('swal', [
                'icon' => 'success',
                'title' => 'Cotizacion eliminada',
                'text' => 'La cotizacion se elimino correctamente.',
            ]);
    }

    private function loadCalendar(): array
    {
        $path = storage_path('app/mantenimiento/catalogo-servicios.xlsx');

        if (! is_file($path)) {
            return $this->emptyCalendar($path, 'No se encontro el archivo Catalogo de Servicios.xlsx en storage/app/mantenimiento.');
        }

        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getSheetByName('Calendario de Servicios 2026')
                ?? $spreadsheet->getSheetByName('Calendario de Servicios')
                ?? $spreadsheet->getActiveSheet();

            $calendar = $this->parseCalendarSheet($sheet, $path);
            $spreadsheet->disconnectWorksheets();

            return $calendar;
        } catch (Throwable $exception) {
            return $this->emptyCalendar($path, 'No se pudo leer el catalogo de servicios: '.$exception->getMessage());
        }
    }

    private function parseCalendarSheet(Worksheet $sheet, string $path): array
    {
        $rows = [];
        $monthlySummary = [];
        $providerCounts = [];
        $frequencyCounts = [];

        foreach (self::MONTHS as $month) {
            $monthlySummary[$month['key']] = [
                'key' => $month['key'],
                'label' => $month['label'],
                'name' => $month['name'],
                'count' => 0,
                'total' => 0.0,
            ];
        }

        for ($row = 7; $row <= $sheet->getHighestRow(); $row++) {
            $id = $this->number($this->cell($sheet, 1, $row));
            $service = $this->text($this->cell($sheet, 3, $row));

            if ($id === null || $service === '') {
                continue;
            }

            $frequency = $this->text($this->cell($sheet, 2, $row)) ?: 'Sin frecuencia';
            $provider = $this->text($this->cell($sheet, 6, $row)) ?: 'Sin proveedor';
            $monthValues = [];
            $monthlyTotal = 0.0;

            foreach (self::MONTHS as $month) {
                $amount = $this->number($this->cell($sheet, $month['column'], $row));
                $amount = $amount !== null && abs($amount) > 0.00001 ? round($amount, 2) : null;
                $monthValues[$month['key']] = $amount;

                if ($amount !== null) {
                    $monthlySummary[$month['key']]['count']++;
                    $monthlySummary[$month['key']]['total'] += $amount;
                    $monthlyTotal += $amount;
                }
            }

            $oneTime = $this->number($this->cell($sheet, 8, $row));
            $oneTime = $oneTime !== null && abs($oneTime) > 0.00001 ? round($oneTime, 2) : null;
            $scheduledTotal = round($monthlyTotal + (float) ($oneTime ?? 0), 2);

            $providerCounts[$provider] = ($providerCounts[$provider] ?? 0) + 1;
            $frequencyCounts[$frequency] = ($frequencyCounts[$frequency] ?? 0) + 1;

            $rows[] = [
                'id' => (int) $id,
                'source_key' => 'excel-'.$row.'-'.md5($id.'|'.$service.'|'.$provider),
                'frequency' => $frequency,
                'service' => $service,
                'quantity' => $this->number($this->cell($sheet, 4, $row)),
                'identification' => $this->text($this->cell($sheet, 5, $row)),
                'provider' => $provider,
                'unit_price' => $this->number($this->cell($sheet, 7, $row)),
                'one_time' => $oneTime,
                'months' => $monthValues,
                'total' => $scheduledTotal,
                'has_schedule' => $scheduledTotal > 0,
            ];
        }

        arsort($providerCounts);
        arsort($frequencyCounts);

        $year = (int) ($this->number($this->cell($sheet, 9, 5)) ?? 2026);
        $scheduledRows = array_values(array_filter($rows, fn (array $row): bool => $row['has_schedule']));
        $yearTotal = array_sum(array_column($rows, 'total'));
        $currentMonthKey = self::MONTHS[max(0, min(11, now()->month - 1))]['key'];

        return [
            'title' => $this->text($this->cell($sheet, 1, 1)) ?: 'Plan completo de servicios',
            'year' => $year,
            'months' => self::MONTHS,
            'rows' => $rows,
            'scheduled_rows' => $scheduledRows,
            'monthly_summary' => array_values($monthlySummary),
            'provider_summary' => $this->summaryRows($providerCounts),
            'frequency_summary' => $this->summaryRows($frequencyCounts),
            'service_count' => count($rows),
            'scheduled_count' => count($scheduledRows),
            'provider_count' => count($providerCounts),
            'year_total' => round($yearTotal, 2),
            'current_month' => $monthlySummary[$currentMonthKey],
            'source_file' => basename($path),
            'source_updated_at' => date('d/m/Y H:i', (int) filemtime($path)),
            'error' => null,
        ];
    }

    private function emptyCalendar(string $path, string $error): array
    {
        return [
            'title' => 'Plan completo de servicios',
            'year' => 2026,
            'months' => self::MONTHS,
            'rows' => [],
            'scheduled_rows' => [],
            'monthly_summary' => array_map(fn (array $month): array => [
                'key' => $month['key'],
                'label' => $month['label'],
                'name' => $month['name'],
                'count' => 0,
                'total' => 0.0,
            ], self::MONTHS),
            'provider_summary' => [],
            'frequency_summary' => [],
            'service_count' => 0,
            'scheduled_count' => 0,
            'provider_count' => 0,
            'year_total' => 0.0,
            'current_month' => ['label' => now()->format('M'), 'count' => 0, 'total' => 0.0],
            'source_file' => basename($path),
            'source_updated_at' => null,
            'error' => $error,
        ];
    }

    private function cell(Worksheet $sheet, int $column, int $row): mixed
    {
        return $sheet->getCell(Coordinate::stringFromColumnIndex($column).$row)->getCalculatedValue();
    }

    private function number(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $clean = str_replace(['$', ',', ' '], '', trim($value));

            return is_numeric($clean) ? (float) $clean : null;
        }

        return null;
    }

    private function text(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<int, array{label: string, count: int}>
     */
    private function summaryRows(array $counts): array
    {
        return array_map(
            fn (string $label, int $count): array => ['label' => $label, 'count' => $count],
            array_keys($counts),
            array_values($counts)
        );
    }

    private function activeLaboratories()
    {
        return Laboratory::query()
            ->select(['id', 'nombre', 'estado', 'direccion', 'activo'])
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    private function selectedLaboratory(Request $request, $laboratories): ?Laboratory
    {
        $requestedLaboratoryId = $request->integer('laboratory_id');

        return $requestedLaboratoryId > 0
            ? $laboratories->firstWhere('id', $requestedLaboratoryId)
            : null;
    }

    private function validatedService(Request $request): array
    {
        return $request->validate([
            'laboratory_id' => ['nullable', 'integer', Rule::exists('laboratories', 'id')->where('activo', true)],
            'service' => ['required', 'string', 'max:255'],
            'qualification_stages' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:120'],
            'areas' => ['nullable', 'string', 'max:255'],
            'frequency' => ['nullable', 'string', 'max:120'],
            'identification' => ['nullable', 'string', 'max:1000'],
            'providers' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function catalogRedirectQuery(Request $request): array
    {
        return array_filter([
            'laboratory_id' => $request->integer('current_laboratory_id') ?: null,
        ]);
    }

    private function validatedQuote(Request $request): array
    {
        return $request->validate([
            'supplier_name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
        ]);
    }

    private function priceListRedirectQuery(Request $request): array
    {
        return array_filter([
            'laboratory_id' => $request->integer('current_laboratory_id') ?: null,
        ]);
    }

    private function seedCatalogFromCalendar(array $calendar): void
    {
        foreach ($calendar['rows'] as $row) {
            $service = MaintenanceService::query()->firstOrCreate(
                ['source_key' => $row['source_key']],
                [
                    'service' => $row['service'],
                    'qualification_stages' => $this->inferQualificationStages($row),
                    'type' => $this->inferType($row['service']),
                    'areas' => $this->inferAreas($row),
                    'frequency' => $row['frequency'],
                    'identification' => $row['identification'],
                    'providers' => $row['provider'],
                    'is_active' => true,
                ]
            );

            $price = $row['unit_price'] ?? null;
            $provider = $row['provider'] ?? null;

            if ($provider && $provider !== 'Sin proveedor' && $price !== null && (float) $price > 0) {
                $service->quotes()->firstOrCreate([
                    'supplier_name' => $provider,
                    'price' => number_format((float) $price, 2, '.', ''),
                ]);
            }
        }
    }

    private function inferQualificationStages(array $row): string
    {
        $type = $this->inferType($row['service']);

        return match ($type) {
            'Calificacion' => 'IQ / OQ / PQ',
            'Certificacion' => 'Previa / ejecucion / dictamen',
            'Calibracion' => 'Calibracion / certificado',
            'Mantenimiento' => 'Preventivo / verificacion',
            'Capacitacion' => 'Programacion / evidencia',
            default => 'Revision documental',
        };
    }

    private function inferType(string $service): string
    {
        $service = mb_strtolower($service);

        return match (true) {
            str_contains($service, 'calific') => 'Calificacion',
            str_contains($service, 'certific') => 'Certificacion',
            str_contains($service, 'calibr') => 'Calibracion',
            str_contains($service, 'mantenimiento') => 'Mantenimiento',
            str_contains($service, 'capacit') => 'Capacitacion',
            default => 'Servicio',
        };
    }

    private function inferAreas(array $row): string
    {
        $text = mb_strtolower(($row['service'] ?? '').' '.($row['identification'] ?? ''));
        $areas = [];

        if (str_contains($text, 'onc') || str_contains($text, 'onco')) {
            $areas[] = 'Oncologicos';
        }

        if (str_contains($text, 'npt') || str_contains($text, 'nutric')) {
            $areas[] = 'Nutricion Parenteral';
        }

        return $areas === [] ? 'Central de mezclas' : implode(' / ', array_unique($areas));
    }
}
