<?php

namespace Tests\Unit;

use App\Exports\Instituciones\InstitutionBillingExport;
use App\Models\Oncologicos\MedicinesCatalog;
use App\Models\Oncologicos\MedicineOnco;
use App\Models\Oncologicos\MezclaMedicamento;
use App\Models\Nutricionales\Solicitud as NutricionalSolicitud;
use App\Services\InstitutionBillingPricingService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class InstitutionBillingPricingServiceTest extends TestCase
{
    public function test_it_splits_an_iva_included_price(): void
    {
        $pricing = new InstitutionBillingPricingService();

        $this->assertSame([
            'base' => 100.0,
            'vat' => 16.0,
            'total' => 116.0,
        ], $pricing->splitIncludedVat(116.0));
    }

    public function test_it_parses_formatted_money_values(): void
    {
        $pricing = new InstitutionBillingPricingService();

        $this->assertSame(398521.75, $pricing->parseMoney('$398,521.75'));
        $this->assertSame(0.0, $pricing->parseMoney(null));
    }

    public function test_it_calculates_iva_from_a_base_price_only_when_selected(): void
    {
        $pricing = new InstitutionBillingPricingService();

        $this->assertSame(16.0, $pricing->calculateVatFromBase(100.0, true));
        $this->assertSame(0.0, $pricing->calculateVatFromBase(100.0, false));
    }

    public function test_it_uses_recorded_bottles_and_calculates_a_fallback_from_the_dose(): void
    {
        $pricing = new InstitutionBillingPricingService();
        $resolver = new ReflectionMethod($pricing, 'resolveBottleQuantity');
        $medicine = (object) [
            'dosis' => 230,
            'medicamentoOnco' => null,
        ];

        $this->assertSame(3.0, $resolver->invoke(
            $pricing,
            $medicine,
            new Collection([(object) ['unidades_usadas' => 3]]),
            null
        ));

        $this->assertSame(3.0, $resolver->invoke(
            $pricing,
            $medicine,
            new Collection(),
            (object) [
                'contenido_valor' => 100,
                'contenido_unidad' => 'mg',
                'cantidad_medicamento' => null,
                'presentacion' => 'Frasco ampula 100mg',
            ]
        ));
    }

    public function test_explicit_presentation_charge_method_controls_remission_pricing(): void
    {
        $pricing = new InstitutionBillingPricingService();
        $resolver = new ReflectionMethod($pricing, 'resolveOncoChargeMethod');

        $this->assertSame('frasco', $resolver->invoke(
            $pricing,
            (object) [
                'charge_by' => 'frasco',
                'precio_mg_override' => 2.5,
            ],
            (object) ['charge_by' => 'mg']
        ));

        $this->assertSame('mg', $resolver->invoke(
            $pricing,
            (object) ['charge_by' => 'mg'],
            (object) ['charge_by' => 'frasco']
        ));
    }

    public function test_remission_total_is_calculated_by_bottle_when_the_presentation_requires_it(): void
    {
        $pricing = new InstitutionBillingPricingService();
        $catalog = (new MedicinesCatalog())->forceFill([
            'id' => 9,
            'denominacion' => 'Medicamento de prueba',
        ]);
        $catalog->setRelation('presentations', collect());
        $medicine = (new MedicineOnco())->forceFill([
            'catalog_id' => 9,
            'precio' => 100,
            'precio_mg' => 5,
        ]);
        $medicine->setRelation('catalog', $catalog);

        $mixtureMedicine = (new MezclaMedicamento())->forceFill([
            'dosis' => 120,
            'charge_by' => 'mg',
            'precio_mg_snapshot' => 5,
        ]);
        $mixtureMedicine->setRelation('medicamentoOnco', $medicine);
        $mixtureMedicine->setRelation('presentacionesUsadas', collect());

        $config = (object) [
            'medicine_presentation_id' => 11,
            'charge_by' => 'frasco',
            'precio' => 100,
            'precio_mg_override' => 5,
            'iva_desglosado' => false,
            'descripcion_remision' => 'Medicamento configurado por frasco',
            'contenido_valor' => 50,
            'contenido_unidad' => 'mg',
            'cantidad_medicamento' => 50,
            'presentacion' => 'Frasco 50 mg',
        ];

        $configCache = new ReflectionProperty($pricing, 'oncoPresentationConfigs');
        $configCache->setValue($pricing, ['1:9' => collect([$config])]);

        $resolver = new ReflectionMethod($pricing, 'resolveOncoMedication');
        $line = $resolver->invoke($pricing, $mixtureMedicine, 1);

        $this->assertSame('frasco', $line['unit_label']);
        $this->assertSame(3.0, $line['quantity']);
        $this->assertSame(100.0, $line['unit_price']);
        $this->assertSame(300.0, $line['subtotal']);
    }

    public function test_bottle_remission_exposes_each_used_presentation_as_an_independent_line(): void
    {
        $pricing = new InstitutionBillingPricingService();
        $resolver = new ReflectionMethod($pricing, 'resolveBottlePricing');
        $usedPresentations = collect([
            (object) [
                'unidades_usadas' => 2,
                'subtotal' => 200,
                'precio_frasco_snapshot' => 100,
                'presentacion_snapshot' => 'Frasco 100 mg',
                'batch' => null,
                'presentation' => null,
            ],
            (object) [
                'unidades_usadas' => 1,
                'subtotal' => 75,
                'precio_frasco_snapshot' => 75,
                'presentacion_snapshot' => 'Frasco 60 mg',
                'batch' => null,
                'presentation' => null,
            ],
        ]);

        [, , $subtotal, , , $lines] = $resolver->invoke(
            $pricing,
            (object) [],
            $usedPresentations,
            collect()
        );

        $this->assertSame(275.0, $subtotal);
        $this->assertSame('Frasco 100 mg', $lines[0]['presentation']);
        $this->assertSame(2.0, $lines[0]['quantity']);
        $this->assertSame(200.0, $lines[0]['subtotal']);
        $this->assertSame('Frasco 60 mg', $lines[1]['presentation']);
        $this->assertSame(1.0, $lines[1]['quantity']);
        $this->assertSame(75.0, $lines[1]['subtotal']);
    }

    public function test_bottle_remission_uses_the_configured_warehouse_when_no_batch_was_recorded(): void
    {
        $pricing = new InstitutionBillingPricingService();
        $resolver = new ReflectionMethod($pricing, 'resolveBottlePricing');
        $config = (object) [
            'precio' => 100,
            'cantidad_medicamento' => 50,
            'presentacion' => 'Frasco 50 mg',
            'iva_desglosado' => false,
        ];

        [, , , , , $lines] = $resolver->invoke(
            $pricing,
            (object) ['dosis' => 75, 'medicamentoOnco' => null],
            collect(),
            collect([$config]),
            'Almacén principal'
        );

        $this->assertSame('Almacén principal', $lines[0]['warehouse']);
    }

    public function test_nutrition_bottle_quantity_uses_the_number_of_required_presentations(): void
    {
        $pricing = new InstitutionBillingPricingService();
        $resolver = new ReflectionMethod($pricing, 'nutritionBottleQuantity');
        $request = new NutricionalSolicitud();
        $request->setRelation('solicitud_detail', (object) ['sobrellenado_ml' => 0]);
        $item = (object) [
            'valor_ml' => 260,
            'valor_sobrellenado' => null,
            'presentation' => (object) ['presentacion_ml' => 100],
        ];

        $this->assertSame(3.0, $resolver->invoke($pricing, $request, $item));
    }

    public function test_nutrition_uses_the_canonical_price_per_ml_instead_of_an_old_line_total(): void
    {
        $pricing = new InstitutionBillingPricingService();
        $resolver = new ReflectionMethod($pricing, 'nutritionUnitPrice');

        $unitPrice = $resolver->invoke(
            $pricing,
            (object) ['precio_ml' => 250, 'presentation' => (object) ['presentacion_ml' => 500]],
            (object) ['precio_ml' => 2.5],
            'ml'
        );

        $this->assertSame(2.5, $unitPrice);
    }

    public function test_legacy_oncology_milliliter_records_keep_their_historical_total(): void
    {
        $pricing = new InstitutionBillingPricingService();
        $catalog = (new MedicinesCatalog())->forceFill([
            'id' => 12,
            'denominacion' => 'Medicamento por mililitro',
        ]);
        $catalog->setRelation('presentations', collect());
        $medicine = (new MedicineOnco())->forceFill(['catalog_id' => 12]);
        $medicine->setRelation('catalog', $catalog);

        $mixtureMedicine = (new MezclaMedicamento())->forceFill([
            'dosis' => 100,
            'charge_by' => 'ml',
            'precio_ml_snapshot' => 25.5,
        ]);
        $mixtureMedicine->setRelation('medicamentoOnco', $medicine);
        $mixtureMedicine->setRelation('presentacionesUsadas', collect([
            (object) ['volumen_usado_ml' => 4.25],
            (object) ['volumen_usado_ml' => 1.75],
        ]));

        $config = (object) [
            'medicine_presentation_id' => 20,
            'charge_by' => 'ml',
            'precio' => 0,
            'precio_ml_override' => 25.5,
            'iva_desglosado' => false,
            'descripcion_remision' => 'Medicamento por mL',
        ];

        $configCache = new ReflectionProperty($pricing, 'oncoPresentationConfigs');
        $configCache->setValue($pricing, ['1:12' => collect([$config])]);

        $resolver = new ReflectionMethod($pricing, 'resolveOncoMedication');
        $line = $resolver->invoke($pricing, $mixtureMedicine, 1);

        $this->assertSame('mL', $line['unit_label']);
        $this->assertSame(6.0, $line['quantity']);
        $this->assertSame(25.5, $line['unit_price']);
        $this->assertSame(153.0, $line['subtotal']);
    }

    public function test_mixture_line_total_uses_the_ml_snapshot(): void
    {
        $medicine = (new MezclaMedicamento())->forceFill([
            'charge_by' => 'ml',
            'dosis_ml' => 7.5,
            'precio_ml_snapshot' => 18.4,
        ]);

        $this->assertSame(138.0, $medicine->total());
    }

    public function test_standard_billing_export_includes_the_bottle_quantity_column(): void
    {
        $headings = (new InstitutionBillingExport([]))->headings();

        $this->assertSame('Cantidad de Frascos', $headings[7]);
        $this->assertCount(19, $headings);
    }
}
