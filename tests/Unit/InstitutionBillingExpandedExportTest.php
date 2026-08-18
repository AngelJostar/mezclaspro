<?php

namespace Tests\Unit;

use App\Exports\Instituciones\InstitutionBillingExpandedExport;
use App\Http\Controllers\Admin\InstitucionBillingController;
use App\Services\InstitutionBillingDueDateService;
use App\Services\InstitutionBillingPricingService;
use PHPUnit\Framework\TestCase;

class InstitutionBillingExpandedExportTest extends TestCase
{
    public function test_it_exports_one_row_per_billing_concept_with_expanded_columns(): void
    {
        $rows = [
            [
                'Institucion Demo',
                'Hospital Demo',
                'Medico Demo',
                'Paciente Demo',
                '12',
                '10/08/2026 09:00',
                'Mezcla oncologica',
                'Oncologia',
                'EXP-1',
                1,
                'Medicamento',
                'Oxaliplatino',
                230,
                'mg',
                92.30,
                21229.00,
                0.00,
                21229.00,
                21750.75,
                'EMPRESA-1',
                21750.75,
                'Si',
                'UUID-1',
                'INT-1',
                '10/08/2026',
                'CARTA-1',
                '10/08/2026',
                'Pendiente',
                'Yellow - 10 dias',
            ],
        ];

        $export = new InstitutionBillingExpandedExport($rows);

        $this->assertCount(29, $export->headings());
        $this->assertSame('TIPO DE CONCEPTO', $export->headings()[10]);
        $this->assertSame('DESCRIPCION DEL CONCEPTO', $export->headings()[11]);
        $this->assertSame('IVA', $export->headings()[16]);
        $this->assertSame($rows, $export->array());
    }

    public function test_it_builds_separate_rows_for_medicine_supply_and_mixing_service(): void
    {
        $controller = new class(new InstitutionBillingPricingService(), new InstitutionBillingDueDateService()) extends InstitucionBillingController
        {
            public function breakdown($record, string $type, array $pricing): array
            {
                return $this->buildBillingBreakdownLines($record, $type, $pricing);
            }

            public function expandedRows($records): array
            {
                return $this->buildExpandedExportRows($records);
            }
        };

        $pricing = [
            'lines' => collect([[
                'description' => 'Oxaliplatino',
                'quantity' => 230,
                'unit_label' => 'mg',
                'unit_price' => 92.30,
                'subtotal' => 21229.00,
                'vat' => 0.00,
                'total_with_vat' => 21229.00,
            ]]),
            'supplies_base' => 100.00,
            'supplies_vat' => 16.00,
            'supplies_total' => 116.00,
            'service_base' => 349.78,
            'service_vat' => 55.97,
            'service_total' => 405.75,
            'total_iva_included' => 21750.75,
        ];
        $record = (object) ['infusor_nombre' => 'Infusor elastomerico'];
        $breakdown = $controller->breakdown($record, 'onco', $pricing);

        $this->assertSame(['Medicamento', 'Insumo', 'Servicio'], array_column($breakdown, 'concept_type'));

        $expandedRows = $controller->expandedRows(collect([[
            'billing' => null,
            'computed_total_input' => '21750.75',
            'vencimiento' => ['status' => 'yellow', 'days' => 10],
            'breakdown_lines' => $breakdown,
            'institucion' => (object) ['nombre' => 'Institucion Demo'],
            'hospital' => (object) ['name' => 'Hospital Demo'],
            'medico' => 'Medico Demo',
            'patient_name' => 'Paciente Demo',
            'remision' => '12',
            'fecha' => '10/08/2026 09:00',
            'tipo_texto' => 'Mezcla oncologica',
            'servicio' => 'Oncologia',
            'registro' => 'EXP-1',
            'empresa' => 'EMPRESA-1',
        ]]));

        $this->assertCount(3, $expandedRows);
        $this->assertSame([1, 2, 3], array_column($expandedRows, 9));
        $this->assertSame(['Medicamento', 'Insumo', 'Servicio'], array_column($expandedRows, 10));
        $this->assertSame('Yellow - 10 dias', $expandedRows[0][28]);
    }
}
