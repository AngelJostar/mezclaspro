<?php

namespace Tests\Unit;

use App\Exports\Instituciones\InstitutionMonthlyNutritionSupplyExport;
use App\Exports\Instituciones\InstitutionMonthlyNutritionSupplyHospitalExport;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\Nutricionales\Input;
use App\Models\Nutricionales\NutriDistributor;
use App\Models\Nutricionales\NutriMedicineList;
use App\Models\Nutricionales\Solicitud;
use App\Models\Nutricionales\SolicitudInput;
use App\Services\InstitutionMonthlyNutritionSupplyReportService;
use App\Services\InstitutionReportTemplateService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PHPUnit\Framework\TestCase;

class InstitutionMonthlyNutritionSupplyReportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        $container->instance('config', new Repository([
            'prodifem' => [
                'legal_name' => 'Prodifem S.A. de C.V.',
                'fiscal_address' => 'Direccion fiscal Prodifem',
                'rfc' => 'PRO170214P96',
                'phone' => '5591862620',
                'email' => 'contacto@prodifem.com.mx',
                'logo' => '',
            ],
        ]));
        $container->instance(InstitutionReportTemplateService::class, new class extends InstitutionReportTemplateService
        {
            public function get(string $key): array
            {
                return [
                    'title' => 'Reporte mensual de insumos por hospital',
                    'subtitle' => 'Consumo mensual consolidado de insumos de nutricion parenteral.',
                    'columns' => [
                        ['key' => 'descripcion', 'label' => 'DESCRIPCION', 'visible' => true],
                        ['key' => 'unidad', 'label' => 'UNIDAD', 'visible' => true],
                        ['key' => 'cantidad', 'label' => 'CANTIDAD', 'visible' => true],
                    ],
                ];
            }

            public function visibleColumns(string $key): array
            {
                return $this->get($key)['columns'];
            }

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

    public function test_monthly_hospital_export_includes_legal_hospital_contract_and_signature_blocks(): void
    {
        $report = new class extends InstitutionMonthlyNutritionSupplyReportService
        {
            public function requestsForHospital(int $hospitalId, CarbonInterface $month): Collection
            {
                return collect();
            }

            public function build(Collection $requests): array
            {
                return [['Aminoacidos pediatricos 10%', 'ML', 438.37]];
            }
        };

        $hospital = new Hospital();
        $hospital->forceFill([
            'name' => 'Hospital General Atlacomulco',
            'clues' => 'MCIMB009063',
            'adress' => 'Boulevard Jorge Jimenez Cantu, Colonia Mercedes, C.P. 50454',
        ]);
        $hospital->id = 5;

        $list = new NutriMedicineList();
        $list->forceFill([
            'has_contract' => true,
            'contract_number' => 'ISEM-CMI-CS-RF-085-2025',
        ]);

        $distributor = new NutriDistributor();
        $distributor->forceFill([
            'nombre' => 'Subdistribuidor Demo S.A. de C.V.',
            'rfc' => 'SDD010101AA1',
            'direccion' => 'Direccion fiscal subdistribuidor',
            'contacto' => 'Laura Mendoza | 55 4890 2176 | laura@subdemo.mx',
            'informacion_adicional' => '',
            'logo_path' => null,
        ]);
        $list->setRelation('distributor', $distributor);
        $hospital->setRelation('nutriMedicineList', $list);

        $export = new InstitutionMonthlyNutritionSupplyHospitalExport(
            $hospital,
            CarbonImmutable::parse('2026-08-01'),
            $report
        );

        $content = collect($export->array())->flatten()->implode(' | ');

        $this->assertStringContainsString('Prodifem S.A. de C.V.', $content);
        $this->assertStringContainsString('Direccion fiscal Prodifem', $content);
        $this->assertStringContainsString('Subdistribuidor Demo S.A. de C.V.', $content);
        $this->assertStringContainsString('Hospital General Atlacomulco', $content);
        $this->assertStringContainsString('agosto 2026', $content);
        $this->assertStringContainsString('MCIMB009063', $content);
        $this->assertStringContainsString('ISEM-CMI-CS-RF-085-2025', $content);
        $this->assertStringContainsString('Boulevard Jorge Jimenez Cantu', $content);
        $this->assertStringContainsString('Aminoacidos pediatricos 10%', $content);
        $this->assertStringContainsString('Por medio de la presente', $content);
        $this->assertStringContainsString('Nombre, Cargo, Firma, Sello y Fecha', $content);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($export->array(), null, 'A1');
        $export->styles($sheet);

        $mergedCells = array_values($sheet->getMergeCells());

        $this->assertContains('A6:F6', $mergedCells);
        $this->assertContains('A21:D21', $mergedCells);
        $this->assertSame(PageSetup::ORIENTATION_PORTRAIT, $sheet->getPageSetup()->getOrientation());
        $this->assertSame('A1:F26', $sheet->getPageSetup()->getPrintArea());
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
