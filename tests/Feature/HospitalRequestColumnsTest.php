<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fixtures\HospitalRequestTable;
use Tests\TestCase;

class HospitalRequestColumnsTest extends TestCase
{
    public static function views(): array
    {
        $cases = [];
        foreach (['Cliente', 'Institucion', 'Admin', 'Super Admin'] as $role) {
            foreach (['todas', 'nutricionales', 'oncologicos', 'antibioticos'] as $category) {
                foreach ([false, true] as $empty) {
                    $cases[$role.' '.$category.($empty ? ' empty' : ' populated')] = [$role, $category, $empty];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('views')]
    public function test_request_columns_follow_the_hospital_role_in_every_view(string $role, string $category, bool $empty): void
    {
        $hospitalView = in_array($role, ['Cliente', 'Institucion'], true);
        $expectedCount = $hospitalView ? 14 : 22;
        $html = HospitalRequestTable::render($category, $role, $empty);
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $headers = $xpath->query('//table/thead/tr/th');
        $this->assertSame($expectedCount, $headers->length);
        $expected = [
            'Tipo', 'ID mezcla', 'No. solicitud', 'Institución', 'Hospital', 'Paciente', 'Fecha y hora de solicitud',
            'Fecha y hora programada de entrega', 'Lote', 'Ver', 'Mensajes', 'Aprobación', 'Ajustes', 'Estado de proceso',
        ];
        foreach ($expected as $index => $label) {
            $text = preg_replace('/[↑↓↔]/u', '', $headers->item($index)->textContent);
            $this->assertSame($label, trim(preg_replace('/\s+/u', ' ', $text)));
        }
        $lines = $xpath->query('./span', $headers->item(13));
        $this->assertCount(2, $lines);
        $this->assertSame('Estado de', trim($lines->item(0)->textContent));
        if (!$hospitalView) {
            $this->assertSame('Próximo proceso', trim(preg_replace('/\s+/u', ' ', $headers->item(14)->textContent)));
        }
        $this->assertSame($hospitalView ? 12 : 13, $xpath->query('//th[@data-force-column-filter]')->length);

        $rows = $xpath->query('//table/tbody/tr');
        if (! $empty) {
            $this->assertSame($category === 'todas' ? 3 : 1, $rows->length);
        }
        foreach ($rows as $row) {
            $cells = $xpath->query('./td', $row);
            if ($empty) {
                $this->assertSame(1, $cells->length);
                $this->assertSame((string) $expectedCount, $cells->item(0)->getAttribute('colspan'));
                continue;
            }
            $this->assertSame($expectedCount, $cells->length);
            $this->assertSame('Institucion de prueba', trim($cells->item(3)->textContent));
            $this->assertSame('Hospital de prueba', trim($cells->item(4)->textContent));
            $this->assertSame('Ver', trim($cells->item(9)->textContent));
            $this->assertSame('Aprobada', trim($cells->item(11)->textContent));
            $this->assertSame(1, $xpath->query('./a[@href]', $cells->item(9))->length);
            $this->assertSame('Sin Ajustes', trim($cells->item(12)->textContent));
            $this->assertContains(trim($cells->item(13)->textContent), ['Aprobada', 'Preparada']);
            $this->assertSame(1, $xpath->query('.//button[@disabled and @aria-label="Sin mensajes"]', $cells->item(10))->length);
            $this->assertSame($hospitalView ? 1 : 0, $xpath->query('.//button[@aria-label="Enviar mensaje"]', $cells->item(10))->length);
            if ($hospitalView) {
                $this->assertSame(1, $xpath->query('./button[@disabled]', $cells->item(11))->length);
                $this->assertSame(0, $xpath->query('.//a[@target="_blank"] | .//form | .//*[@data-dispensing-popup]', $row)->length);
            }
        }
    }

    public function test_adjusted_approval_has_a_distinct_label_in_each_column(): void
    {
        foreach (['Cliente', 'Institucion', 'Admin', 'Super Admin'] as $role) {
            foreach (['todas', 'nutricionales', 'oncologicos', 'antibioticos'] as $category) {
                $html = HospitalRequestTable::render($category, $role, false, 'approved');
                $document = new \DOMDocument();
                @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
                $xpath = new \DOMXPath($document);
                foreach ($xpath->query('//table/tbody/tr') as $row) {
                    $cells = $xpath->query('./td', $row);
                    $this->assertSame('Aprobada', trim($cells->item(11)->textContent));
                    $this->assertSame('Aprobada con Ajuste', trim($cells->item(12)->textContent));
                    foreach ([11, 12] as $index) {
                        $link = $xpath->query('./a[@data-approval-popup]', $cells->item($index));
                        $this->assertSame(1, $link->length);
                        $this->assertStringContainsString('bg-green-600', $link->item(0)->getAttribute('class'));
                        $this->assertStringContainsString('text-white', $link->item(0)->getAttribute('class'));
                    }
                }
            }
        }
    }

    public function test_institution_cells_handle_unassigned_multiple_and_escaped_names(): void
    {
        foreach (['todas', 'nutricionales', 'oncologicos', 'antibioticos'] as $category) {
            foreach ([
                [[], 'Sin institución'],
                [['Institucion A', 'Institucion B', 'Institucion A', ''], 'Institucion A, Institucion B'],
                [['<script>institucion</script>'], '<script>institucion</script>'],
            ] as [$names, $expected]) {
                $html = HospitalRequestTable::render($category, 'Super Admin', false, null, $names);
                $document = new \DOMDocument();
                @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
                $xpath = new \DOMXPath($document);
                foreach ($xpath->query('//table/tbody/tr/td[4]') as $cell) {
                    $this->assertSame($expected, trim($cell->textContent));
                    $this->assertSame(0, $xpath->query('.//script', $cell)->length);
                }
            }
        }
        $this->assertStringContainsString('Sin institución', view('admin.solicitudes._institution-cell', [
            'institutionHospital' => null,
        ])->render());
    }
}
