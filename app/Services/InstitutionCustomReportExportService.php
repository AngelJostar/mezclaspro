<?php

namespace App\Services;

use App\Models\Institucion;
use App\Models\InstitutionReportTemplate;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InstitutionCustomReportExportService
{
    public function __construct(private InstitutionCustomReportDataService $data)
    {
    }

    public function make(
        InstitutionReportTemplate $template,
        Institucion $institution,
        ?User $user,
        CarbonInterface $periodFrom,
        CarbonInterface $periodTo
    ): string {
        abort_unless($template->is_custom && $template->is_published, 404);

        $layout = is_array($template->layout) ? $template->layout : [];
        $rows = min(40, max(1, (int) ($layout['rows'] ?? 1)));
        $columns = min(20, max(1, (int) ($layout['columns'] ?? 1)));
        $layoutCells = collect($layout['cells'] ?? [])
            ->filter(fn ($cell) => is_array($cell))
            ->values();
        $cells = $layoutCells->keyBy(
            fn (array $cell) => ((int) ($cell['row'] ?? -1)).':'.((int) ($cell['column'] ?? -1))
        );
        $repeatCells = $layoutCells->filter(fn (array $cell) => ($cell['type'] ?? null) === 'parameter'
            && in_array($cell['repeat_direction'] ?? 'none', ['vertical', 'horizontal'], true)
            && filled($cell['parameter'] ?? null)
        );
        $repeatParameterKeys = $repeatCells
            ->pluck('parameter')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $baseContext = $this->data->baseContext($institution, $user, $periodFrom, $periodTo);
        $records = $this->data->records(
            $template,
            $institution,
            $user,
            $periodFrom,
            $periodTo,
            $repeatParameterKeys
        );
        $records = $this->orderedRecords($records, $layoutCells);
        $firstContext = array_merge($baseContext, $records->first() ?? []);
        $recordCount = max(1, $records->count());

        $expandedRows = $rows;
        $expandedColumns = $columns;
        foreach ($repeatCells as $cell) {
            if (($cell['repeat_direction'] ?? 'none') === 'vertical') {
                $expandedRows = max($expandedRows, (int) ($cell['row'] ?? 0) + $recordCount);
            }

            if (($cell['repeat_direction'] ?? 'none') === 'horizontal') {
                $expandedColumns = max($expandedColumns, (int) ($cell['column'] ?? 0) + $recordCount);
            }
        }

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator($baseContext['system.user_name'])
            ->setTitle($template->name ?: $template->title)
            ->setDescription((string) $template->subtitle);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte');

        for ($column = 1; $column <= $expandedColumns; $column++) {
            $sheet->getColumnDimensionByColumn($column)->setWidth(22);
        }

        for ($row = 1; $row <= $expandedRows; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(28);
        }

        for ($row = 1; $row <= $rows; $row++) {
            for ($column = 1; $column <= $columns; $column++) {
                $this->writeCell(
                    $sheet,
                    $column,
                    $row,
                    $cells->get(($row - 1).':'.($column - 1), []),
                    $firstContext
                );
            }
        }

        foreach ($repeatCells as $cell) {
            foreach ($records as $index => $record) {
                $row = (int) ($cell['row'] ?? 0) + 1;
                $column = (int) ($cell['column'] ?? 0) + 1;

                if (($cell['repeat_direction'] ?? 'none') === 'vertical') {
                    $row += $index;
                } else {
                    $column += $index;
                }

                $this->writeCell($sheet, $column, $row, $cell, array_merge($baseContext, $record));
            }
        }

        $sheet->getPageSetup()
            ->setOrientation($expandedColumns > 7 ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.4)->setRight(0.4)->setBottom(0.4)->setLeft(0.4);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $contents = (string) ob_get_clean();
        $spreadsheet->disconnectWorksheets();

        return $contents;
    }

    private function orderedRecords(Collection $records, Collection $layoutCells): Collection
    {
        $sortParameters = $layoutCells
            ->filter(fn (array $cell) => (int) ($cell['column'] ?? -1) === 0
                && ($cell['type'] ?? null) === 'parameter'
                && filled($cell['parameter'] ?? null)
            )
            ->sortBy(fn (array $cell) => (int) ($cell['row'] ?? 0))
            ->pluck('parameter')
            ->unique()
            ->values();

        return $records
            ->sort(function (array $left, array $right) use ($sortParameters) {
                foreach ($sortParameters as $parameter) {
                    $comparison = $this->compareValues(
                        $left['__sort.'.$parameter] ?? $left[$parameter] ?? null,
                        $right['__sort.'.$parameter] ?? $right[$parameter] ?? null
                    );

                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return ((int) ($left['__sort.sequence'] ?? 0)) <=> ((int) ($right['__sort.sequence'] ?? 0));
            })
            ->values();
    }

    private function compareValues(mixed $left, mixed $right): int
    {
        $leftBlank = $left === null || $left === '';
        $rightBlank = $right === null || $right === '';

        if ($leftBlank || $rightBlank) {
            return $leftBlank === $rightBlank ? 0 : ($leftBlank ? 1 : -1);
        }

        if (is_numeric($left) && is_numeric($right)) {
            return (float) $left <=> (float) $right;
        }

        return strnatcasecmp((string) $left, (string) $right);
    }

    private function writeCell(
        Worksheet $sheet,
        int $column,
        int $row,
        array $cell,
        array $context
    ): void {
        $coordinate = $sheet->getCell([$column, $row])->getCoordinate();
        $style = is_array($cell['style'] ?? null) ? $cell['style'] : [];

        $sheet->setCellValueExplicit($coordinate, $this->cellValue($cell, $context), DataType::TYPE_STRING);
        $sheet->getStyle($coordinate)->applyFromArray([
            'font' => [
                'name' => $style['font_family'] ?? 'Arial',
                'size' => (int) ($style['font_size'] ?? 11),
                'bold' => (bool) ($style['bold'] ?? false),
                'italic' => (bool) ($style['italic'] ?? false),
                'underline' => ($style['underline'] ?? false) ? Font::UNDERLINE_SINGLE : Font::UNDERLINE_NONE,
                'color' => ['rgb' => $this->rgb($style['color'] ?? '#1F2937', '1F2937')],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $this->rgb($style['background'] ?? '#FFFFFF', 'FFFFFF')],
            ],
            'alignment' => [
                'horizontal' => match ($style['align'] ?? 'left') {
                    'center' => Alignment::HORIZONTAL_CENTER,
                    'right' => Alignment::HORIZONTAL_RIGHT,
                    default => Alignment::HORIZONTAL_LEFT,
                },
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);
    }

    private function cellValue(array $cell, array $context): string
    {
        if (($cell['type'] ?? 'text') === 'parameter') {
            return (string) ($context[$cell['parameter'] ?? ''] ?? '');
        }

        return (string) ($cell['value'] ?? '');
    }

    private function rgb(mixed $value, string $fallback): string
    {
        $value = strtoupper(ltrim((string) $value, '#'));

        return preg_match('/^[0-9A-F]{6}$/', $value) ? $value : $fallback;
    }
}
