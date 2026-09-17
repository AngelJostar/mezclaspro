<?php

namespace Tests\Feature;

use App\Models\{HospitalInvoiceAccount, HospitalInvoicePayment, InstitutionBilling, InstitutionBillingMovement};
use App\Services\{HospitalInvoiceLedger, InstitutionBillingDueDateService};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema, Storage};
use Illuminate\Support\Str;
use Tests\Fixtures\{HospitalToolsData, HospitalInvoiceData, UnifiedRequestExportData};
use Tests\TestCase;

class HospitalInvoicesTest extends TestCase
{
    private $hospital;
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite', 'database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        $this->travelTo(now()->setDate(2026, 9, 15)->setTime(12, 0));
        $this->hospital = UnifiedRequestExportData::seed();
        HospitalToolsData::addBilling();
        HospitalInvoiceData::seed();
        foreach (['users'=>'is_active','hospitals'=>'access_is_active','clientes'=>'is_active'] as $table=>$column) Schema::table($table, fn (Blueprint $s) => $s->boolean($column)->default(true));
        (require database_path('migrations/2024_05_21_124030_create_notifications_table.php'))->up();
        $this->actingAs($this->hospital->refresh());
    }
    private function page(array $query = [])
    {
        return $this->get(route('admin.hospital.herramientas', array_merge(['tab'=>'facturacion','desde'=>'2026-08-01','hasta'=>'2026-09-15'], $query)));
    }
    private function key(int $origin = 1): string
    {
        return HospitalInvoiceLedger::key(InstitutionBilling::where('origen_tipo','oncologica_mezcla')->where('origen_id',$origin)->firstOrFail());
    }
    private function report(array $data = [], ?string $key = null)
    {
        return $this->postJson(route('admin.hospital.facturacion.pago', $key ?? $this->key()), array_merge([
            'amount'=>'10000.00','paid_at'=>'2026-09-14','reference'=>'TEST-REF','submission_key'=>(string) Str::uuid(),
        ], $data));
    }
    public function test_invoices_group_mixtures_and_filter_by_emission_not_request_date(): void
    {
        $response = $this->page()->assertOk()->assertDontSee('FACTURA-AJENA')->assertSee('Seguimiento de facturación');
        $response->assertViewHas('rows', fn ($rows) => $rows->total() === 5);
        $response->assertViewHas('summary', ['total'=>30000000,'paid'=>15000000,'balance'=>15000000,'overdue'=>4000000,'incomplete'=>0]);
        $response->assertViewHas('counts', ['all'=>5,'pending'=>2,'partial'=>1,'paid'=>2,'overdue'=>1]);
        $this->page(['desde'=>'2026-08-01','hasta'=>'2026-08-01'])->assertViewHas('rows', fn ($r) => $r->total() === 1 && $r[0]['lines']->count() === 2);
        foreach (['pending'=>2,'partial'=>1,'paid'=>2,'overdue'=>1] as $state=>$count) $this->page(['pago_estado'=>$state])->assertViewHas('rows', fn ($r) => $r->total() === $count);
        $this->page(['folio'=>'1055','aclaracion'=>'resolved'])->assertViewHas('rows', fn ($r) => $r->total() === 1);
        $this->page(['folio'=>'missing'])->assertSee('No hay facturas');
        $this->page(['periodo'=>'mes','desde'=>'2026-08-15','hasta'=>'2026-08-15'])->assertViewHas('rows', fn ($r) => $r->total() === 3);
        $this->page(['pago_estado'=>'invalid'])->assertSessionHasErrors('pago_estado');
    }
    public function test_due_dates_reuse_prodifem_service_and_completed_is_not_paid(): void
    {
        $row = $this->page()->viewData('rows')->firstWhere('folio','F-1051');
        $expected = app(InstitutionBillingDueDateService::class)->calculate('2026-08-20');
        $this->assertSame($expected['days'], $row['days_overdue']);
        $this->assertSame('2026-08-31', $row['due']->toDateString());
        InstitutionBilling::where('origen_id',20)->update(['estatus_facturacion'=>'Completado']);
        $row = $this->page()->viewData('rows')->firstWhere('folio','F-1052');
        $this->assertSame('pending',$row['state']);
        $this->assertFalse($row['overdue']);
    }

    public function test_document_workflow_filters_are_independent_of_payment_and_apply_to_exports(): void
    {
        foreach ([20 => 'signing', 21 => 'delivered'] as $id => $status) {
            HospitalInvoiceAccount::updateOrCreate(['invoice_key' => $this->key($id)], [
                'hospital_id' => 1, 'institucion_id' => 1, 'document_status' => $status,
            ]);
        }
        $this->page()->assertOk()->assertViewHas('rows', fn ($rows) => $rows->total() === 3)
            ->assertViewHas('filterQuery', fn ($query) => $query['tramite_estado'] === 'received');
        foreach (['signing' => 'F-1052', 'delivered' => 'F-1053'] as $state => $folio) {
            $response = $this->page(['tramite_estado' => $state])->assertOk()
                ->assertViewHas('rows', fn ($rows) => $rows->total() === 1 && $rows[0]['folio'] === $folio);
            $this->assertSame($state, $response->viewData('filterQuery')['tramite_estado']);
            $response->assertSee('name="tramite_estado" value="'.$state.'"', false);
        }
        $this->page(['tramite_estado' => 'delivered', 'pago_estado' => 'paid'])->assertViewHas('rows', fn ($rows) => $rows->total() === 1);
        $this->page(['tramite_estado' => 'delivered', 'pago_estado' => 'pending'])->assertViewHas('rows', fn ($rows) => $rows->total() === 0);
        InstitutionBilling::where('origen_id', 22)->update(['estatus_facturacion' => 'Completado']);
        $this->page()->assertSee('F-1054');
        $this->page(['tramite_estado' => 'invalid'])->assertSessionHasErrors('tramite_estado');
        $this->get(route('admin.hospital.facturacion.detalle', $this->key(21)))->assertOk()->assertSee('F-1053');
        $this->get(route('admin.hospital.facturacion.detalle', $this->key(20)))->assertOk()->assertSee('F-1052');
        \Maatwebsite\Excel\Facades\Excel::fake();
        $query = ['desde' => '2026-08-01', 'hasta' => '2026-09-15', 'tramite_estado' => 'delivered'];
        $this->get(route('admin.hospital.facturacion.exportar', $query))->assertOk();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('facturacion_hospital.xlsx', fn ($export) => $export->collection()->count() === 1 && $export->collection()->first()['folio'] === 'F-1053');
        $this->get(route('admin.hospital.facturacion.estado-cuenta', $query))->assertOk()->assertHeader('content-type', 'application/pdf');
    }
    public function test_reports_are_pending_idempotent_scoped_and_never_change_balance(): void
    {
        $uuid = (string) Str::uuid();
        $this->report(['submission_key'=>$uuid,'status'=>'approved','reviewed_by'=>1,'hospital_id'=>2])->assertCreated();
        $this->report(['submission_key'=>$uuid])->assertOk();
        $this->report(['submission_key'=>$uuid,'amount'=>'999.00'])->assertStatus(409);
        $payment = HospitalInvoicePayment::where('submission_key',$uuid)->sole();
        $this->assertSame('pending',$payment->status);
        $this->assertNull($payment->reviewed_at);
        $this->assertSame(1,InstitutionBillingMovement::count());
        $this->page()->assertViewHas('summary', fn ($s) => $s['paid'] === 15000000 && $s['balance'] === 15000000);
        $this->report(['amount'=>'40000.00'])->assertUnprocessable();
        $this->report([], $this->key(3))->assertNotFound();
        foreach ([['amount'=>'-1'],['amount'=>'0'],['amount'=>'1.001'],['paid_at'=>'2027-01-01'],['reference'=>' ']] as $data) $this->report($data)->assertUnprocessable();
        $this->get(route('admin.hospital.facturacion.detalle',$this->key(3)))->assertNotFound();
        $this->get(route('admin.hospital.facturacion.detalle',$this->key()))->assertOk()->assertSee('TEST-REF')->assertSee('Pendiente de validación');
    }
    public function test_only_internal_billing_can_validate_payment_once(): void
    {
        $this->report()->assertCreated();
        $payment = HospitalInvoicePayment::where('status','pending')->first();
        $url = route('admin.instituciones.billing.payment-review',$payment);
        $this->postJson($url,['decision'=>'approved'])->assertForbidden();
        $this->hospital->syncRoles('Super Admin');
        $this->post($url,['decision'=>'approved'])->assertRedirect();
        $this->assertSame('approved',$payment->fresh()->status);
        $this->postJson($url,['decision'=>'approved'])->assertStatus(409);
        $this->hospital->syncRoles('Institucion');
        $this->page()->assertViewHas('summary',fn ($s) => $s['paid'] === 16000000 && $s['balance'] === 14000000);
    }
    public function test_unknown_amounts_are_not_paid_and_private_documents_require_ownership(): void
    {
        InstitutionBilling::where('origen_id',20)->update(['precio_total'=>null]);
        $row = $this->page()->viewData('rows')->firstWhere('folio','F-1052');
        $this->assertSame('unknown',$row['state']);
        $this->assertNull($row['balance']);
        $this->report([], $this->key(20))->assertUnprocessable();
        Storage::fake('local');
        $account = HospitalInvoiceAccount::where('invoice_key',$this->key())->first();
        $account->update(['pdf_path'=>'hospital-invoices/test.pdf']);
        Storage::disk('local')->put('hospital-invoices/test.pdf','%PDF-1.4 test');
        $this->get(route('admin.hospital.facturacion.documento',[$this->key(),'pdf']))->assertOk()->assertDownload('factura.pdf');
        $this->get(route('admin.hospital.facturacion.documento',[$this->key(3),'pdf']))->assertNotFound();
        $this->get(route('admin.hospital.facturacion.documento',[$this->key(),'xml']))->assertNotFound();
        $account->update(['pdf_path'=>'../secret.pdf']);
        $this->get(route('admin.hospital.facturacion.documento',[$this->key(),'pdf']))->assertNotFound();
    }
    public function test_exports_honor_filters_and_are_real_documents(): void
    {
        $query = ['desde'=>'2026-08-01','hasta'=>'2026-09-15','pago_estado'=>'partial'];
        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->get(route('admin.hospital.facturacion.exportar',$query))->assertOk();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('facturacion_hospital.xlsx', fn ($export) => $export->collection()->count() === 1 && $export->map($export->collection()->first())[0] === 'F-1051');
        $pdf = $this->get(route('admin.hospital.facturacion.estado-cuenta',$query))->assertOk()->assertHeader('content-type','application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_partial_invoice_access_is_denied_and_missing_permissions_are_enforced(): void
    {
        $key = $this->key();
        $this->hospital->revokePermissionTo('nutricionales_solicitudes_index');
        $this->page()->assertDontSee('F-1051')->assertViewHas('rows', fn ($r) => $r->total() === 4);
        $this->get(route('admin.hospital.facturacion.detalle',$key))->assertNotFound();
        $this->report([], $key)->assertNotFound();
        $this->hospital->revokePermissionTo('oncologicos_solicitudes_index');
        $this->page()->assertForbidden();
        $this->get(route('admin.hospital.facturacion.exportar'))->assertForbidden();
    }

    public function test_rejected_payments_remain_in_history_without_changing_totals(): void
    {
        $this->report()->assertCreated();
        $payment = HospitalInvoicePayment::where('status','pending')->sole();
        $this->hospital->syncRoles('Super Admin');
        $this->post(route('admin.instituciones.billing.payment-review',$payment),['decision'=>'rejected'])->assertRedirect();
        $this->assertSame('rejected',$payment->fresh()->status);
        $this->hospital->syncRoles('Institucion');
        $this->page()->assertViewHas('summary',fn ($s) => $s['paid'] === 15000000);
        $this->get(route('admin.hospital.facturacion.detalle',$this->key()))->assertSee('Rechazado');
    }
}
