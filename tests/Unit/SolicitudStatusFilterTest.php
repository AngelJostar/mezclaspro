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

    public function test_adjustment_requires_a_pending_version_and_an_unapproved_request(): void
    {
        $this->assertSame('en_ajuste', SolicitudStatusFilter::normalize('en_ajuste'));
        foreach (['pendiente', null, ''] as $state) {
            $this->assertTrue(SolicitudStatusFilter::matches('en_ajuste', $state, false, true));
            $this->assertFalse(SolicitudStatusFilter::matches('en_ajuste', $state));
        }
        foreach (['aprobada', 'dispensada', 'preparada', 'revisada', 'entregada', 'cancelada', 'no_aprobada'] as $state) {
            $this->assertFalse(SolicitudStatusFilter::matches('en_ajuste', $state, false, true));
        }
        $keys = array_keys(SolicitudStatusFilter::options());
        $index = array_search(SolicitudStatusFilter::ADJUSTMENT, $keys, true);
        $this->assertSame(SolicitudStatusFilter::MESSAGING, $keys[$index - 1]);
        $this->assertSame(SolicitudStatusFilter::PREPARATION, $keys[$index + 1]);
    }

    public function test_messaging_filter_requires_a_conversation_regardless_of_process_state(): void
    {
        $this->assertSame('mensajeria', SolicitudStatusFilter::normalize('mensajeria'));
        foreach ([null, 'pendiente', 'aprobada', 'entregada', 'cancelada'] as $state) {
            $this->assertTrue(SolicitudStatusFilter::matches('mensajeria', $state, false, false, true));
            $this->assertFalse(SolicitudStatusFilter::matches('mensajeria', $state));
        }
        $keys = array_keys(SolicitudStatusFilter::options());
        $index = array_search(SolicitudStatusFilter::MESSAGING, $keys, true);
        $this->assertSame(SolicitudStatusFilter::PENDING, $keys[$index - 1]);
        $this->assertSame(SolicitudStatusFilter::ADJUSTMENT, $keys[$index + 1]);
    }
}
