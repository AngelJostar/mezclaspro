<?php

namespace App\Exports\Instituciones;

use App\Models\Hospital;
use App\Services\InstitutionDailyNutritionPatientReportService;
use App\Services\InstitutionReportTemplateService;
use Carbon\CarbonInterface;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstitutionDailyNutritionPatientHospitalExport implements FromArray, WithHeadings, WithStyles, WithColumnFormatting
{
    private array $rows;

    private array $summaryRows;

    private array $context;

    public function __construct(
        Hospital $hospital,
        InstitutionDailyNutritionPatientReportService $report,
        CarbonInterface $from,
        CarbonInterface $to
    ) {
        $result = $report->build(
            $report->requestsForHospital($hospital->id, $from, $to),
            $from,
            $to
        );
        $templates = app(InstitutionReportTemplateService::class);
        $this->context = [
            'hospital' => $hospital->name,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'periodo' => $from->format('d/m/Y').' al '.$to->format('d/m/Y'),
        ];
        $this->rows = $templates->projectRows(
            InstitutionReportTemplateService::DAILY_PATIENT,
            $result['rows']
        );
        $offset = $templates->metadataOffset(InstitutionReportTemplateService::DAILY_PATIENT, $this->context);
        $this->summaryRows = array_map(fn (int $row) => $row + $offset, $result['summary_rows']);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return app(InstitutionReportTemplateService::class)->headingRows(
            InstitutionReportTemplateService::DAILY_PATIENT,
            $this->context
        );
    }

    public function columnFormats(): array
    {
        $costColumn = app(InstitutionReportTemplateService::class)->columnLetter(
            InstitutionReportTemplateService::DAILY_PATIENT,
            'costo'
        );

        return $costColumn ? [$costColumn => '$#,##0.00'] : [];
    }

    public function styles(Worksheet $sheet): array
    {
        $templates = app(InstitutionReportTemplateService::class);
        $headerRow = $templates->styleWorksheet(
            $sheet,
            InstitutionReportTemplateService::DAILY_PATIENT,
            $this->context,
            '1E293B',
            'FFFFFF'
        );
        $highestRow = max($headerRow + 1, $sheet->getHighestRow());
        $highestColumn = $sheet->getHighestColumn();
        $dataStart = $headerRow + 1;

        foreach ($this->summaryRows as $rowNumber) {
            $sheet->getStyle("A{$rowNumber}:{$highestColumn}{$rowNumber}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E5F3'],
                ],
            ]);
        }

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
        foreach (['paciente', 'medico'] as $key) {
            if ($column = $templates->columnLetter(InstitutionReportTemplateService::DAILY_PATIENT, $key)) {
                $sheet->getStyle("{$column}{$dataStart}:{$column}{$highestRow}")->getAlignment()->setWrapText(true);
            }
        }
        if ($costColumn = $templates->columnLetter(InstitutionReportTemplateService::DAILY_PATIENT, 'costo')) {
            $sheet->getStyle("{$costColumn}{$dataStart}:{$costColumn}{$highestRow}")
                ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
        }

        $widths = ['fecha' => 15, 'lote' => 20, 'paciente' => 38, 'medico' => 36, 'nacimiento' => 20, 'costo' => 18];
        foreach ($widths as $key => $width) {
            if ($column = $templates->columnLetter(InstitutionReportTemplateService::DAILY_PATIENT, $key)) {
                $sheet->getColumnDimension($column)->setWidth($width);
            }
        }

        return [];
    }
}
