<?php

namespace Tests\Unit;

use App\Support\MixtureIntegrationStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MixtureIntegrationStatusTest extends TestCase
{
    #[DataProvider('statuses')]
    public function test_it_exposes_cbta_statuses_as_the_canonical_vocabulary(string $source, string $canonical, string $label): void
    {
        $this->assertSame($canonical, MixtureIntegrationStatus::fromCbta($source));
        $this->assertSame($label, MixtureIntegrationStatus::label($canonical));
    }

    public static function statuses(): array
    {
        return [
            ['pendiente', 'pending', 'Pendiente'],
            ['aprobada', 'authorized', 'Aprobada'],
            ['dispensada', 'dispensed', 'Dispensada'],
            ['preparada', 'preparing', 'Preparada'],
            ['revisada', 'ready', 'Inspeccionada'],
            ['en_ruta', 'in_route', 'En ruta'],
            ['entregada', 'delivered', 'Entregada'],
            ['no_aprobada', 'rejected', 'No aprobada'],
            ['cancelada', 'cancelled', 'Cancelada'],
        ];
    }
}
