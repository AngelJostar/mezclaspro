<?php

namespace App\Services\Agents;

use Carbon\CarbonImmutable;

class AgentRules
{
    public function evaluate(string $rule, array $sources, array $config, CarbonImmutable $now): array
    {
        $findings = [];
        $add = function (array $row, string $title, string $reason, ?float $amount, string $unit, string $action) use (&$findings, $rule, $config) {
            $findings[] = ['rule' => $rule, 'record' => $row, 'title' => $title, 'reason' => $reason,
                'amount' => $amount, 'unit' => $unit, 'action' => $action, 'priority' => $config['priority']];
        };
        $rows = collect(AgentConfiguration::rules()[$rule]['sources'])->flatMap(fn ($source) => $sources[$source] ?? []);
        if ($rule === 'invoicing') $rows = $rows->whereIn('record_type', ['oncologica_mezcla', 'nutricional_solicitud']);
        if ($rule === 'summary') $rows = $rows->sortBy(fn ($r) => ['high' => 0, 'medium' => 1, 'low' => 2][$r['priority']] ?? 3);
        foreach ($rows as $r) {
            if ($rule === 'conciliation') {
                if ($r['no']) $add($r, 'Conciliación con mezclas no conciliables', $r['no'].' registros marcados como No en el envío.', $r['no'], 'registros de mezcla', 'Revisar los motivos con el responsable de conciliación.');
                foreach (['missing_amounts' => 'Importes sin registrar', 'missing_fields' => 'Datos de conciliación incompletos', 'missing_reasons' => 'No conciliables sin motivo'] as $field => $title) {
                    if ($r[$field]) $add($r, $title, $r[$field].' registros con información pendiente en el envío.', $r[$field], 'registros de mezcla', 'Consultar la conciliación y solicitar la documentación faltante; no sustituir datos ausentes por valores supuestos.');
                }
            }
            if ($rule === 'consumption') {
                if ($r['before'] === null || $r['after'] === null || $r['quantity'] === null) {
                    $add($r, 'Salida con trazabilidad incompleta', 'Falta cantidad o existencia anterior/posterior.', null, $r['unit'], 'Revisar el movimiento y su registro de origen.');
                } else {
                    $difference = round((float) $r['before'] - (float) $r['after'] - (float) $r['quantity'], 4);
                    if (abs($difference) > 0.0001) $add($r, 'Diferencia en salida de inventario', 'Existencia anterior - posterior - cantidad de salida.', abs($difference), $r['unit'], 'Conciliar el movimiento con Almacén; no ajustar automáticamente.');
                }
            }
            if (in_array($rule, ['production', 'quality', 'invoicing'])) {
                if (in_array(mb_strtolower($r['request_status'] ?? $r['estado']), ['cancelada', 'cancelado', 'no_aprobada', 'no-aprobada', 'rechazada'])) continue;
                $delivered = in_array(mb_strtolower($r['estado']), ['entregada', 'entregado']);
                if ($rule === 'production' && ! $delivered && $r['due_at']) {
                    $due = CarbonImmutable::parse($r['due_at']);
                    if ($due->lte($now->addMinutes($config['thresholds']['delivery_minutes']))) {
                        $late = $due->lt($now);
                        $add($r, $late ? 'Entrega comprometida atrasada' : 'Entrega próxima pendiente', 'Fecha comprometida '.$due->format('d/m/Y H:i').'; estado '.$r['estado'].'.', (float) $now->diffInMinutes($due), $late ? 'minutos de atraso' : 'minutos restantes', 'Revisar el bloqueo con la etapa responsable.');
                    }
                }
                if ($rule === 'quality' && $delivered && (! $r['inspection_approved'] || ! trim((string) $r['lote']))) {
                    $missing = [];
                    if (! $r['inspection_approved']) $missing[] = 'inspección aprobada';
                    if (! trim((string) $r['lote'])) $missing[] = 'lote';
                    $add($r, 'Entrega con registro incompleto', 'No consta: '.implode(', ', $missing).'.', 1, 'mezcla', 'Solicitar revisión documental al responsable sanitario.');
                }
                if ($rule === 'invoicing' && $delivered && mb_strtolower((string) $r['estatus_facturacion']) !== 'completado') {
                    $missing = array_filter(['folio_interno', 'fecha_facturacion', 'numero_carta_factura', 'fecha_carta_factura'], fn ($field) => ! trim((string) $r[$field]));
                    if ($missing) $add($r, 'Entrega pendiente de completar facturación', 'Campos pendientes: '.implode(', ', $missing).'.', is_numeric($r['precio_total']) ? (float) $r['precio_total'] : null, 'importe registrado (moneda no especificada)', 'Conciliar remisión y factura; verificar precio antes de emitir.');
                }
            }
            if ($rule === 'inventory' && isset($r['quantity'])) {
                $reserved = (float) ($r['reserved'] ?? 0);
                if ((float) $r['quantity'] < 0 || $reserved > (float) $r['quantity']) $add($r, 'Existencia insuficiente para las reservas registradas', 'Reservado '.$reserved.'; existencia '.$r['quantity'].'.', max(0, $reserved - (float) $r['quantity']), 'unidades', 'Revisar reservas y existencias de este lote.');
            }
            if ($rule === 'expiry' && (float) $r['quantity'] > 0) {
                if (! $r['expires_at']) {
                    $add($r, 'Lote con existencia sin caducidad', 'No consta una fecha de caducidad.', (float) $r['quantity'], 'unidades', 'Completar la trazabilidad del lote con el responsable.');
                } else {
                    $expiry = CarbonImmutable::parse($r['expires_at'])->endOfDay();
                    if ($expiry->lte($now->addDays($config['thresholds']['expiry_days'])->endOfDay())) $add($r, $expiry->lt($now) ? 'Lote vencido con existencia registrada' : 'Lote próximo a vencer', 'Caducidad '.$expiry->format('d/m/Y').'; ventana '.$config['thresholds']['expiry_days'].' días.', (float) $r['quantity'], 'unidades', 'Revisar disponibilidad y destino autorizado; no liberar ni descartar automáticamente.');
                }
            }
            if ($rule === 'purchases' && ! $r['proposed_delivery_at'] && ! in_array(mb_strtolower((string) $r['status']), ['cancelado', 'cancelada', 'cancelled'])) $add($r, 'Compra sin fecha de entrega propuesta', 'Pedido '.$r['folio'].' sin compromiso de entrega registrado.', 1, 'pedido', 'Confirmar la fecha con Compras y el proveedor.');
            if ($rule === 'costs' && (float) $r['quantity'] > 0 && (! is_numeric($r['costo_unitario']) || (float) $r['costo_unitario'] <= 0)) $add($r, 'Lote sin costo unitario positivo', 'El costo está ausente o no es positivo; no se puede calcular el valor.', (float) $r['quantity'], 'unidades sin valoración', 'Confirmar el costo de adquisición y si el costo cero está justificado.');
            if ($rule === 'collection' && $r['fecha_facturacion'] && ! $r['fecha_compensacion'] && mb_strtolower((string) $r['estatus_facturacion']) !== 'completado') {
                $date = CarbonImmutable::parse($r['fecha_facturacion']);
                if ($date->lte($now->subDays($config['thresholds']['collection_days']))) $add($r, 'Factura con antigüedad sin compensación registrada', 'Antigüedad '.$date->diffInDays($now).' días; umbral '.$config['thresholds']['collection_days'].' días. No implica vencimiento contractual ni saldo impago confirmado.', is_numeric($r['precio_total']) ? (float) $r['precio_total'] : null, 'importe registrado (moneda no especificada)', 'Revisar pagos, compensaciones y seguimiento antes de contactar al cliente.');
            }
            if ($rule === 'distribution' && ! $r['delivered_at'] && $r['scheduled_date'] && ! in_array(mb_strtolower((string) $r['status']), ['cancelled', 'cancelada', 'cancelado']) && CarbonImmutable::parse($r['scheduled_date'])->endOfDay()->lt($now)) $add($r, 'Entrega programada sin confirmación', 'Fecha programada '.$r['scheduled_date'].' sin evidencia de recepción registrada.', 1, 'entrega', 'Verificar la entrega y la evidencia con Distribución.');
            if ($rule === 'maintenance' && (! trim((string) $r['identification']) || ! trim((string) $r['frequency']))) $add($r, 'Servicio con datos de control incompletos', 'Falta identificación o frecuencia en '.$r['service'].'.', 1, 'servicio', 'Completar la identificación y periodicidad autorizada.');
            if ($rule === 'summary') {
                $priority = $r['priority'];
                $evidence = json_decode($r['evidence'], true, flags: JSON_THROW_ON_ERROR);
                $r = ['id' => $r['id'], 'record_type' => 'ai_agent_findings', 'last_seen_at' => $r['last_seen_at'], 'owner' => $r['owner']];
                $add($r, $evidence['title'], 'Hallazgo abierto de otro agente; última detección '.$r['last_seen_at'].'.', $evidence['amount'], $evidence['unit'], $evidence['action']);
                $findings[array_key_last($findings)]['priority'] = $priority;
            }
        }
        if ($rule === 'inventory') {
            $lots = collect(array_merge($sources['diluents'] ?? [], $sources['consumables'] ?? []))->keyBy(fn ($r) => $r['record_type'].':'.$r['id']);
            $groups = collect($sources['supply_requests'] ?? [])->groupBy(fn ($r) => ($r['diluent_presentation_id'] ? 'diluent_presentations:'.$r['diluent_presentation_id'] : 'consumable_lots:'.$r['consumable_lot_id']));
            foreach ($groups as $key => $requests) {
                $lot = $lots->get($key);
                if (! $lot) continue; // Missing/out-of-scope inventory is not evidence of zero stock.
                $required = $requests->sum(fn ($r) => max(0, (float) $r['approved_quantity'] - (float) $r['supplied_quantity']));
                $usable = $lot['is_active'] && $lot['expires_at'] && CarbonImmutable::parse($lot['expires_at'])->endOfDay()->gte($now);
                $available = $usable ? max(0, (float) $lot['quantity']) : 0;
                if ($required > $available) {
                    $lot['request_ids'] = $requests->pluck('request_id')->unique()->values()->all();
                    $add($lot, 'Abasto aprobado supera la existencia del lote', 'Pendiente aprobado '.$required.' - existencia utilizable '.$available.'. Reservas no se descuentan de nuevo.', $required - $available, 'unidades faltantes', 'Revisar las requisiciones vinculadas y proponer abasto para autorización.');
                }
            }
        }
        return $findings;
    }
}
