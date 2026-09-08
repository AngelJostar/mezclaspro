<?php

namespace Tests\Unit;

use App\Models\Institucion;
use App\Models\InstitutionReportTemplate;
use App\Models\User;
use App\Services\InstitutionCustomReportDataService;
use App\Services\InstitutionCustomReportExportService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;

class InstitutionCustomReportExportServiceTest extends TestCase
{
    public function test_it_repeats_parameters_and_orders_records_by_the_first_column(): void
    {
        $records = collect([
            ['request.patient' => 'ZULU', 'request.id' => '2', '__sort.sequence' => 0],
            ['request.patient' => 'ANA', 'request.id' => '3', '__sort.sequence' => 1],
            ['request.patient' => 'ANA', 'request.id' => '1', '__sort.sequence' => 2],
        ]);
        $data = new class($records) extends InstitutionCustomReportDataService
        {
            public function __construct(private Collection $rows)
            {
            }

            public function records(
                InstitutionReportTemplate $template,
                Institucion $institution,
                ?User $user,
                CarbonInterface $periodFrom,
                CarbonInterface $periodTo,
                array $repeatedParameterKeys
            ): Collection {
                return $this->rows;
            }

            public function baseContext(
                Institucion $institution,
                ?User $user,
                CarbonInterface $periodFrom,
                CarbonInterface $periodTo
            ): array {
                return ['system.user_name' => 'Pruebas'];
            }
        };
        $template = new InstitutionReportTemplate;
        $template->forceFill([
            'name' => 'Continuación de parámetros',
            'title' => 'Continuación de parámetros',
            'subtitle' => '',
            'data_source' => 'solicitudes',
            'is_custom' => true,
            'is_published' => true,
            'layout' => [
                'rows' => 5,
                'columns' => 3,
                'cells' => [
                    $this->parameterCell(0, 0, 'request.patient'),
                    $this->parameterCell(1, 0, 'request.id'),
                    $this->parameterCell(0, 1, 'request.patient', 'vertical'),
                    $this->parameterCell(0, 2, 'request.id', 'vertical'),
                    $this->parameterCell(4, 0, 'request.patient', 'horizontal'),
                ],
            ],
        ]);

        $contents = (new InstitutionCustomReportExportService($data))->make(
            $template,
            new Institucion,
            null,
            CarbonImmutable::parse('2026-08-01')->startOfDay(),
            CarbonImmutable::parse('2026-08-31')->endOfDay()
        );
        $path = tempnam(sys_get_temp_dir(), 'custom-report-');
        file_put_contents($path, $contents);

        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();

            $this->assertSame(['ANA', 'ANA', 'ZULU'], [
                $sheet->getCell('B1')->getValue(),
                $sheet->getCell('B2')->getValue(),
                $sheet->getCell('B3')->getValue(),
            ]);
            $this->assertSame(['1', '3', '2'], [
                $sheet->getCell('C1')->getValue(),
                $sheet->getCell('C2')->getValue(),
                $sheet->getCell('C3')->getValue(),
            ]);
            $this->assertSame(['ANA', 'ANA', 'ZULU'], [
                $sheet->getCell('A5')->getValue(),
                $sheet->getCell('B5')->getValue(),
                $sheet->getCell('C5')->getValue(),
            ]);

            $spreadsheet->disconnectWorksheets();
        } finally {
            @unlink($path);
        }
    }

    private function parameterCell(int $row, int $column, string $parameter, string $repeat = 'none'): array
    {
        return [
            'row' => $row,
            'column' => $column,
            'type' => 'parameter',
            'value' => '',
            'parameter' => $parameter,
            'repeat_direction' => $repeat,
            'style' => [
                'background' => '#FFFFFF',
                'color' => '#1F2937',
                'font_family' => 'Arial',
                'font_size' => 11,
                'bold' => false,
                'italic' => false,
                'underline' => false,
                'align' => 'left',
            ],
        ];
    }
}
