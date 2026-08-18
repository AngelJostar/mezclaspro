<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Instituciones\InstitucionGeneralExport;
use App\Exports\Instituciones\InstitucionHospitalDetalleExport;
use App\Exports\Instituciones\InstitucionHospitalExport;
use App\Exports\Instituciones\InstitucionMezclasOncoExport;
use App\Exports\Instituciones\InstitutionDailyNutritionPatientHospitalExport;
use App\Exports\Instituciones\InstitutionMonthlyNutritionSupplyExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Services\InstitutionDailyNutritionPatientReportService;
use App\Services\InstitutionMonthlyNutritionSupplyReportService;
use App\Services\InstitutionReportTemplateService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use ZipArchive;

class InstitucionController extends Controller
{
    public function index()
    {
        $instituciones = Institucion::orderBy('id', 'desc')->paginate(10);

        return view('admin.instituciones.index', compact('instituciones'));
    }

    public function reportes(Request $request, InstitutionReportTemplateService $templates)
    {
        $dateRange = $request->validate([
            'daily_from' => ['nullable', 'required_with:daily_to', 'date_format:Y-m-d'],
            'daily_to' => ['nullable', 'required_with:daily_from', 'date_format:Y-m-d', 'after_or_equal:daily_from'],
        ]);
        $search = trim((string) $request->query('search', ''));
        $dailyReportFrom = (string) ($dateRange['daily_from'] ?? now()->startOfMonth()->toDateString());
        $dailyReportTo = (string) ($dateRange['daily_to'] ?? now()->endOfMonth()->toDateString());

        $instituciones = Institucion::with([
            'hospitals' => fn ($query) => $query
                ->select('hospitals.id', 'hospitals.name')
                ->orderBy('hospitals.name'),
        ])
            ->withCount('hospitals')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('nombre', 'like', '%'.$search.'%')
                        ->orWhere('razon_social', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $reportTemplates = $templates->all();

        return view('admin.instituciones.reportes', compact(
            'instituciones',
            'search',
            'reportTemplates',
            'dailyReportFrom',
            'dailyReportTo'
        ));
    }

    public function create()
    {
        return view('admin.instituciones.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'razon_social' => ['required', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
        ]);

        Institucion::create($data);

        session()->flash('swal', [
            'icon' => 'success',
            'title' => 'Institucion creada',
            'text' => 'La institucion se registro correctamente.',
        ]);

        return redirect()->route('admin.instituciones.create');
    }

    public function edit(Institucion $institucion)
    {
        return view('admin.instituciones.edit', compact('institucion'));
    }

    public function update(Request $request, Institucion $institucion)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'razon_social' => ['required', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
        ]);

        $institucion->update($data);

        session()->flash('swal', [
            'icon' => 'success',
            'title' => 'Institucion actualizada',
            'text' => 'Los datos de la institucion se actualizaron correctamente.',
        ]);

        return redirect()->route('admin.instituciones.edit', $institucion);
    }

    public function destroy(Institucion $institucion)
    {
        $institucion->delete();

        session()->flash('swal', [
            'icon' => 'success',
            'title' => 'Institucion eliminada',
            'text' => 'La institucion fue eliminada correctamente.',
        ]);

        return redirect()->route('admin.instituciones.index');
    }

    public function exportarMezclasOnco(Institucion $institucion)
    {
        $filename = 'reporte_mezclas_onco_institucion_'.$institucion->id.'.xlsx';

        return $this->downloadExcel(new InstitucionMezclasOncoExport($institucion->id), $filename);
    }

    public function exportarReporteGeneral(Institucion $institucion)
    {
        $filename = 'reporte_general_institucion_'.$institucion->id.'.xlsx';

        return $this->downloadExcel(new InstitucionGeneralExport($institucion->id), $filename);
    }

    public function exportarReporteHospital(Institucion $institucion)
    {
        $filename = 'reporte_por_hospital_institucion_'.$institucion->id.'.xlsx';

        return $this->downloadExcel(new InstitucionHospitalExport($institucion->id), $filename);
    }

    public function exportarReporteHospitalDetalle(Request $request, Institucion $institucion)
    {
        $hospitals = $this->selectedReportHospitals($request, $institucion);
        $filename = 'reporte_por_hospital_con_detalle_institucion_'.$institucion->id.'.xlsx';

        return $this->downloadExcel(
            new InstitucionHospitalDetalleExport($institucion->id, $hospitals->pluck('id')->all()),
            $filename
        );
    }

    public function exportarReporteDiarioPaciente(
        Request $request,
        Institucion $institucion,
        InstitutionDailyNutritionPatientReportService $report
    ) {
        $dateRange = $request->validate([
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $from = isset($dateRange['from'])
            ? Carbon::createFromFormat('Y-m-d', $dateRange['from'])->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $to = isset($dateRange['to'])
            ? Carbon::createFromFormat('Y-m-d', $dateRange['to'])->endOfDay()
            : now()->endOfMonth()->endOfDay();
        $hospitals = $this->selectedReportHospitals($request, $institucion);
        $token = (string) Str::uuid();
        $relativeDirectory = 'tmp/reportes-diarios-paciente/'.$token;
        $absoluteDirectory = storage_path('app/'.$relativeDirectory);
        $zipDirectory = storage_path('app/tmp/reportes-diarios-paciente');
        $zipPath = $zipDirectory.'/'.$token.'.zip';

        File::ensureDirectoryExists($absoluteDirectory);
        $zip = new ZipArchive();
        $zipOpened = false;

        try {
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No fue posible preparar el archivo de reportes.');
            }
            $zipOpened = true;

            if ($hospitals->isEmpty()) {
                $zip->addFromString(
                    'sin_hospitales.txt',
                    'Esta institucion no tiene hospitales vinculados.'
                );
            }

            foreach ($hospitals as $hospital) {
                $hospitalName = Str::slug((string) $hospital->name) ?: 'hospital';
                $fileName = sprintf(
                    '%03d_reporte_diario_%s_%s_%s.xlsx',
                    $hospital->id,
                    $hospitalName,
                    $from->format('Ymd'),
                    $to->format('Ymd')
                );
                $relativePath = $relativeDirectory.'/'.$fileName;
                $absolutePath = storage_path('app/'.$relativePath);

                $stored = Excel::store(
                    new InstitutionDailyNutritionPatientHospitalExport($hospital, $report, $from, $to),
                    $relativePath,
                    'local'
                );

                if (! $stored || ! File::exists($absolutePath) || ! $zip->addFile($absolutePath, $fileName)) {
                    throw new RuntimeException('No fue posible agregar el reporte del hospital '.$hospital->name.'.');
                }
            }

            $zip->close();
            $zipOpened = false;
        } catch (\Throwable $exception) {
            if ($zipOpened) {
                $zip->close();
            }

            File::deleteDirectory($absoluteDirectory);
            File::delete($zipPath);

            throw $exception;
        }

        File::deleteDirectory($absoluteDirectory);

        $downloadName = sprintf(
            'reportes_diarios_por_paciente_institucion_%d_%s_%s_%s.zip',
            $institucion->id,
            $from->format('Ymd'),
            $to->format('Ymd'),
            now()->format('Ymd_His')
        );

        try {
            $contents = File::get($zipPath);
        } finally {
            File::delete($zipPath);
        }

        return $this->downloadContents($contents, $downloadName, 'application/zip');
    }

    public function exportarReporteMensualInsumos(
        Request $request,
        Institucion $institucion,
        InstitutionMonthlyNutritionSupplyReportService $report
    ) {
        $month = now()->startOfMonth();
        $hospitals = $this->selectedReportHospitals($request, $institucion);
        $downloadName = sprintf(
            'reporte_mensual_insumos_por_hospital_institucion_%d_%s.xlsx',
            $institucion->id,
            $month->format('Y-m')
        );

        return $this->downloadExcel(
            new InstitutionMonthlyNutritionSupplyExport($institucion, $month, $report, $hospitals),
            $downloadName
        );
    }

    private function selectedReportHospitals(Request $request, Institucion $institucion)
    {
        $validated = $request->validate([
            'hospital_ids' => ['nullable', 'array'],
            'hospital_ids.*' => ['integer', 'distinct', 'exists:hospitals,id'],
        ]);
        $selectedIds = collect($validated['hospital_ids'] ?? [])
            ->map(fn ($hospitalId) => (int) $hospitalId)
            ->unique()
            ->values();
        $hasSelection = $request->has('hospital_ids');

        $hospitals = $institucion->hospitals()
            ->when($hasSelection, fn ($query) => $query->whereIn('hospitals.id', $selectedIds))
            ->orderBy('hospitals.name')
            ->get();

        abort_if(
            $hasSelection && $hospitals->count() !== $selectedIds->count(),
            422,
            'La seleccion contiene hospitales que no pertenecen a esta institucion.'
        );

        return $hospitals;
    }

    private function downloadExcel(object $export, string $downloadName): Response
    {
        $contents = Excel::raw($export, ExcelWriter::XLSX);

        return $this->downloadContents(
            $contents,
            $downloadName,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    private function downloadContents(string $contents, string $downloadName, string $contentType): Response
    {
        return response($contents, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => HeaderUtils::makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $downloadName
            ),
            'Content-Length' => (string) strlen($contents),
            'Content-Transfer-Encoding' => 'binary',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function hospitales(Request $request, Institucion $institucion)
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        $unitType = (string) $request->query('unit_type', 'all');
        $service = (string) $request->query('service', 'all');

        $hospitals = $institucion->hospitals()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('hospitals.name', 'like', '%'.$search.'%')
                        ->orWhere('hospitals.internal_key', 'like', '%'.$search.'%')
                        ->orWhere('hospitals.municipality', 'like', '%'.$search.'%');
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('hospitals.is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('hospitals.is_active', false))
            ->when($unitType !== 'all', fn ($query) => $query->where('hospitals.unit_type', $unitType))
            ->when($service === 'oncology', function ($query) {
                $query->where(function ($subquery) {
                    $subquery->where('hospitals.service_oncology', true)
                        ->orWhereNotNull('hospitals.onco_medicine_list_id');
                });
            })
            ->when($service === 'antibiotics', fn ($query) => $query->where('hospitals.service_antibiotics', true))
            ->when($service === 'nutrition', function ($query) {
                $query->where(function ($subquery) {
                    $subquery->where('hospitals.service_nutrition', true)
                        ->orWhereNotNull('hospitals.nutri_medicine_list_id');
                });
            })
            ->orderBy('hospitals.name')
            ->paginate(25)
            ->withQueryString();

        $totalHospitals = $institucion->hospitals()->count();
        $unitTypes = $institucion->hospitals()
            ->whereNotNull('hospitals.unit_type')
            ->where('hospitals.unit_type', '<>', '')
            ->distinct()
            ->orderBy('hospitals.unit_type')
            ->pluck('hospitals.unit_type');

        return view('admin.instituciones.hospitals', compact(
            'institucion',
            'hospitals',
            'totalHospitals',
            'unitTypes',
            'search',
            'status',
            'unitType',
            'service'
        ));
    }

    public function storeHospital(Request $request, Institucion $institucion)
    {
        $data = $request->validate([
            'name_hp' => ['required', 'string', 'max:255'],
            'adress' => ['required', 'string', 'max:400'],
            'laboratory_id' => ['nullable', 'integer', 'exists:laboratories,id'],
            'nutri_medicine_list_id' => ['nullable', 'integer', 'exists:nutri_medicine_lists,id'],
            'onco_medicine_list_id' => ['nullable', 'integer', 'exists:medicine_lists,id'],
        ]);

        $hospital = Hospital::create([
            'name' => $data['name_hp'],
            'adress' => $data['adress'],
            'laboratory_id' => $data['laboratory_id'] ?? null,
            'nutri_medicine_list_id' => $data['nutri_medicine_list_id'] ?? null,
            'onco_medicine_list_id' => $data['onco_medicine_list_id'] ?? null,
        ]);

        $institucion->hospitals()->syncWithoutDetaching([$hospital->id]);

        session()->flash('swal', [
            'icon' => 'success',
            'title' => 'Hospital creado',
            'text' => 'El hospital se creo y quedo vinculado a la institucion correctamente.',
        ]);

        return redirect()->route('admin.instituciones.hospitals', $institucion);
    }

    public function actualizarHospitales(Request $request, Institucion $institucion)
    {
        $data = $request->validate([
            'hospitals' => ['nullable', 'array'],
            'hospitals.*' => ['integer', 'exists:hospitals,id'],
        ]);

        $institucion->hospitals()->sync($data['hospitals'] ?? []);

        session()->flash('swal', [
            'icon' => 'success',
            'title' => 'Hospitales actualizados',
            'text' => 'Los hospitales vinculados a la institucion se actualizaron correctamente.',
        ]);

        return redirect()->route('admin.instituciones.hospitals', $institucion);
    }
}
