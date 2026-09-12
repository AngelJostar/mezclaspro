<?php

namespace Tests\Feature;

use Tests\TestCase;

class NutritionRemissionTemplateTest extends TestCase
{
    public function test_it_renders_the_nutrition_bottle_template_with_the_presentation(): void
    {
        $html = $this->renderRemission('frasco');

        $this->assertStringContainsString('COBRO POR PRESENTACIÓN', $html);
        $this->assertStringNotContainsString('PROPUESTA DE FORMATO', $html);
        $this->assertStringContainsString('Frasco 500 mL', $html);
        $this->assertStringContainsString('$120.00', $html);
        $this->assertStringContainsString('Servicio de mezclado', $html);
        $this->assertStringContainsString('$440.00', $html);
        $this->assertStringNotContainsString('VOLUMEN TOTAL DE LA MEZCLA', $html);
    }

    public function test_it_renders_the_nutrition_milliliter_template_without_the_presentation(): void
    {
        $html = $this->renderRemission('mL');

        $this->assertStringContainsString('COBRO POR MILILITRO', $html);
        $this->assertStringContainsString('250', $html);
        $this->assertStringNotContainsString('<th style="width:10%">Diluyente</th>', $html);
        $this->assertStringContainsString('Volumen mezcla', $html);
        $this->assertStringContainsString('250 mL', $html);
        $this->assertStringNotContainsString('Frasco 500 mL', $html);
    }

    public function test_it_keeps_distributor_information_in_the_same_document(): void
    {
        $html = $this->renderRemission('mL', (object) [
            'nombre' => 'Distribuidor de prueba',
            'direccion' => 'Dirección de prueba',
            'informacion_adicional' => null,
            'logo_path' => null,
        ]);

        $this->assertSame(1, substr_count($html, 'class="document"'));
        $this->assertStringContainsString('Distribuidor de prueba', $html);
        $this->assertStringContainsString('Dirección de prueba', $html);
    }

    private function renderRemission(string $unit, ?object $distributor = null): string
    {
        $catalog = (object) ['denominacion_generica' => 'Aminoácidos', 'presentations' => collect()];
        $presentation = (object) [
            'presentacion' => 'Frasco 500 mL', 'denominacion_comercial' => 'Marca prueba',
            'catalog' => $catalog, 'listItems' => collect(),
        ];
        $item = (object) [
            'id' => 10, 'valor' => 25, 'input' => (object) ['unidad' => 'g', 'description' => 'Aminoácidos', 'nutritionMedicineCatalog' => $catalog],
            'presentation' => $presentation,
        ];
        $detail = (object) [
            'npt' => 'ADULT', 'fecha_hora_entrega' => '2026-09-11 10:43:00', 'nombre_medico' => 'Médico',
            'volumen_total_final' => 250, 'observaciones' => null,
        ];
        $patient = (object) [
            'nombre_paciente' => 'Paciente', 'apellidos_paciente' => 'Nutricional', 'fecha_nacimiento' => '1987-04-25',
            'edad' => 39, 'sexo' => 'F', 'diagnostico' => 'Diagnóstico', 'servicio' => 'Nutrición', 'registro' => 'EXP-1',
        ];
        $request = (object) [
            'id' => 1, 'remision' => 'REM-N-1', 'lote' => 'LOTE-N-1', 'created_at' => '2026-09-10 13:04:00',
            'solicitud_detail' => $detail, 'solicitud_patient' => $patient,
        ];

        return view('pdfs.nutricionales.remision', [
            'solicitud_detalles' => $request, 'inputs_solicitud' => collect([$item]), 'bolsa_eva' => null,
            'set_infusion' => null, 'imprimirMarcas' => false, 'distributor' => $distributor, 'priceList' => null,
            'pricingSummary' => ['lines' => collect([['unit_label' => $unit, 'quantity' => $unit === 'frasco' ? 1 : 250, 'unit_price' => $unit === 'frasco' ? 120 : .48, 'subtotal' => 120]]), 'supply_lines' => collect(), 'additional_charge_lines' => collect(), 'service_total' => 440, 'total_iva_included' => 560],
            'almacenesPorSolicitudInput' => collect([10 => 'Almacén principal']),
        ])->render();
    }
}
