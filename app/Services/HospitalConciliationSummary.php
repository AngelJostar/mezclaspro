<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class HospitalConciliationSummary
{
    public function __construct(private InstitutionBillingPricingService $pricing) {}

    public function snapshot(Collection $rows): array
    {
        return $rows->map(fn ($row) => [
            'kind' => $row['kind'], 'id' => $row['id'], 'cells' => $row['cells'],
            'conciliable' => $row['conciliable'], 'amount_cents' => $this->amount($row), 'reason' => null,
            'adjustment' => $row['target']->currentAdjustment()?->log_label ?? 'Sin Ajustes',
        ])->values()->all();
    }

    private function amount(array $row): ?int
    {
        $captured = trim((string) ($row['billing']?->precio_total ?? ''));
        if ($captured !== '') {
            // Billing accepts currency-formatted overrides; invalid values must not become zero.
            if (! preg_match('/^\$?\s*(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d{1,2})?$/D', $captured)) return null;
            return HospitalInvoiceLedger::cents(str_replace(['$', ',', ' '], '', $captured));
        }
        $price = $row['kind'] === 'nutricionales'
            ? $this->pricing->priceNutritionRequest($row['target'])
            : $this->pricing->priceOncoMix($row['target']);
        $amount = $price['total_iva_included'] ?? null;
        return is_numeric($amount) && $amount > 0
            ? HospitalInvoiceLedger::cents(number_format((float) $amount, 2, '.', '')) : null;
    }

    public static function summarize(array $snapshot, string $hospital, ?string $from, ?string $to): array
    {
        $rows = collect($snapshot);
        $group = static function (Collection $items): array {
            $missing = $items->filter(fn ($row) => !isset($row['amount_cents']))->count();
            return ['count' => $items->count(), 'amount_cents' => $missing ? null : $items->sum('amount_cents'), 'missing_amounts' => $missing];
        };
        $yes = $rows->filter(fn ($row) => $row['conciliable'] ?? ($row['cells']['conciliable'] !== 'No'));
        $no = $rows->reject(fn ($row) => $row['conciliable'] ?? ($row['cells']['conciliable'] !== 'No'));
        $date = fn ($value) => Carbon::parse($value)->locale('es')->translatedFormat('d \d\e F \d\e Y');
        $period = !$from && !$to ? 'Todo el historial' : ($from ? 'Del '.$date($from) : 'Sin fecha inicial')
            .($to ? ' al '.$date($to) : ' · Sin fecha final');
        if ($from && $from === $to) $period = $date($from);
        elseif ($from && $to && substr($from, 0, 7) === substr($to, 0, 7)) $period = 'Del '.Carbon::parse($from)->format('d').' al '.$date($to);
        return [
            'hospital' => $hospital, 'provider' => 'PROMESA · Prodifem Mezclas Estériles', 'period' => $period,
            'total' => $group($rows), 'yes' => $group($yes), 'no' => $group($no),
            'non_conciliable' => $no->map(fn ($row) => [
                'key' => $row['kind'].'-'.$row['id'], 'label' => $row['cells']['type'].' · Mezcla #'.$row['id'],
                'reason' => $row['reason'] ?? '',
            ])->values()->all(),
        ];
    }

    public static function signature(array $value): string
    {
        $canonical = static function (array $data) use (&$canonical): array {
            ksort($data);
            foreach ($data as &$item) if (is_array($item)) $item = $canonical($item);
            return $data;
        };
        return hash_hmac('sha256', json_encode($canonical($value), JSON_THROW_ON_ERROR), config('app.key'));
    }

    public static function money(?int $cents): string
    {
        return $cents === null ? 'Sin registrar' : '$'.number_format($cents / 100, 2).' MXN';
    }
}
