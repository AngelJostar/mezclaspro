<?php

namespace Tests\Unit;

use App\Services\SolicitudOperativeStatusService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SolicitudOperativeStatusServiceTest extends TestCase
{
    #[DataProvider('statusCombinations')]
    public function test_it_resolves_the_request_status_from_all_its_mixes(array $mixStates, string $expected): void
    {
        $this->assertSame($expected, SolicitudOperativeStatusService::resolve($mixStates));
    }

    public static function statusCombinations(): array
    {
        return [
            'without mixes' => [[], 'pendiente'],
            'all pending' => [['pendiente', 'pendiente'], 'pendiente'],
            'waits for every approval' => [['aprobada', 'pendiente'], 'pendiente'],
            'all approved' => [['aprobada', 'aprobada'], 'aprobada'],
            'waits for every preparation' => [['preparada', 'aprobada'], 'aprobada'],
            'all prepared' => [['preparada', 'preparada'], 'preparada'],
            'waits for every inspection' => [['revisada', 'preparada'], 'preparada'],
            'all inspected' => [['revisada', 'revisada'], 'revisada'],
            'waits for every delivery' => [['entregada', 'revisada'], 'revisada'],
            'all delivered' => [['entregada', 'entregada'], 'entregada'],
            'ignores a cancelled mix when active mixes remain' => [['cancelada', 'preparada'], 'preparada'],
            'all cancelled' => [['cancelada', 'cancelada'], 'cancelada'],
            'normalizes legacy states' => [['enproceso', 'finalizada'], 'preparada'],
        ];
    }
}
