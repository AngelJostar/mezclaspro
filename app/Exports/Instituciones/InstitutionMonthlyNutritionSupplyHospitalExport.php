<?php

namespace App\Exports\Instituciones;

use App\Models\Hospital;
use App\Services\InstitutionMonthlyNutritionSupplyReportService;
use App\Services\InstitutionReportTemplateService;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstitutionMonthlyNutritionSupplyHospitalExport implements FromArray, WithHeadings, WithStyles, WithColumnFormatting, WithTitle
{
    private array $rows;

    private string $sheetTitle;

    private CarbonInterface $month;

    private array $context;

    public function __construct(
        Hospital $hospital,
        CarbonInterface $month,
        InstitutionMonthlyNutritionSupplyReportService $report
    ) {
        $templates = app(InstitutionReportTemplateService::class);
        $this->context = [
            'hospital' => $hospital->name,
            'periodo' => $month->translatedFormat('F Y'),
        ];
        $this->rows = $templates->projectRows(
            InstitutionReportTemplateService::MONTHLY_SUPPLIES,
            $report->build($report->requestsForHospital($hospital->id, $month))
        );
        $this->month = $month;
        $hospitalName = preg_replace('/\s+/', ' ', (string) preg_replace(
            '/[\\[\\]:*?\\/\\\\]/',
            ' ',
            Str::ascii((string) $hospital->name)
        ));
        $this->sheetTitle = mb_substr(trim('H' . $hospital->id . ' ' . $hospitalName), 0, 31);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return app(InstitutionReportTemplateService::class)->headingRows(
            InstitutionReportTemplateService::MONTHLY_SUPPLIES,
            $this->context
        );
    }

    public function title(): string
    {
        return $this->sheetTitle ?: $this->month->format('Y-m');
    }

    public function columnFormats(): array
    {
        $quantityColumn = app(InstitutionReportTemplateService::class)->columnLetter(
            InstitutionReportTemplateService::MONTHLY_SUPPLIES,
            'cantidad'
        );

        return $quantityColumn ? [$quantityColumn => '#,##0.00'] : [];
    }

    public function styles(Worksheet $sheet): array
    {
        $templates = app(InstitutionReportTemplateService::class);
        $headerRow = $templates->styleWorksheet(
            $sheet,
            InstitutionReportTemplateService::MONTHLY_SUPPLIES,
            $this->context,
            '17365D',
            'FFFFFF'
        );
        $highestRow = max($headerRow + 1, $sheet->getHighestRow());
        $highestColumn = $sheet->getHighestColumn();
        $dataStart = $headerRow + 1;

        $sheet->getStyle("A{$headerRow}:{$highestColumn}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '64748B'],
                ],
            ],
            'alignment' => [
                'vertical' => 'center',
            ],
        ]);
        if ($descriptionColumn = $templates->columnLetter(InstitutionReportTemplateService::MONTHLY_SUPPLIES, 'descripcion')) {
            $sheet->getStyle("{$descriptionColumn}{$dataStart}:{$descriptionColumn}{$highestRow}")->getAlignment()->setWrapText(true);
            $sheet->getColumnDimension($descriptionColumn)->setWidth(58);
        }
        foreach (['unidad' => 16, 'cantidad' => 20] as $key => $width) {
            if ($column = $templates->columnLetter(InstitutionReportTemplateService::MONTHLY_SUPPLIES, $key)) {
                $sheet->getStyle("{$column}{$dataStart}:{$column}{$highestRow}")->getAlignment()->setHorizontal('center');
                $sheet->getColumnDimension($column)->setWidth($width);
            }
        }

        return [];
    }
}
