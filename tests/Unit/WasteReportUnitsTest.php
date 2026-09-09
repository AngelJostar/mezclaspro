<?php

namespace Tests\Unit;

use App\Support\WasteReportUnits;
use PHPUnit\Framework\TestCase;

class WasteReportUnitsTest extends TestCase
{
    public function test_totals_sum_each_unit_separately_and_preserve_decimals(): void
    {
        $total = WasteReportUnits::sum([
            ['mg' => 90, 'mezclas' => 1], ['mg' => 200, 'mezclas' => 1], ['mg' => 900, 'mezclas' => 1],
            ['mg' => 0.125], ['mL' => 10.25], ['mL' => 0.5, 'frascos' => 2],
        ]);
        $this->assertSame(['mg' => 1190.125, 'mezclas' => 3.0, 'mL' => 10.75, 'frascos' => 2.0], $total['quantities']);
        $this->assertSame(0, $total['missing']);
        $this->assertSame('1,190.13 mg · 10.75 mL · 2.00 frascos · 3.00 mezclas', WasteReportUnits::formatTotal($total));
    }

    public function test_unknown_quantities_are_flagged_and_empty_results_have_zero_totals(): void
    {
        $total = WasteReportUnits::sum([['mg' => null], ['mg' => 50.5]]);
        $this->assertSame(['quantities' => ['mg' => 50.5], 'missing' => 1], $total);
        $this->assertSame('50.50 mg · Sin cuantificar: 1', WasteReportUnits::formatTotal($total));
        $this->assertSame('Sin dato (mg)', WasteReportUnits::formatQuantities(['mg' => null]));
        $this->assertSame('0.00 unidades', WasteReportUnits::formatTotal(WasteReportUnits::sum([])));
    }
}
