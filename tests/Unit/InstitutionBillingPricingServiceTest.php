<?php

namespace Tests\Unit;

use App\Exports\Instituciones\InstitutionBillingExport;
use App\Services\InstitutionBillingPricingService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

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

    public function test_standard_billing_export_includes_the_bottle_quantity_column(): void
    {
        $headings = (new InstitutionBillingExport([]))->headings();

        $this->assertSame('Cantidad de Frascos', $headings[7]);
        $this->assertCount(19, $headings);
    }
}
