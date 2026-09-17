<?php

namespace Tests\Fixtures;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgentAuditData
{
    public static function seed(): void
    {
        if (DB::getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') throw new \RuntimeException('Memory database required.');
        Schema::table('users', fn (Blueprint $t) => $t->unsignedBigInteger('hospital_id')->nullable());
        $tables = [
            'hospitals' => 'name,laboratory_id', 'cliente_hospital' => 'cliente_id,hospital_id',
            'medicine_batches' => 'lote,medicine_presentation_id,stock_actual,stock_reservado,caducidad,costo_unitario,is_active,laboratory_id,warehouse_id',
            'medicine_laboratory_stocks' => 'lote,nutrition_medicine_presentation_id,frascos_actuales,caducidad,is_active,laboratory_id,warehouse_id',
            'diluent_presentations' => 'lote,diluent_id,stock_actual,stock_reservado,caducidad,is_active,laboratory_id,warehouse_id',
            'consumable_lots' => 'lot,catalog_presentation_id,stock_actual,expires_at,is_active,warehouse_id',
            'medicine_batch_movements' => 'medicine_batch_id,movement_type,quantity,stock_actual_before,stock_actual_after,reference_type,reference_id,laboratory_id,warehouse_id',
            'medicine_stock_movements' => 'medicine_laboratory_stock_id,tipo,cantidad_ml,stock_antes,stock_despues,reference_type,reference_id,warehouse_id',
            'mezclas' => 'solicitud_id,estado,lote,remision,fecha_entrega',
            'solicitud_oncos' => 'hospital_id,estado,fecha_entrega',
            'solicituds' => 'user_id,solicitud_detail_id,estado,lote,remision',
            'solicitud_details' => 'fecha_hora_entrega',
            'inspeccion_mezclas' => 'mezcla_id,mezcla_aprobada',
            'inspeccion_nutricionales' => 'solicitud_id,mezcla_aprobada',
            'institution_billings' => 'hospital_id,origen_tipo,origen_id,folio_interno,fecha_facturacion,numero_carta_factura,fecha_carta_factura,precio_total,estatus_facturacion,fecha_compensacion',
            'production_supply_requests' => 'folio,status,warehouse_id',
            'production_supply_request_lines' => 'production_supply_request_id,diluent_presentation_id,consumable_lot_id,approved_quantity,supplied_quantity',
            'laboratory_purchase_orders' => 'folio,proposed_delivery_at,status,laboratory_id,warehouse_id',
            'maintenance_services' => 'service,identification,frequency,is_active,laboratory_id',
            'distribution_delivery_schedules' => 'hospital_id,warehouse_id,scheduled_date,status',
            'distribution_delivery_confirmations' => 'distribution_delivery_schedule_id,delivered_at',
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach (explode(',', $columns) as $column) {
                    if (str_ends_with($column, '_id') || $column === 'is_active') $table->integer($column)->nullable();
                    else $table->text($column)->nullable();
                }
            });
        }
        DB::table('laboratories')->insert([['id' => 1, 'nombre' => 'Central A'], ['id' => 2, 'nombre' => 'Central B']]);
        DB::table('warehouses')->insert([['id' => 1, 'name' => 'Almacén A', 'laboratory_id' => 1], ['id' => 2, 'name' => 'Almacén B', 'laboratory_id' => 2]]);
        DB::table('clientes')->insert([['id' => 1, 'nombre' => 'Institución A'], ['id' => 2, 'nombre' => 'Institución B']]);
        DB::table('hospitals')->insert([['id' => 1, 'name' => 'Hospital A', 'laboratory_id' => 1], ['id' => 2, 'name' => 'Hospital B', 'laboratory_id' => 2]]);
        DB::table('cliente_hospital')->insert([['cliente_id' => 1, 'hospital_id' => 1], ['cliente_id' => 2, 'hospital_id' => 2]]);
        (require database_path('migrations/2026_09_15_130000_create_hospital_conciliation_submissions.php'))->up();
        (require database_path('migrations/2026_09_15_150000_add_confirmation_to_hospital_conciliation_submissions.php'))->up();
    }
}
