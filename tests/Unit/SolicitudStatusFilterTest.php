<?php

namespace Tests\Unit;

use App\Support\SolicitudStatusFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SolicitudStatusFilterTest extends TestCase
{
    #[DataProvider('statusCases')]
    public function test_it_classifies_operational_statuses(
        string $filter,
        ?string $status,
        bool $isInRoute,
        bool $expected
    ): void {
        $this->assertSame($expected, SolicitudStatusFilter::matches($filter, $status, $isInRoute));
    }

    public static function statusCases(): array
    {
        return [
            'all includes any state' => [SolicitudStatusFilter::ALL, 'cancelada', false, true],
            'null state is pending' => [SolicitudStatusFilter::PENDING, null, false, true],
            'approved request is in preparation' => [SolicitudStatusFilter::PREPARATION, 'aprobada', false, true],
            'dispensed request is in preparation' => [SolicitudStatusFilter::PREPARATION, 'dispensada', false, true],
            'scheduled request leaves preparation' => [SolicitudStatusFilter::PREPARATION, 'preparada', true, false],
            'scheduled active request is in route' => [SolicitudStatusFilter::IN_ROUTE, 'revisada', true, true],
            'delivered request is no longer in route' => [SolicitudStatusFilter::IN_ROUTE, 'entregada', true, false],
            'legacy final state is delivered' => [SolicitudStatusFilter::DELIVERED, 'finalizada', false, true],
            'delivered request appears in history' => [SolicitudStatusFilter::HISTORY, 'entregada', false, true],
            'cancelled request appears in history' => [SolicitudStatusFilter::HISTORY, 'cancelada', false, true],
            'legacy rejected request appears in history' => [SolicitudStatusFilter::HISTORY, 'no-aprobada', false, true],
            'pending request is not historical' => [SolicitudStatusFilter::HISTORY, 'pendiente', false, false],
        ];
    }

    public function test_it_falls_back_to_all_for_an_unknown_filter(): void
    {
        $this->assertSame(SolicitudStatusFilter::ALL, SolicitudStatusFilter::normalize('desconocido'));
    }
}
