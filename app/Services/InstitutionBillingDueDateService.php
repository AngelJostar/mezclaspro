<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;

class InstitutionBillingDueDateService
{
    /**
     * @return array{status: 'none'|'yellow'|'red', days: int|null}
     */
    public function calculate(
        CarbonInterface|DateTimeInterface|string|null $deliveryDate,
        ?string $billingStatus = null,
        CarbonInterface|DateTimeInterface|string|null $asOf = null
    ): array {
        if (! $deliveryDate || $this->isCompleted($billingStatus)) {
            return $this->withoutExpiration();
        }

        $today = $asOf
            ? CarbonImmutable::parse($asOf)->startOfDay()
            : CarbonImmutable::today();
        $countFrom = $this->dueDate($deliveryDate, $billingStatus)->addDay();

        if ($today->lessThan($countFrom)) {
            return $this->withoutExpiration();
        }

        return [
            'status' => $today->greaterThanOrEqualTo($countFrom->addMonth()) ? 'red' : 'yellow',
            'days' => $countFrom->diffInDays($today) + 1,
        ];
    }

    public function dueDate(CarbonInterface|DateTimeInterface|string|null $deliveryDate, ?string $billingStatus = null): ?CarbonImmutable
    {
        return ! $deliveryDate || $this->isCompleted($billingStatus)
            ? null : CarbonImmutable::parse($deliveryDate)->endOfMonth()->startOfDay();
    }

    private function isCompleted(?string $billingStatus): bool
    {
        return mb_strtolower(trim((string) $billingStatus)) === 'completado';
    }

    /**
     * @return array{status: 'none', days: null}
     */
    private function withoutExpiration(): array
    {
        return [
            'status' => 'none',
            'days' => null,
        ];
    }
}
