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
        $expectedCount = $hospitalView ? 11 : 19;
        $html = HospitalRequestTable::render($category, $role, $empty);
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $headers = $xpath->query('//table/thead/tr/th');
        $this->assertSame($expectedCount, $headers->length);
        $expected = [
            'Tipo', 'ID mezcla', 'No. solicitud', 'Hospital', 'Paciente', 'Fecha y hora de solicitud',
            'Fecha y hora programada de entrega', 'Estado operativo', 'Lote', 'Ver', 'Aprobación',
        ];
        foreach ($expected as $index => $label) {
            $text = preg_replace('/[↑↓↔]/u', '', $headers->item($index)->textContent);
            $this->assertSame($label, trim(preg_replace('/\s+/u', ' ', $text)));
        }
        $this->assertSame($hospitalView ? 10 : 11, $xpath->query('//th[@data-force-column-filter]')->length);

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
            $this->assertSame('Ver', trim($cells->item(9)->textContent));
            $this->assertSame('Aprobada', trim($cells->item(10)->textContent));
            $this->assertSame(1, $xpath->query('./a[@href]', $cells->item(9))->length);
            if ($hospitalView) {
                $this->assertSame(1, $xpath->query('./button[@disabled]', $cells->item(10))->length);
                $this->assertSame(0, $xpath->query('.//a[@target="_blank"] | .//form | .//*[@data-dispensing-popup]', $row)->length);
            }
        }
    }
}
