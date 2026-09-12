<?php

namespace Tests\Feature;

use Tests\TestCase;

class OncologyRemissionTemplateTest extends TestCase
{
    public function test_it_renders_the_bottle_template_with_each_used_presentation(): void
    {
        $medicine = (object) [
            'unidad_cobro' => 'frasco',
            'denominacion_doc' => 'Oxaliplatino',
            'dosis' => 260,
            'diluyente' => (object) ['denominacion_generica' => 'GLUCOSA 5%'],
            'presentacionesUsadas' => collect(),
            'presentation_charge_lines' => collect([
                ['presentation' => '100 mg', 'quantity' => 2, 'unit_price' => 100, 'subtotal' => 200, 'warehouse' => 'Almacén principal'],
                ['presentation' => '60 mg', 'quantity' => 1, 'unit_price' => 75, 'subtotal' => 75, 'warehouse' => 'Almacén principal'],
            ]),
        ];

        $html = $this->renderRemission($medicine);

        $this->assertStringContainsString('COBRO POR PRESENTACIÓN', $html);
        $this->assertStringNotContainsString('PROPUESTA DE FORMATO', $html);
        $this->assertStringContainsString('100 mg', $html);
        $this->assertStringContainsString('60 mg', $html);
        $this->assertStringContainsString('$200.00', $html);
        $this->assertStringContainsString('$75.00', $html);
        $this->assertStringContainsString('Servicio de mezclado', $html);
        $this->assertStringContainsString('$440.00', $html);
        $this->assertStringContainsString('class="observations"', $html);
        $this->assertStringContainsString('class="signature"', $html);
    }

    public function test_it_renders_the_milligram_template_without_presentation_breakdown(): void
    {
        $medicine = (object) [
            'unidad_cobro' => 'mg',
            'denominacion_doc' => 'Oxaliplatino',
            'dosis' => 260,
            'diluyente' => (object) ['denominacion_generica' => 'GLUCOSA 5%'],
            'presentacionesUsadas' => collect(),
            'presentation_charge_lines' => collect([
                ['presentation' => 'NO DEBE MOSTRARSE', 'quantity' => 2, 'unit_price' => 100, 'subtotal' => 200, 'warehouse' => 'Almacén principal'],
            ]),
            'cantidad_cobro' => 12.5,
            'precio_unitario_calculado' => 8,
            'subtotal_calculado' => 100,
        ];

        $html = $this->renderRemission($medicine);

        $this->assertStringContainsString('COBRO POR MILIGRAMO', $html);
        $this->assertStringContainsString('<td>mg</td>', $html);
        $this->assertStringContainsString('12.5', $html);
        $this->assertStringContainsString('GLUCOSA 5%', $html);
        $this->assertStringContainsString('250 mL', $html);
        $this->assertStringNotContainsString('NO DEBE MOSTRARSE', $html);
    }

    public function test_it_keeps_the_oncology_distributor_in_the_same_document(): void
    {
        $medicine = (object) [
            'unidad_cobro' => 'mg', 'denominacion_doc' => 'Oxaliplatino', 'dosis' => 10,
            'diluyente' => null, 'presentacionesUsadas' => collect(), 'presentation_charge_lines' => collect(),
            'cantidad_cobro' => 2, 'precio_unitario_calculado' => 5, 'subtotal_calculado' => 10,
        ];
        $html = $this->renderRemission($medicine, (object) [
            'nombre' => 'Distribuidor oncológico', 'direccion' => 'Dirección de prueba',
            'informacion_adicional' => null,
        ]);

        $this->assertSame(1, substr_count($html, 'class="document"'));
        $this->assertStringContainsString('Distribuidor oncológico', $html);
        $this->assertStringContainsString('Dirección de prueba', $html);
    }

    public function test_it_keeps_each_unit_when_bottle_and_milligram_charges_are_mixed(): void
    {
        $bottle = (object) [
            'unidad_cobro' => 'frasco', 'denominacion_doc' => 'Medicamento por frasco', 'dosis' => 100,
            'diluyente' => null, 'presentacionesUsadas' => collect(),
            'presentation_charge_lines' => collect([
                ['presentation' => 'Frasco 100 mg', 'quantity' => 1, 'unit_price' => 500, 'subtotal' => 500, 'warehouse' => 'Principal'],
            ]),
        ];
        $milligrams = (object) [
            'unidad_cobro' => 'mg', 'denominacion_doc' => 'Medicamento por mg', 'dosis' => 25,
            'diluyente' => null, 'presentacionesUsadas' => collect(), 'presentation_charge_lines' => collect(),
            'cantidad_cobro' => 25, 'precio_unitario_calculado' => 2, 'subtotal_calculado' => 50,
        ];

        $html = $this->renderRemission([$bottle, $milligrams]);

        $this->assertStringContainsString('DETALLE DE MEZCLAS Y COBRO', $html);
        $this->assertStringContainsString('Frasco 100 mg', $html);
        $this->assertStringContainsString('<td>mg</td><td>NA</td><td>25</td>', $html);
    }

    private function renderRemission(object|array $medicine, ?object $distributor = null): string
    {
        $medicines = is_array($medicine) ? collect($medicine) : collect([$medicine]);
        $mixture = (object) [
            'remision' => 'REM-001', 'lote' => 'LOTE-001', 'volumen_dilucion' => 250,
            'medicamentos' => $medicines, 'additional_charge_lines' => collect(),
            'infusor_aplica' => false, 'mixing_service_total' => 440,
        ];
        $request = (object) [
            'nombre_paciente' => 'Paciente de prueba', 'fecha_nacimiento' => '1987-04-25', 'edad' => 39,
            'sexo' => 'F', 'alergias' => null, 'diagnostico' => 'Diagnóstico', 'servicio' => 'Oncología',
            'registro_paciente' => 'EXP-1', 'nombre_medico' => 'Médico', 'observaciones' => null,
            'created_at' => '2026-09-10 13:04:00',
        ];

        return view('pdfs.oncologicos.remision', [
            'solicitud' => $request, 'mezclas' => collect([$mixture]), 'fechaEmision' => '2026-09-11 10:43:00',
            'totalRemision' => 715, 'priceList' => null, 'distributor' => $distributor, 'subdistributorOnly' => false,
        ])->render();
    }
}
