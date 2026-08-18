<?php

namespace Tests\Unit;

use App\Services\InstitutionBillingDueDateService;
use App\Services\InstitutionBillingPendingSummaryService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class InstitutionBillingPendingSummaryServiceTest extends TestCase
{
    public function test_it_counts_yellow_and_red_pending_billings(): void
    {
        CarbonImmutable::setTestNow('2026-08-10');

        try {
            $service = new InstitutionBillingPendingSummaryService(
                new InstitutionBillingDueDateService()
            );

            $counts = $service->summarize([
                ['delivery_date' => '2026-07-18', 'billing_status' => null],
                ['delivery_date' => '2026-07-31', 'billing_status' => 'Pendiente'],
                ['delivery_date' => '2026-06-18', 'billing_status' => null],
                ['delivery_date' => '2026-05-02', 'billing_status' => 'Completado'],
                ['delivery_date' => '2026-08-03', 'billing_status' => null],
            ]);

            $this->assertSame(['yellow' => 2, 'red' => 1], $counts);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
