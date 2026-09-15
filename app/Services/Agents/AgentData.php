<?php

namespace App\Services\Agents;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AgentData
{
    public const LIMIT = 3000;

    /** Queries only project audit fields, never patient names, diagnoses or credentials. */
    public function read(string $source, array $config): array
    {
        $rows = [];
        $issues = [];
        foreach ($this->queries($source, $config) as [$query, $key, $hospital, $laboratory, $warehouse]) {
            if (! $config['scope_all']) {
                foreach (['institutions' => $hospital, 'laboratories' => $laboratory, 'warehouses' => $warehouse] as $dimension => $column) {
                    if (! empty($config[$dimension]) && ! $column) {
                        $issues[] = 'Esta fuente no permite delimitar por '.['institutions' => 'institución', 'laboratories' => 'central', 'warehouses' => 'almacén'][$dimension].'.';
                        continue 2;
                    }
                    if (empty($config[$dimension])) continue;
                    if ($dimension === 'institutions') {
                        $query->whereIn($column, DB::table('cliente_hospital')->select('hospital_id')->whereIn('cliente_id', $config[$dimension]));
                    } else {
                        $query->whereIn($column, $config[$dimension]);
                    }
                }
            }
            $items = $query->orderByDesc($key)->limit(self::LIMIT + 1)->get();
            if ($items->count() > self::LIMIT) $issues[] = 'Límite de '.self::LIMIT.' registros por consulta; revisión parcial.';
            foreach ($items->take(self::LIMIT) as $item) $rows[] = (array) $item;
        }
        return ['rows' => $rows, 'issues' => array_values(array_unique($issues))];
    }

    private function stockQuery(string $table, array $columns): Builder
    {
        return DB::table($table.' as s')->select($columns)->addSelect(DB::raw("'{$table}' as record_type"));
    }

    private function requests(bool $nutrition, bool $billing): Builder
    {
        $q = $nutrition
            ? DB::table('solicituds as s')->join('users as u', 'u.id', '=', 's.user_id')->join('solicitud_details as d', 'd.id', '=', 's.solicitud_detail_id')->leftJoin('hospitals as h', 'h.id', '=', 'u.hospital_id')
            : DB::table('mezclas as s')->join('solicitud_oncos as r', 'r.id', '=', 's.solicitud_id')->leftJoin('hospitals as h', 'h.id', '=', 'r.hospital_id');
        $origin = $nutrition ? 'nutricional_solicitud' : 'oncologica_mezcla';
        $inspection = $nutrition ? 'inspeccion_nutricionales' : 'inspeccion_mezclas';
        $foreign = $nutrition ? 'solicitud_id' : 'mezcla_id';
        $q->select(['s.id', 's.estado', 's.lote', 's.remision', 'h.id as hospital_id', 'h.laboratory_id']);
        $q->addSelect(DB::raw("'{$origin}' as record_type"));
        $q->addSelect($nutrition ? 'd.fecha_hora_entrega as due_at' : DB::raw('COALESCE(s.fecha_entrega, r.fecha_entrega) as due_at'));
        if (! $nutrition) $q->addSelect('r.estado as request_status');
        $q->selectSub(DB::table($inspection.' as i')->select('mezcla_aprobada')->whereColumn('i.'.$foreign, 's.id')->orderByDesc('i.id')->limit(1), 'inspection_approved');
        foreach (['folio_interno', 'fecha_facturacion', 'numero_carta_factura', 'fecha_carta_factura', 'precio_total', 'estatus_facturacion'] as $field) {
            if (! $billing) {
                $q->selectRaw('NULL as '.$field);
                continue;
            }
            $q->selectSub(DB::table('institution_billings as b')->select($field)->where('origen_tipo', $origin)->whereColumn('b.origen_id', 's.id')->whereColumn('b.hospital_id', 'h.id')->orderByDesc('b.id')->limit(1), $field);
        }
        return $q;
    }

    private function queries(string $source, array $config): array
    {
        $billing = in_array('invoicing', $config['rules']) && in_array('billing', $config['sources']);
        // Each tuple declares which scope dimensions the source can enforce. Unsupported scopes fail closed.
        return match ($source) {
            'batches' => [[$this->stockQuery('medicine_batches', ['s.id', 's.lote as lot', 's.medicine_presentation_id as product_id', 's.stock_actual as quantity', 's.stock_reservado as reserved', 's.caducidad as expires_at', 's.costo_unitario', 's.is_active', 's.laboratory_id', 's.warehouse_id']), 's.id', null, 's.laboratory_id', 's.warehouse_id']],
            'nutrition' => [[$this->stockQuery('medicine_laboratory_stocks', ['s.id', 's.lote as lot', 's.nutrition_medicine_presentation_id as product_id', 's.frascos_actuales as quantity', 's.caducidad as expires_at', 's.is_active', 's.laboratory_id', 's.warehouse_id']), 's.id', null, 's.laboratory_id', 's.warehouse_id']],
            'diluents' => [[$this->stockQuery('diluent_presentations', ['s.id', 's.lote as lot', 's.diluent_id as product_id', 's.stock_actual as quantity', 's.stock_reservado as reserved', 's.caducidad as expires_at', 's.is_active', 's.laboratory_id', 's.warehouse_id']), 's.id', null, 's.laboratory_id', 's.warehouse_id']],
            'consumables' => [[$this->stockQuery('consumable_lots', ['s.id', 's.lot', 's.catalog_presentation_id as product_id', 's.stock_actual as quantity', 's.expires_at', 's.is_active', 's.warehouse_id', 'w.laboratory_id'])->leftJoin('warehouses as w', 'w.id', '=', 's.warehouse_id'), 's.id', null, 'w.laboratory_id', 's.warehouse_id']],
            'movements' => [
                [DB::table('medicine_batch_movements as s')->where('s.movement_type', 'salida')->select(['s.id', 's.medicine_batch_id as lot_id', 's.quantity', 's.stock_actual_before as before', 's.stock_actual_after as after', 's.reference_type', 's.reference_id', 's.laboratory_id', 's.warehouse_id'])->selectRaw("'medicine_batch_movements' as record_type, 'frascos' as unit"), 's.id', null, 's.laboratory_id', 's.warehouse_id'],
                [DB::table('medicine_stock_movements as s')->join('medicine_laboratory_stocks as l', 'l.id', '=', 's.medicine_laboratory_stock_id')->where('s.tipo', 'salida')->select(['s.id', 's.medicine_laboratory_stock_id as lot_id', 's.cantidad_ml as quantity', 's.stock_antes as before', 's.stock_despues as after', 's.reference_type', 's.reference_id', 's.warehouse_id', 'l.laboratory_id'])->selectRaw("'medicine_stock_movements' as record_type, 'mL' as unit"), 's.id', null, 'l.laboratory_id', 's.warehouse_id'],
            ],
            'onco' => [[$this->requests(false, $billing), 's.id', 'h.id', 'h.laboratory_id', null]],
            'nutri' => [[$this->requests(true, $billing), 's.id', 'h.id', 'h.laboratory_id', null]],
            'supply_requests' => [[DB::table('production_supply_request_lines as s')->join('production_supply_requests as r', 'r.id', '=', 's.production_supply_request_id')->join('warehouses as w', 'w.id', '=', 'r.warehouse_id')->whereIn('r.status', ['approved', 'partially_approved'])->select(['s.id', 'r.id as request_id', 'r.folio', 's.diluent_presentation_id', 's.consumable_lot_id', 's.approved_quantity', 's.supplied_quantity', 'r.warehouse_id', 'w.laboratory_id'])->selectRaw("'production_supply_request_lines' as record_type"), 's.id', null, 'w.laboratory_id', 'r.warehouse_id']],
            'billing' => [[DB::table('institution_billings as s')->leftJoin('hospitals as h', 'h.id', '=', 's.hospital_id')->select(['s.id', 's.hospital_id', 'h.laboratory_id', 's.precio_total', 's.folio_interno', 's.fecha_facturacion', 's.numero_carta_factura', 's.fecha_carta_factura', 's.fecha_compensacion', 's.estatus_facturacion'])->selectRaw("'institution_billings' as record_type"), 's.id', 's.hospital_id', 'h.laboratory_id', null]],
            'orders' => [[DB::table('laboratory_purchase_orders as s')->select(['s.id', 's.folio', 's.proposed_delivery_at', 's.status', 's.laboratory_id', 's.warehouse_id'])->selectRaw("'laboratory_purchase_orders' as record_type"), 's.id', null, 's.laboratory_id', 's.warehouse_id']],
            'maintenance' => [[DB::table('maintenance_services as s')->where('s.is_active', true)->select(['s.id', 's.service', 's.identification', 's.frequency', 's.laboratory_id'])->selectRaw("'maintenance_services' as record_type"), 's.id', null, 's.laboratory_id', null]],
            'deliveries' => [[DB::table('distribution_delivery_schedules as s')->leftJoin('warehouses as w', 'w.id', '=', 's.warehouse_id')->select(['s.id', 's.hospital_id', 's.warehouse_id', 'w.laboratory_id', 's.scheduled_date', 's.status'])->selectSub(DB::table('distribution_delivery_confirmations as c')->select('delivered_at')->whereColumn('c.distribution_delivery_schedule_id', 's.id')->orderByDesc('c.id')->limit(1), 'delivered_at')->selectRaw("'distribution_delivery_schedules' as record_type"), 's.id', 's.hospital_id', 'w.laboratory_id', 's.warehouse_id']],
            'findings' => [[DB::table('ai_agent_findings as s')->whereIn('s.status', ['new', 'review'])->where('s.rule', '!=', 'summary')->select(['s.id', 's.priority', 's.evidence', 's.owner', 's.last_seen_at'])->selectRaw("'ai_agent_findings' as record_type"), 's.id', null, null, null]],
            default => throw new \InvalidArgumentException('Fuente no permitida.'),
        };
    }
}
