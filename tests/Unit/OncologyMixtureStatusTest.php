<?php

namespace Tests\Unit;

use App\Models\Oncologicos\Mezcla;
use App\Models\Oncologicos\SolicitudOnco;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OncologyMixtureStatusTest extends TestCase
{
    #[DataProvider('statusCases')]
    public function test_it_resolves_the_status_shown_for_each_mixture(
        ?string $requestStatus,
        ?string $mixtureStatus,
        string $expected
    ): void {
        $request = new SolicitudOnco(['estado' => $requestStatus]);
        $mixture = new Mezcla(['estado' => $mixtureStatus]);
        $mixture->setRelation('solicitud', $request);

        $this->assertSame($expected, $mixture->operational_status);
    }

    public static function statusCases(): array
    {
        return [
            'mixture has its own state' => ['aprobada', 'preparada', 'preparada'],
            'dispensed mixture has its own state' => ['aprobada', 'dispensada', 'dispensada'],
            'blank mixture is pending' => ['pendiente', null, 'pendiente'],
            'cancelled request overrides mixture' => ['cancelada', 'preparada', 'cancelada'],
            'rejected request overrides mixture' => ['no_aprobada', 'aprobada', 'no_aprobada'],
            'legacy rejected request overrides mixture' => ['no-aprobada', 'revisada', 'no-aprobada'],
        ];
    }
}
