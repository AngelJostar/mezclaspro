<?php

namespace App\Exports\Instituciones;

use App\Models\Hospital;
use App\Models\Nutricionales\NutriDistributor;
use App\Services\InstitutionMonthlyNutritionSupplyReportService;
use App\Services\InstitutionReportTemplateService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstitutionMonthlyNutritionSupplyHospitalExport implements FromArray, WithStyles, WithColumnFormatting, WithDrawings, WithTitle
{
    private const COLUMN_COUNT = 6;

    private array $rows = [];

    private array $layout = [];

    private string $sheetTitle;

    private ?NutriDistributor $distributor = null;

    public function __construct(
        private readonly Hospital $hospital,
        private readonly CarbonInterface $month,
        InstitutionMonthlyNutritionSupplyReportService $report,
    ) {
        $this->loadLegalRelations();

        $templates = app(InstitutionReportTemplateService::class);
        $definition = $templates->get(InstitutionReportTemplateService::MONTHLY_SUPPLIES);
        $reportRows = $templates->projectRows(
            InstitutionReportTemplateService::MONTHLY_SUPPLIES,
            $report->build($report->requestsForHospital((int) $hospital->id, $month)),
        );

        $this->rows = $this->buildRows(
            $reportRows,
            $templates->visibleColumns(InstitutionReportTemplateService::MONTHLY_SUPPLIES),
            (string) ($definition['title'] ?? 'Reporte mensual de insumos por hospital'),
            (string) ($definition['subtitle'] ?? 'Consumo mensual consolidado de insumos de nutrición parenteral.'),
        );

        $hospitalName = preg_replace(
            '/\s+/',
            ' ',
            (string) preg_replace('/[\[\]:*?\/\\\\]/', ' ', Str::ascii((string) $hospital->name)),
        );
        $this->sheetTitle = mb_substr(trim('H'.$hospital->id.' '.$hospitalName), 0, 31);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return $this->sheetTitle ?: $this->month->format('Y-m');
    }

    public function columnFormats(): array
    {
        return [
            'F' => '#,##0.00',
        ];
    }

    public function drawings(): array
    {
        $drawings = [];

        if ($prodifemLogo = $this->publicFile((string) config('prodifem.logo'))) {
            $drawings[] = $this->makeDrawing('Logotipo Prodifem', $prodifemLogo, 'A1');
        }

        if ($this->distributor && ($distributorLogo = $this->distributorLogo($this->distributor))) {
            $drawings[] = $this->makeDrawing('Logotipo del subdistribuidor', $distributorLogo, 'D1');
        }

        return $drawings;
    }

    public function styles(Worksheet $sheet): array
    {
        $legalEnd = $this->layout['legal_end'];
        $titleRow = $this->layout['title'];
        $subtitleRow = $this->layout['subtitle'];
        $metadataStart = $this->layout['metadata_start'];
        $metadataEnd = $this->layout['metadata_end'];
        $statementRow = $this->layout['statement'];
        $headerRow = $this->layout['table_header'];
        $dataStart = $this->layout['data_start'];
        $dataEnd = $this->layout['data_end'];
        $closingRow = $this->layout['closing'];
        $signatureLineRow = $this->layout['signature_line'];
        $signatureCaptionRow = $this->layout['signature_caption'];

        $sheet->setShowGridlines(false);
        $sheet->freezePane('A'.$dataStart);
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()
            ->setTop(0.35)
            ->setRight(0.35)
            ->setBottom(0.55)
            ->setLeft(0.35);
        $sheet->getPageSetup()->setPrintArea('A1:F'.$signatureCaptionRow);

        foreach (range(1, $legalEnd) as $row) {
            $sheet->mergeCells("B{$row}:C{$row}");
            $sheet->mergeCells("E{$row}:F{$row}");
        }

        $sheet->mergeCells("A{$titleRow}:F{$titleRow}");
        $sheet->mergeCells("A{$subtitleRow}:F{$subtitleRow}");

        foreach (range($metadataStart, $metadataEnd) as $row) {
            $sheet->mergeCells("B{$row}:F{$row}");
        }

        $sheet->mergeCells("A{$statementRow}:F{$statementRow}");
        $sheet->mergeCells("A{$closingRow}:F{$closingRow}");
        $sheet->mergeCells("A{$signatureLineRow}:F{$signatureLineRow}");
        $sheet->mergeCells("A{$signatureCaptionRow}:F{$signatureCaptionRow}");

        foreach (range($headerRow, $dataEnd) as $row) {
            $sheet->mergeCells("A{$row}:D{$row}");
        }

        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(21);
        $sheet->getColumnDimension('C')->setWidth(21);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(16);

        $sheet->getRowDimension(1)->setRowHeight(31);
        foreach (range(2, $legalEnd) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(22);
        }
        $sheet->getRowDimension($titleRow)->setRowHeight(28);
        $sheet->getRowDimension($subtitleRow)->setRowHeight(22);
        $sheet->getRowDimension($statementRow)->setRowHeight(44);
        $sheet->getRowDimension($closingRow)->setRowHeight(38);
        $sheet->getRowDimension($signatureLineRow)->setRowHeight(30);

        $sheet->getStyle("A1:F{$signatureCaptionRow}")->getFont()->setName('Arial')->setSize(10);
        $sheet->getStyle("B1:C{$legalEnd}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getStyle("E1:F{$legalEnd}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $thinBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFB7C5D8'],
                ],
            ],
        ];
        $sheet->getStyle("A1:C{$legalEnd}")->applyFromArray($thinBorder);
        $sheet->getStyle("D1:F{$legalEnd}")->applyFromArray($thinBorder);
        $sheet->getStyle("A{$headerRow}:F{$dataEnd}")->applyFromArray($thinBorder);

        $sheet->getStyle("B1:C1")->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle("E1:F1")->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle("A{$titleRow}:F{$titleRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FF132A4D']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9EAFB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF2F80ED']]],
        ]);
        $sheet->getStyle("A{$subtitleRow}:F{$subtitleRow}")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF50627A']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle("A{$metadataStart}:A{$metadataEnd}")->getFont()->setBold(true);
        $sheet->getStyle("A{$metadataStart}:F{$metadataEnd}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);
        $sheet->getStyle("A{$statementRow}:F{$statementRow}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $sheet->getStyle("A{$headerRow}:F{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E416A']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(28);
        $sheet->getStyle("A{$dataStart}:F{$dataEnd}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getStyle("E{$dataStart}:F{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("A{$closingRow}:F{$closingRow}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getStyle("A{$signatureLineRow}:F{$signatureLineRow}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1F2937'));
        $sheet->getStyle("A{$signatureCaptionRow}:F{$signatureCaptionRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$signatureCaptionRow}:F{$signatureCaptionRow}")->getFont()->setItalic(true);

        return [];
    }

    private function buildRows(array $reportRows, array $visibleColumns, string $title, string $subtitle): array
    {
        $prodifem = $this->prodifemLegalData();
        $subdistributor = $this->distributor
            ? $this->distributorLegalData($this->distributor)
            : [
                'name' => 'Sin subdistribuidor asignado',
                'address' => 'Sin dirección fiscal registrada',
                'rfc' => 'Sin RFC registrado',
                'phone' => 'Sin registrar',
                'email' => 'Sin registrar',
            ];
        $period = $this->month->copy()->locale('es')->translatedFormat('F Y');
        $contract = $this->contractNumber();
        $hospitalName = filled($this->hospital->name) ? (string) $this->hospital->name : 'Sin hospital registrado';
        $hospitalAddress = $this->hospitalAddress();

        $rows = [
            ['', $prodifem['name'], '', '', $subdistributor['name'], ''],
            ['', 'Dirección fiscal: '.$prodifem['address'], '', '', 'Dirección fiscal: '.$subdistributor['address'], ''],
            ['', 'RFC: '.$prodifem['rfc'].' | Teléfono: '.$prodifem['phone'], '', '', 'RFC: '.$subdistributor['rfc'].' | Teléfono: '.$subdistributor['phone'], ''],
            ['', 'Correo electrónico: '.$prodifem['email'], '', '', 'Correo electrónico: '.$subdistributor['email'], ''],
            $this->emptyRow(),
            [$title, '', '', '', '', ''],
            [$subtitle, '', '', '', '', ''],
            $this->emptyRow(),
            ['Hospital', $hospitalName, '', '', '', ''],
            ['Periodo', $period, '', '', '', ''],
            ['Lugar de expedición', $prodifem['address'], '', '', '', ''],
            ['Lugar de entrega', $hospitalName.', '.$hospitalAddress, '', '', '', ''],
            ['Clave CLUES', filled($this->hospital->clues) ? (string) $this->hospital->clues : 'Sin CLUES registrada', '', '', '', ''],
            ['Contrato', $contract, '', '', '', ''],
            ['Dirección del hospital', $hospitalAddress, '', '', '', ''],
            ['Fecha de generación', now()->format('d/m/Y H:i'), '', '', '', ''],
            $this->emptyRow(),
            [sprintf(
                'Por medio de la presente se hace constancia del consumo mensual consolidado de insumos para el servicio de nutrición parenteral individualizada del hospital %s, correspondiente al periodo %s.',
                $hospitalName,
                $period,
            ), '', '', '', '', ''],
            $this->emptyRow(),
            ['DESCRIPCIÓN', '', '', '', 'UNIDAD', 'CANTIDAD'],
        ];

        $columnKeys = array_map(
            static fn (array $column): string => (string) ($column['key'] ?? ''),
            $visibleColumns,
        );

        if ($reportRows === []) {
            $reportRows = [['Sin insumos registrados para este mes', '-', 0]];
        }

        foreach ($reportRows as $reportRow) {
            $mapped = [];
            foreach ($columnKeys as $index => $key) {
                if ($key !== '') {
                    $mapped[$key] = $reportRow[$index] ?? null;
                }
            }

            $rows[] = [
                (string) ($mapped['descripcion'] ?? $reportRow[0] ?? ''),
                '',
                '',
                '',
                (string) ($mapped['unidad'] ?? $reportRow[1] ?? ''),
                $mapped['cantidad'] ?? $reportRow[2] ?? '',
            ];
        }

        $dataEnd = count($rows);
        $rows[] = $this->emptyRow();
        $rows[] = [sprintf(
            'El suministro de insumos se realizó de acuerdo con las condiciones, características y especificaciones pactadas en el contrato No. %s.',
            $contract,
        ), '', '', '', '', ''];
        $rows[] = $this->emptyRow();
        $rows[] = $this->emptyRow();
        $rows[] = ['Nombre, Cargo, Firma, Sello y Fecha', '', '', '', '', ''];

        $this->layout = [
            'legal_end' => 4,
            'title' => 6,
            'subtitle' => 7,
            'metadata_start' => 9,
            'metadata_end' => 16,
            'statement' => 18,
            'table_header' => 20,
            'data_start' => 21,
            'data_end' => $dataEnd,
            'closing' => $dataEnd + 2,
            'signature_line' => $dataEnd + 4,
            'signature_caption' => $dataEnd + 5,
        ];

        return $rows;
    }

    private function loadLegalRelations(): void
    {
        if (! $this->hospital->relationLoaded('nutriMedicineList')) {
            if ($this->hospital->exists) {
                $this->hospital->loadMissing('nutriMedicineList.distributor');
            } else {
                $this->hospital->setRelation('nutriMedicineList', null);
            }
        }

        $list = $this->hospital->relationLoaded('nutriMedicineList')
            ? $this->hospital->getRelation('nutriMedicineList')
            : null;

        if ($list && ! $list->relationLoaded('distributor')) {
            if ($list->exists) {
                $list->loadMissing('distributor');
            } else {
                $list->setRelation('distributor', null);
            }
        }

        $this->distributor = $list?->relationLoaded('distributor')
            ? $list->getRelation('distributor')
            : null;
    }

    private function prodifemLegalData(): array
    {
        return [
            'name' => (string) config('prodifem.legal_name', 'Prodifem S.A. de C.V.'),
            'address' => (string) config('prodifem.fiscal_address', 'Sin dirección fiscal registrada'),
            'rfc' => (string) config('prodifem.rfc', 'Sin RFC registrado'),
            'phone' => (string) config('prodifem.phone', 'Sin registrar'),
            'email' => (string) config('prodifem.email', 'Sin registrar'),
        ];
    }

    private function contractNumber(): string
    {
        $list = $this->hospital->relationLoaded('nutriMedicineList')
            ? $this->hospital->getRelation('nutriMedicineList')
            : null;

        if ($list?->has_contract === false) {
            return 'Sin contrato registrado';
        }

        return filled($list?->contract_number)
            ? (string) $list->contract_number
            : 'Sin contrato registrado';
    }

    private function hospitalAddress(): string
    {
        if (filled($this->hospital->adress)) {
            return (string) $this->hospital->adress;
        }

        $parts = array_filter([
            $this->hospital->street_number,
            $this->hospital->neighborhood,
            $this->hospital->municipality,
            $this->hospital->state,
            $this->hospital->country,
            filled($this->hospital->postal_code) ? 'C.P. '.$this->hospital->postal_code : null,
        ], static fn ($value): bool => filled($value));

        return $parts ? implode(', ', $parts) : 'Sin dirección registrada';
    }

    private function distributorLegalData(NutriDistributor $distributor): array
    {
        $contactText = trim(implode(' ', array_filter([
            $distributor->contacto,
            $distributor->informacion_adicional,
        ], static fn ($value): bool => filled($value))));
        preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $contactText, $emailMatch);
        preg_match('/\+?\d[\d\s().-]{7,}\d/', $contactText, $phoneMatch);

        return [
            'name' => $distributor->nombre ?: 'Sin razón social registrada',
            'address' => $distributor->direccion ?: 'Sin dirección fiscal registrada',
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

    private function makeDrawing(string $name, string $path, string $coordinates): Drawing
    {
        $drawing = new Drawing();
        $drawing->setName($name);
        $drawing->setDescription($name);
        $drawing->setPath($path);
        $drawing->setHeight(48);
        $drawing->setCoordinates($coordinates);
        $drawing->setOffsetX(6);
        $drawing->setOffsetY(5);

        return $drawing;
    }

    private function emptyRow(): array
    {
        return array_fill(0, self::COLUMN_COUNT, '');
    }
}
