<?php

namespace Tests\Unit;

use App\Services\InstitutionBillingDueDateService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class InstitutionBillingDueDateServiceTest extends TestCase
{
    private InstitutionBillingDueDateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new InstitutionBillingDueDateService();
    }

    public function test_it_does_not_mark_a_delivery_from_the_current_month(): void
    {
        $result = $this->service->calculate('2026-08-03', null, CarbonImmutable::parse('2026-08-10'));

        $this->assertSame(['status' => 'none', 'days' => null], $result);
    }

    public function test_it_starts_the_yellow_count_on_the_first_day_of_the_next_month(): void
    {
        $firstDay = $this->service->calculate('2026-07-18', null, CarbonImmutable::parse('2026-08-01'));
        $tenthDay = $this->service->calculate('2026-07-18', null, CarbonImmutable::parse('2026-08-10'));

        $this->assertSame(['status' => 'yellow', 'days' => 1], $firstDay);
        $this->assertSame(['status' => 'yellow', 'days' => 10], $tenthDay);
    }

    public function test_it_turns_red_in_the_second_calendar_month_without_resetting_the_days(): void
    {
        $result = $this->service->calculate('2026-06-18', null, CarbonImmutable::parse('2026-08-14'));

        $this->assertSame(['status' => 'red', 'days' => 45], $result);
    }

    public function test_it_does_not_mark_completed_billing_as_expired(): void
    {
        $result = $this->service->calculate('2026-05-18', 'Completado', CarbonImmutable::parse('2026-08-10'));

        $this->assertSame(['status' => 'none', 'days' => null], $result);
    }
}
