<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\CatalogoListasController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CatalogoListasFormattingTest extends TestCase
{
    public function test_measurements_hide_trailing_decimals_without_losing_real_fractions(): void
    {
        $method = new ReflectionMethod(CatalogoListasController::class, 'doseLabel');
        $controller = new CatalogoListasController();

        $this->assertSame('50 mg', $method->invoke($controller, 50.0, 'mg'));
        $this->assertSame('1,200 mg', $method->invoke($controller, 1200.0, 'mg'));
        $this->assertSame('0.5 mg', $method->invoke($controller, 0.5, 'mg'));
    }
}
