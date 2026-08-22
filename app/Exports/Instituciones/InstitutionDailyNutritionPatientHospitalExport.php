<?php

namespace App\Exports\Instituciones;

use App\Models\Hospital;
use App\Models\Nutricionales\NutriDistributor;
use App\Services\InstitutionDailyNutritionPatientReportService;
use App\Services\InstitutionReportTemplateService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstitutionDailyNutritionPatientHospitalExport implements FromArray, WithStyles, WithColumnFormatting, WithDrawings
{
    private array $rows;

    private array $summaryRows;

    private array $letterheadBlocks = [];

    private array $drawings = [];

    private int $titleRow;

    private int $subtitleRow;

    private int $metadataStartRow;

    private int $metadataEndRow;

    private int $headerRow;

    private int $dataStartRow;

    private int $dataEndRow;

    private int $signatureLineRow;

    private int $signatureRow;

    private string $lastColumn;

    public function __construct(
        Hospital $hospital,
        InstitutionDailyNutritionPatientReportService $report,
        CarbonInterface $from,
        CarbonInterface $to
    ) {
        $hospital->loadMissing('nutriMedicineList.distributor');

        $result = $report->build(
            $report->requestsForHospital($hospital->id, $from, $to),
            $from,
            $to
        );
        $templates = app(InstitutionReportTemplateService::class);
        $dataRows = $templates->projectRows(
            InstitutionReportTemplateService::DAILY_PATIENT,
            $result['rows']
        );

        $columnCount = count($templates->visibleColumns(InstitutionReportTemplateService::DAILY_PATIENT));
        $this->lastColumn = Coordinate::stringFromColumnIndex(max(1, $columnCount));
        $headingRows = $this->buildHeadingRows($hospital, $from, $to, $columnCount);
        $this->headerRow = count($headingRows);
        $this->dataStartRow = $this->headerRow + 1;
        $this->dataEndRow = $this->headerRow + count($dataRows);
        $this->summaryRows = array_map(
            fn (int $row): int => $this->headerRow + $row - 1,
            $result['summary_rows']
        );

        $this->rows = array_merge($headingRows, $dataRows);
        $blankRow = array_fill(0, $columnCount, null);
        $this->rows[] = $blankRow;
        $this->rows[] = $blankRow;
        $this->signatureLineRow = count($this->rows);
        $signatureRow = $blankRow;
        $signatureRow[0] = 'Nombre, cargo, firma sello y fecha';
        $this->rows[] = $signatureRow;
        $this->signatureRow = count($this->rows);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function drawings(): array
    {
        return $this->drawings;
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
        $sheet->setShowGridlines(false);

        foreach ($this->letterheadBlocks as $block) {
            $this->styleLetterheadBlock($sheet, $block['start'], $block['end']);
        }

        $sheet->mergeCells("A{$this->titleRow}:{$this->lastColumn}{$this->titleRow}");
        $sheet->getStyle("A{$this->titleRow}:{$this->lastColumn}{$this->titleRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '172033']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9EAFB'],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($this->titleRow)->setRowHeight(28);

        $sheet->mergeCells("A{$this->subtitleRow}:{$this->lastColumn}{$this->subtitleRow}");
        $sheet->getStyle("A{$this->subtitleRow}:{$this->lastColumn}{$this->subtitleRow}")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
        ]);

        for ($row = $this->metadataStartRow; $row <= $this->metadataEndRow; $row++) {
            $sheet->mergeCells("B{$row}:{$this->lastColumn}{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("B{$row}:{$this->lastColumn}{$row}")->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($row)->setRowHeight(20);
        }

        $sheet->getStyle("A{$this->headerRow}:{$this->lastColumn}{$this->headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension($this->headerRow)->setRowHeight(30);

        foreach ($this->summaryRows as $rowNumber) {
            $sheet->getStyle("A{$rowNumber}:{$this->lastColumn}{$rowNumber}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E5F3'],
                ],
            ]);
            $sheet->getStyle("A{$rowNumber}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setWrapText(true);
        }

        $sheet->getStyle("A{$this->headerRow}:{$this->lastColumn}{$this->dataEndRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '94A3B8'],
                ],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        foreach (['paciente', 'medico'] as $key) {
            if ($column = $templates->columnLetter(InstitutionReportTemplateService::DAILY_PATIENT, $key)) {
                $sheet->getStyle("{$column}{$this->dataStartRow}:{$column}{$this->dataEndRow}")
                    ->getAlignment()->setWrapText(true);
            }
        }
        if ($costColumn = $templates->columnLetter(InstitutionReportTemplateService::DAILY_PATIENT, 'costo')) {
            $sheet->getStyle("{$costColumn}{$this->dataStartRow}:{$costColumn}{$this->dataEndRow}")
                ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
        }
        if ($quantityColumn = $templates->columnLetter(InstitutionReportTemplateService::DAILY_PATIENT, 'cantidad_dia')) {
            $sheet->getStyle("{$quantityColumn}{$this->dataStartRow}:{$quantityColumn}{$this->dataEndRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            foreach ($this->summaryRows as $rowNumber) {
                $sheet->getStyle("{$quantityColumn}{$rowNumber}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }
        }

        $sheet->getStyle("A{$this->signatureLineRow}:{$this->lastColumn}{$this->signatureLineRow}")->applyFromArray([
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '172033'],
                ],
            ],
        ]);
        $sheet->mergeCells("A{$this->signatureRow}:{$this->lastColumn}{$this->signatureRow}");
        $sheet->getStyle("A{$this->signatureRow}:{$this->lastColumn}{$this->signatureRow}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '172033'],
                ],
            ],
        ]);
        $sheet->getRowDimension($this->signatureLineRow)->setRowHeight(28);
        $sheet->getRowDimension($this->signatureRow)->setRowHeight(24);

        $widths = [
            'cantidad_dia' => 30,
            'fecha' => 15,
            'lote' => 20,
            'paciente' => 34,
            'medico' => 32,
            'nacimiento' => 20,
            'costo' => 18,
        ];
        foreach ($widths as $key => $width) {
            if ($column = $templates->columnLetter(InstitutionReportTemplateService::DAILY_PATIENT, $key)) {
                $sheet->getColumnDimension($column)->setWidth($width);
            }
        }

        $sheet->freezePane("A{$this->dataStartRow}");
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setPrintArea("A1:{$this->lastColumn}{$this->signatureRow}");
        $sheet->getPageMargins()
            ->setTop(0.35)
            ->setRight(0.3)
            ->setBottom(0.4)
            ->setLeft(0.3);

        return [];
    }

    private function buildHeadingRows(
        Hospital $hospital,
        CarbonInterface $from,
        CarbonInterface $to,
        int $columnCount
    ): array {
        $rows = [];
        $this->addLetterheadBlock(
            $rows,
            [
                'name' => (string) config('prodifem.legal_name'),
                'address' => (string) config('prodifem.fiscal_address'),
                'rfc' => (string) config('prodifem.rfc'),
                'phone' => (string) config('prodifem.phone'),
                'email' => (string) config('prodifem.email'),
            ],
            $this->publicFile((string) config('prodifem.logo')),
            $columnCount
        );

        $distributor = $hospital->nutriMedicineList?->distributor;
        if ($distributor instanceof NutriDistributor) {
            $rows[] = $this->row($columnCount);
            $this->addLetterheadBlock(
                $rows,
                $this->distributorLegalData($distributor),
                $this->distributorLogo($distributor),
                $columnCount,
                'SUBDISTRIBUIDOR: '
            );
        }

        $rows[] = $this->row($columnCount);
        $this->titleRow = count($rows) + 1;
        $rows[] = $this->row($columnCount, [0 => 'Reporte diario por paciente']);
        $this->subtitleRow = count($rows) + 1;
        $rows[] = $this->row($columnCount, [0 => 'Nutriciones conciliables entregadas, agrupadas por dia']);
        $rows[] = $this->row($columnCount);

        $this->metadataStartRow = count($rows) + 1;
        $rows[] = $this->row($columnCount, [0 => 'Hospital', 1 => $hospital->name]);
        $rows[] = $this->row($columnCount, [0 => 'Periodo', 1 => $from->format('d/m/Y').' al '.$to->format('d/m/Y')]);
        $rows[] = $this->row($columnCount, [0 => 'Clave CLUES', 1 => $hospital->clues ?: 'Sin CLUES registrada']);
        $rows[] = $this->row($columnCount, [0 => 'Contrato', 1 => $this->contractNumber($hospital)]);
        $rows[] = $this->row($columnCount, [0 => 'Direccion del Hospital', 1 => $this->hospitalAddress($hospital)]);
        $rows[] = $this->row($columnCount, [0 => 'Fecha de generacion', 1 => now()->format('d/m/Y H:i')]);
        $this->metadataEndRow = count($rows);
        $rows[] = $this->row($columnCount);
        $rows[] = $this->row($columnCount, [
            0 => 'CANTIDAD POR DIA',
            1 => 'FECHA',
            2 => 'LOTE',
            3 => 'PACIENTE',
            4 => 'MEDICO',
            5 => 'FECHA DE NACIMIENTO',
            6 => 'COSTO',
        ]);

        return $rows;
    }

    private function addLetterheadBlock(
        array &$rows,
        array $legalData,
        ?string $logo,
        int $columnCount,
        string $namePrefix = ''
    ): void {
        $start = count($rows) + 1;
        $rows[] = $this->row($columnCount, [2 => $namePrefix.$legalData['name']]);
        $rows[] = $this->row($columnCount, [2 => 'Direccion fiscal: '.$legalData['address']]);
        $rows[] = $this->row($columnCount, [2 => 'RFC: '.$legalData['rfc'].' | Telefono: '.$legalData['phone']]);
        $rows[] = $this->row($columnCount, [2 => 'Correo electronico: '.$legalData['email']]);
        $end = count($rows);
        $this->letterheadBlocks[] = ['start' => $start, 'end' => $end];

        if ($logo) {
            $drawing = new Drawing;
            $drawing->setName($legalData['name']);
            $drawing->setDescription('Logotipo de '.$legalData['name']);
            $drawing->setPath($logo);
            $drawing->setHeight(52);
            $drawing->setCoordinates("A{$start}");
            $drawing->setOffsetX(8);
            $drawing->setOffsetY(4);
            $this->drawings[] = $drawing;
        }
    }

    private function styleLetterheadBlock(Worksheet $sheet, int $start, int $end): void
    {
        for ($row = $start; $row <= $end; $row++) {
            $sheet->mergeCells("C{$row}:{$this->lastColumn}{$row}");
            $sheet->getRowDimension($row)->setRowHeight(18);
        }
        $sheet->getStyle("C{$start}:{$this->lastColumn}{$start}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("C{$start}:{$this->lastColumn}{$end}")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
    }

    private function row(int $columnCount, array $values = []): array
    {
        $row = array_fill(0, $columnCount, null);
        foreach ($values as $index => $value) {
            if ($index < $columnCount) {
                $row[$index] = $value;
            }
        }

        return $row;
    }

    private function contractNumber(Hospital $hospital): string
    {
        $list = $hospital->nutriMedicineList;
        if ($list?->has_contract === false) {
            return 'Sin contrato registrado';
        }

        $contractNumber = $list?->contract_number;

        return filled($contractNumber) ? (string) $contractNumber : 'Sin contrato registrado';
    }

    private function hospitalAddress(Hospital $hospital): string
    {
        if (filled($hospital->adress)) {
            return (string) $hospital->adress;
        }

        $parts = array_filter([
            $hospital->street_number,
            $hospital->neighborhood,
            $hospital->municipality,
            $hospital->state,
            $hospital->country,
            filled($hospital->postal_code) ? 'C.P. '.$hospital->postal_code : null,
        ], fn ($value): bool => filled($value));

        return $parts ? implode(', ', $parts) : 'Sin direccion registrada';
    }

    private function distributorLegalData(NutriDistributor $distributor): array
    {
        $contactText = trim(implode(' ', array_filter([
            $distributor->contacto,
            $distributor->informacion_adicional,
        ], fn ($value): bool => filled($value))));
        preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $contactText, $emailMatch);
        preg_match('/\+?\d[\d\s().-]{7,}\d/', $contactText, $phoneMatch);

        return [
            'name' => $distributor->nombre ?: 'Sin razon social registrada',
            'address' => $distributor->direccion ?: 'Sin direccion fiscal registrada',
            'rfc' => $distributor->rfc ?: 'Sin RFC registrado',
            'phone' => $phoneMatch[0] ?? 'Sin registrar',
            'email' => $emailMatch[0] ?? 'Sin registrar',
        ];
    }

    private function distributorLogo(NutriDistributor $distributor): ?string
    {
        if (blank($distributor->logo_path)) {
            return null;
        }

        $path = str_replace('\\', '/', ltrim((string) $distributor->logo_path, '/'));
        $storagePath = str_starts_with($path, 'storage/')
            ? substr($path, strlen('storage/'))
            : $path;
        $absolutePath = Storage::disk('public')->path($storagePath);
        if (is_file($absolutePath)) {
            return $absolutePath;
        }

        return $this->publicFile(str_starts_with($path, 'storage/') ? $path : 'storage/'.$path);
    }

    private function publicFile(string $relativePath): ?string
    {
        if (blank($relativePath)) {
            return null;
        }

        if (preg_match('/^[A-Z]:[\\\\\/]/i', $relativePath) === 1) {
            return is_file($relativePath) ? $relativePath : null;
        }

        $path = public_path(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relativePath, '/\\')));

        return is_file($path) ? $path : null;
    }
}
