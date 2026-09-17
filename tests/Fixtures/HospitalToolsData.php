<?php

namespace Tests\Fixtures;

use App\Models\InstitutionBilling;
use App\Models\MixtureAdjustment;
use Illuminate\Support\Facades\DB;

class HospitalToolsData
{
    public static function addLogCases(): void
    {
        if (DB::getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Tools fixtures require an in-memory database.');
        }
        DB::table('mezclas')->where('id', 1)->update(['adjustment_id' => 2, 'estado' => 'pendiente']);
        foreach ([['antibioticos', 2, 'authorized'], ['oncologicos', 4, 'approved'], ['nutricionales', 11, 'approved'],
            ['oncologicos', 20, 'rejected'], ['oncologicos', 21, 'declined'], ['oncologicos', 22, 'cancelled']] as [$kind, $id, $status]) {
            if ($id >= 20) DB::table('mezclas')->insert(['id' => $id, 'solicitud_id' => 1, 'estado' => 'pendiente']);
            $version = MixtureAdjustment::create(['kind' => $kind, 'target_id' => $id, 'hospital_id' => 1,
                'status' => $status, 'description' => 'Propuesta de prueba '.$id, 'proposal' => [],
                'review' => [['label' => 'Volumen (ml)', 'before' => '250', 'value' => '300', 'changed' => true],
                    ['label' => 'Observaciones', 'before' => 'Sin observaciones', 'value' => 'Sin observaciones', 'changed' => false]],
                'baseline_hash' => str_repeat('b', 64), 'requested_by' => 1, 'authorized_by' => 1,
                'authorized_at' => '2026-09-12 13:00:00', 'hospital_response' => 'Respuesta del hospital de prueba',
                'approved_by' => $status === 'approved' ? 1 : null,
                'approved_at' => $status === 'approved' ? '2026-09-13 10:00:00' : null,
                'cancelled_by' => in_array($status, ['rejected', 'cancelled']) ? 1 : null,
                'cancelled_at' => in_array($status, ['rejected', 'cancelled']) ? '2026-09-13 10:00:00' : null,
                'central_response' => $status === 'rejected' ? 'Respuesta de Prodifem de prueba' : null,
                'created_at' => '2026-09-11 10:00:00']);
            DB::table($kind === 'nutricionales' ? 'solicituds' : 'mezclas')->where('id', $id)->update(['adjustment_id' => $version->id]);
        }
        // An old delivered request must remain visible when all history is selected.
        DB::table('mixture_adjustments')->where('target_id', 4)->where('kind', 'oncologicos')->update(['created_at' => '2025-01-05 09:00:00']);
    }

    public static function addBilling(): void
    {
        if (DB::getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Tools fixtures require an in-memory database.');
        }
        foreach (['2026_07_29_130000_create_institution_billings_table.php', '2026_08_19_000002_create_institution_billing_movements_table.php', '2026_09_15_120000_create_hospital_invoice_accounts.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        (require database_path('migrations/2026_09_15_140000_add_document_status_to_hospital_invoice_accounts.php'))->up();
        DB::table('clientes')->insert(['id' => 1, 'nombre' => 'Institucion de prueba']);
        DB::table('cliente_hospital')->insert(['hospital_id' => 1, 'cliente_id' => 1]);
        foreach ([['oncologica_mezcla', 1, 1], ['oncologica_mezcla', 3, 2], ['nutricional_solicitud', 11, 1]] as [$kind, $id, $hospital]) {
            InstitutionBilling::create(['origen_tipo' => $kind, 'origen_id' => $id, 'institucion_id' => 1,
                'hospital_id' => $hospital, 'conciliable' => 'Si', 'precio_total' => '500.00',
                'folio_interno' => $hospital === 2 ? 'FACTURA-AJENA' : 'F-'.$id, 'fecha_facturacion' => '2026-09-10']);
        }
        foreach ([['oncologicos', 1, 1, 'approved'], ['oncologicos', 1, 1, 'requested'], ['oncologicos', 3, 2, 'authorized'], ['nutricionales', 12, 2, 'requested']] as [$kind, $id, $hospital, $status]) {
            MixtureAdjustment::create(['kind' => $kind, 'target_id' => $id, 'hospital_id' => $hospital,
                'status' => $status, 'description' => $hospital === 1 ? 'Propuesta de prueba' : 'AJUSTE AJENO',
                'proposal' => [], 'review' => [], 'baseline_hash' => str_repeat('a', 64), 'requested_by' => 1,
                'created_at' => '2026-09-14 10:00:00']);
        }
    }
}
