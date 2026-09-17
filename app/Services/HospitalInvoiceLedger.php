<?php

namespace App\Services;

use App\Models\HospitalInvoiceAccount;
use App\Models\InstitutionBilling;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HospitalInvoiceLedger
{
    public function __construct(private InstitutionBillingDueDateService $dueDates) {}

    public const STATES = ['all' => 'Todas', 'pending' => 'Pendientes', 'partial' => 'Pago parcial', 'paid' => 'Pagadas', 'overdue' => 'Vencidas'];
    public const CLARIFICATIONS = ['none' => 'Sin aclaración', 'review' => 'En revisión', 'resolved' => 'Resuelta'];
    public const DOCUMENT_STATES = ['received' => 'Recibidas', 'signing' => 'En firma', 'delivered' => 'Entregadas'];

    public static function key($billing): string
    {
        $identity = trim((string) $billing->folio_factura_uuid);
        $identity = $identity !== '' ? 'uuid:'.strtoupper($identity) : 'folio:'.mb_strtoupper(trim((string) $billing->folio_interno));
        return hash('sha256', $billing->hospital_id.'|'.$billing->institucion_id.'|'.$identity);
    }

    public static function cents($value): ?int
    {
        // Legacy prices are strings. Unknown or ambiguous formats must not silently become zero.
        $value = trim((string) $value);
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/D', $value)) return null;
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        if (strlen($whole) > 12) return null;
        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function money(?int $cents): string
    {
        return $cents === null ? 'Sin registrar' : '$'.number_format($cents / 100, 2);
    }

    public function invoices(Collection $targets): Collection
    {
        $groups = $targets->filter(fn ($row) => $row['billing'] &&
            (trim((string) $row['billing']->folio_interno) !== '' || trim((string) $row['billing']->folio_factura_uuid) !== ''))
            ->groupBy(fn ($row) => self::key($row['billing']));
        // Never expose a whole invoice document or payment against a partially authorized invoice.
        $hospitalIds = $targets->pluck('billing.hospital_id')->filter()->unique();
        $lineCounts = InstitutionBilling::whereIn('hospital_id', $hospitalIds)->get()->countBy(fn ($billing) => self::key($billing));
        $groups = $groups->filter(fn ($lines, $key) => $lines->count() === $lineCounts->get($key));
        $accounts = HospitalInvoiceAccount::with('payments')->whereIn('invoice_key', $groups->keys())->get()->keyBy('invoice_key');
        return $groups->map(function ($lines, $key) use ($accounts) {
            $billing = $lines->first()['billing'];
            $account = $accounts->get($key);
            $amounts = $lines->map(fn ($line) => self::cents($line['billing']->precio_total));
            $total = $amounts->containsStrict(null) ? null : $amounts->sum();
            $payments = $account?->payments ?? collect();
            $paid = $payments->filter(fn ($p) => $p->status === 'approved' && $p->reviewed_at)
                ->sum(fn ($p) => self::cents($p->amount) ?? 0);
            $balance = $total === null ? null : max(0, $total - $paid);
            $deadlines = $lines->map(fn ($line) => $this->dueDates->dueDate($line['delivery_date'], $line['billing']->estatus_facturacion))->filter();
            $due = $deadlines->sort()->first();
            $expirations = $lines->map(fn ($line) => $this->dueDates->calculate($line['delivery_date'], $line['billing']->estatus_facturacion));
            $days = $expirations->max('days') ?? 0;
            $overdue = $balance > 0 && $days > 0;
            $date = $this->date($billing->fecha_facturacion);
            return [
                'key' => $key, 'billing' => $billing, 'account' => $account, 'lines' => $lines->values(),
                'document_state' => $account?->document_status ?? 'received',
                'folio' => $billing->folio_interno ?: $billing->folio_factura_uuid, 'date' => $date, 'due' => $due,
                'total' => $total, 'paid' => $paid, 'balance' => $balance, 'overdue' => $overdue,
                'days_overdue' => $overdue ? $days : 0,
                'state' => $balance === null ? 'unknown' : ($balance === 0 ? 'paid' : ($paid > 0 ? 'partial' : 'pending')),
                'clarification' => $account?->clarification_status ?? 'none', 'payments' => $payments->sortByDesc('id')->values(),
                'pending_reports' => $payments->where('status', 'pending')->sum(fn ($p) => self::cents($p->amount) ?? 0),
            ];
        })->sortByDesc('date')->values();
    }

    public function filter(Collection $invoices, array $filters, string $from, string $to): array
    {
        $invoices = $invoices->filter(fn ($r) => (! $from || ($r['date'] && $r['date']->toDateString() >= $from))
            && (! $to || ($r['date'] && $r['date']->toDateString() <= $to)));
        $documentState = $filters['tramite_estado'] ?? 'received';
        if ($documentState !== 'all') $invoices = $invoices->where('document_state', $documentState);
        $summary = [
            'total' => $invoices->sum('total'), 'paid' => $invoices->sum('paid'),
            'balance' => $invoices->sum('balance'), 'overdue' => $invoices->where('overdue', true)->sum('balance'),
            'incomplete' => $invoices->whereNull('total')->count(),
        ];
        $search = mb_strtolower(trim($filters['folio'] ?? ''));
        $clarification = $filters['aclaracion'] ?? 'all';
        $invoices = $invoices->filter(fn ($r) => ($search === '' || str_contains(mb_strtolower($r['folio']), $search))
            && ($clarification === 'all' || $r['clarification'] === $clarification));
        $counts = collect(self::STATES)->map(fn ($label, $key) => $invoices->filter(fn ($r) => $this->matches($r, $key))->count())->all();
        $rows = $invoices->filter(fn ($r) => $this->matches($r, $filters['pago_estado'] ?? 'all'))->values();
        return compact('rows', 'summary', 'counts');
    }

    private function matches(array $invoice, string $state): bool
    {
        return $state === 'all' || ($state === 'overdue' ? $invoice['overdue'] : $invoice['state'] === $state);
    }

    private function date(?string $value): ?Carbon
    {
        foreach (['Y-m-d', 'd/m/Y', 'Y-m-d H:i:s'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, (string) $value);
                if ($date && $date->format($format) === $value) return $date;
            } catch (\Throwable $e) { /* A missing legacy date remains unregistered. */ }
        }
        return null;
    }
}
