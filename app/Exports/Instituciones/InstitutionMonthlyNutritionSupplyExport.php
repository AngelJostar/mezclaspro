<?php

namespace App\Exports\Instituciones;

use App\Models\Hospital;
use App\Models\Institucion;
use App\Services\InstitutionMonthlyNutritionSupplyReportService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InstitutionMonthlyNutritionSupplyExport implements WithMultipleSheets
{
    private array $sheets;

    public function __construct(
        Institucion $institucion,
        CarbonInterface $month,
        InstitutionMonthlyNutritionSupplyReportService $report,
        ?Collection $selectedHospitals = null
    ) {
        $hospitals = $selectedHospitals
            ?? ($institucion->relationLoaded('hospitals')
                ? $institucion->hospitals
                : $institucion->hospitals()->get());
        $hospitals = $hospitals
            ->sortBy(fn (Hospital $hospital) => Str::lower((string) preg_replace(
                '/[^a-z0-9]+/i',
                ' ',
                Str::ascii((string) $hospital->name)
            )))
            ->values();

        if ($hospitals->isEmpty()) {
            $hospital = new Hospital(['name' => 'Sin hospitales']);
            $hospital->id = 0;
            $hospitals = collect([$hospital]);
        }

        $this->sheets = $hospitals
            ->map(fn (Hospital $hospital) => new InstitutionMonthlyNutritionSupplyHospitalExport(
                $hospital,
                $month,
                $report
            ))
            ->all();
    }

    public function sheets(): array
    {
        return $this->sheets;
    }
}
