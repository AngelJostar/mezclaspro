<?php

namespace Tests\Unit;

use App\Exports\Instituciones\InstitutionMonthlyNutritionSupplyExport;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\Nutricionales\Input;
use App\Models\Nutricionales\Solicitud;
use App\Models\Nutricionales\SolicitudInput;
use App\Services\InstitutionMonthlyNutritionSupplyReportService;
use App\Services\InstitutionReportTemplateService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class InstitutionMonthlyNutritionSupplyReportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        $container->instance(InstitutionReportTemplateService::class, new class extends InstitutionReportTemplateService
        {
            public function projectRows(string $key, array $rows): array
            {
                return $rows;
            }
        });
        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_it_sums_monthly_volumes_and_counts_piece_concepts(): void
    {
        $service = new InstitutionMonthlyNutritionSupplyReportService();

        $first = $this->request(1, [
            $this->input('Aminoácidos pediátricos 10%', 1, 100.25, 110.50),
            $this->input('Set de Infusion', 10, 1, null, 1),
            $this->input('Bolsa EVA (1000 mL)', 6, 0, null, 1),
        ]);
        $second = $this->request(2, [
            $this->input('AMINOACIDOS PEDIATRICOS 10%', 1, 50.25),
            $this->input('Preparacion para NPT', 11, 1, null, 1),
        ]);

        $rows = $service->build(collect([$first, $second]));

        $this->assertSame([
            ['Aminoácidos pediátricos 10%', 'ML', 160.75],
            ['Servicio de mezclado de NPT', 'PZ', 2.0],
            ['Set de Infusion', 'PZ', 1.0],
        ], $rows);
    }

    public function test_it_creates_one_excel_sheet_per_hospital(): void
    {
        $report = new class extends InstitutionMonthlyNutritionSupplyReportService
        {
            public function requestsForHospital(int $hospitalId, CarbonInterface $month): Collection
            {
                return collect();
            }
        };
        $institution = new Institucion(['nombre' => 'Institucion de prueba']);
        $institution->setRelation('hospitals', collect([
            $this->hospital(2, 'Hospital Sur'),
            $this->hospital(1, 'Hospital: Norte/Uno'),
        ]));

        $export = new InstitutionMonthlyNutritionSupplyExport(
            $institution,
            CarbonImmutable::parse('2026-08-01'),
            $report
        );
        $sheets = $export->sheets();

        $this->assertCount(2, $sheets);
        $this->assertSame('H1 Hospital Norte Uno', $sheets[0]->title());
        $this->assertSame('H2 Hospital Sur', $sheets[1]->title());
    }

    public function test_it_creates_sheets_only_for_the_selected_hospitals(): void
    {
        $report = new class extends InstitutionMonthlyNutritionSupplyReportService
        {
            public function requestsForHospital(int $hospitalId, CarbonInterface $month): Collection
            {
                return collect();
            }
        };
        $institution = new Institucion(['nombre' => 'Institucion de prueba']);
        $north = $this->hospital(1, 'Hospital Norte');
        $south = $this->hospital(2, 'Hospital Sur');
        $institution->setRelation('hospitals', collect([$north, $south]));

        $export = new InstitutionMonthlyNutritionSupplyExport(
            $institution,
            CarbonImmutable::parse('2026-08-01'),
            $report,
            collect([$south])
        );
        $sheets = $export->sheets();

        $this->assertCount(1, $sheets);
        $this->assertSame('H2 Hospital Sur', $sheets[0]->title());
    }

    private function request(int $id, array $inputs): Solicitud
    {
        $request = new Solicitud(['estado' => 'entregada']);
        $request->id = $id;
        $request->setRelation('input', collect($inputs));

        return $request;
    }

    private function input(
        string $description,
        int $categoryId,
        float $milliliters,
        ?float $overfill = null,
        float $value = 0
    ): SolicitudInput {
        $input = new SolicitudInput([
            'valor' => $value,
            'valor_ml' => $milliliters,
            'valor_sobrellenado' => $overfill,
        ]);
        $input->setRelation('input', new Input([
            'description' => $description,
            'category_id' => $categoryId,
        ]));

        return $input;
    }

    private function hospital(int $id, string $name): Hospital
    {
        $hospital = new Hospital(['name' => $name]);
        $hospital->id = $id;

        return $hospital;
    }
}
