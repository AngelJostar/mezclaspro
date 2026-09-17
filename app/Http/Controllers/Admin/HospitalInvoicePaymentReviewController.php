<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{HospitalInvoiceAccount, HospitalInvoicePayment, InstitutionBilling, InstitutionBillingMovement};
use App\Services\HospitalInvoiceLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HospitalInvoicePaymentReviewController extends Controller
{
    public function update(Request $request, HospitalInvoicePayment $payment)
    {
        abort_if($request->user()->hasAnyRole(['Cliente', 'Institucion']), 403);
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])]]);
        DB::transaction(function () use ($request, $payment, $data) {
            $account = HospitalInvoiceAccount::whereKey($payment->account_id)->lockForUpdate()->firstOrFail();
            $payment = HospitalInvoicePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless($payment->status === 'pending', 409, 'Este reporte ya fue revisado.');
            $lines = InstitutionBilling::where('hospital_id', $account->hospital_id)->where('institucion_id', $account->institucion_id)
                ->get()->filter(fn ($line) => HospitalInvoiceLedger::key($line) === $account->invoice_key);
            abort_if($lines->isEmpty(), 409, 'La factura cambio. Revisa sus datos antes de validar el pago.');
            if ($data['decision'] === 'approved') {
                $amounts = $lines->map(fn ($line) => HospitalInvoiceLedger::cents($line->precio_total));
                $paid = $account->payments()->where('status', 'approved')->whereNotNull('reviewed_at')->get()->sum(fn ($p) => HospitalInvoiceLedger::cents($p->amount));
                abort_if($amounts->containsStrict(null) || HospitalInvoiceLedger::cents($payment->amount) > $amounts->sum() - $paid, 422, 'El pago supera el saldo o falta registrar el importe de la factura.');
            }
            $payment->update(['status' => $data['decision'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
            $billing = $lines->first();
            InstitutionBillingMovement::create([
                'institution_billing_id' => $billing->id, 'user_id' => $request->user()->id, 'user_name' => $request->user()->name,
                'origen_tipo' => $billing->origen_tipo, 'origen_id' => $billing->origen_id,
                'from_stage' => $billing->workflowStage(), 'to_stage' => $billing->workflowStage(),
                'details' => ['source' => 'hospital_payment_reviewed', 'payment_id' => $payment->id,
                    'folio' => $billing->folio_interno, 'importe' => $payment->amount, 'referencia' => $payment->reference, 'decision' => $payment->status],
            ]);
        });
        return back()->with('success', 'Revision del pago guardada.');
    }
}
