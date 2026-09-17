<?php

namespace Tests\Fixtures;

use App\Models\{InstitutionBilling, HospitalInvoiceAccount, HospitalInvoicePayment};
use App\Services\HospitalInvoiceLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HospitalInvoiceData
{
    public static function seed(): void
    {
        if (DB::getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') throw new \RuntimeException('In-memory fixtures only.');
        DB::table('institution_billings')->where('hospital_id', 1)->update(['folio_interno' => 'F-1051', 'folio_factura_uuid' => 'INVOICE-1', 'precio_total' => '40000.00', 'fecha_facturacion' => '2026-08-01']);
        DB::table('mezclas')->where('id', 1)->update(['fecha_entrega' => '2026-08-20 10:00:00']);
        DB::table('solicitud_details')->update(['fecha_hora_entrega' => '2026-08-20 10:00:00']);
        $first = InstitutionBilling::where('hospital_id', 1)->first();
        $account = HospitalInvoiceAccount::create(['invoice_key' => HospitalInvoiceLedger::key($first), 'hospital_id' => 1, 'institucion_id' => 1, 'clarification_status' => 'review']);
        self::payment($account, '40000.00', 'approved');
        foreach ([[20,'F-1052','60000.00','2026-08-20',0], [21,'F-1053','70000.00','2026-08-25',70000], [22,'F-1054','50000.00','2026-09-01',0], [23,'F-1055','40000.00','2026-09-05',40000]] as [$id,$folio,$amount,$date,$paid]) {
            DB::table('mezclas')->insert(['id'=>$id, 'solicitud_id'=>1, 'estado'=>'entregada', 'fecha_entrega'=>'2026-09-10 10:00:00']);
            $billing = InstitutionBilling::create(['origen_tipo'=>'oncologica_mezcla', 'origen_id'=>$id, 'institucion_id'=>1, 'hospital_id'=>1,
                'precio_total'=>$amount, 'folio_interno'=>$folio, 'fecha_facturacion'=>$date]);
            if ($paid) {
                $account = HospitalInvoiceAccount::create(['invoice_key'=>HospitalInvoiceLedger::key($billing), 'hospital_id'=>1, 'institucion_id'=>1, 'clarification_status'=>$id === 23 ? 'resolved' : 'none']);
                self::payment($account, (string) $paid, 'approved');
            }
        }
    }

    public static function payment($account, string $amount, string $status): HospitalInvoicePayment
    {
        return HospitalInvoicePayment::create(['account_id'=>$account->id, 'submission_key'=>(string) Str::uuid(), 'amount'=>$amount,
            'paid_at'=>'2026-09-10', 'reference'=>'Referencia de prueba', 'status'=>$status, 'submitted_by'=>1,
            'reviewed_by'=>$status === 'approved' ? 1 : null, 'reviewed_at'=>$status === 'approved' ? '2026-09-11 10:00:00' : null]);
    }
}
