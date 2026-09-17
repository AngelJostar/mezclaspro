<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAgentFinding extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['evidence' => 'array', 'last_seen_at' => 'datetime', 'reviewed_at' => 'datetime'];

    public static function statusLabels(): array
    {
        return ['new' => 'Nueva', 'review' => 'En revisión', 'resolved' => 'Resuelta', 'dismissed' => 'Descartada'];
    }

    public static function recordUrl(array $record): ?string
    {
        return match ($record['record_type']) {
            'hospital_conciliation_submissions' => route('admin.instituciones.conciliaciones.show', $record['id']),
            'oncologica_mezcla' => route('admin.oncologicos.mezclas.show', $record['id']),
            'nutricional_solicitud' => route('admin.nutricionales.solicitudes.show', $record['id']),
            'production_supply_request_lines' => route('admin.production-supplies.show', $record['request_id']),
            'institution_billings' => route('admin.instituciones.billing.receivable', ['search' => $record['folio_interno']]),
            'maintenance_services' => route('admin.maintenance-qualifications.catalog'),
            'laboratory_purchase_orders' => route('admin.warehouses.purchase-orders.index', ['search' => $record['folio']]),
            'distribution_delivery_schedules' => route('admin.distribution.deliveries.index'),
            'ai_agent_findings' => null,
            default => route('admin.warehouses.index', ['warehouse' => $record['warehouse_id'] ?? null]),
        };
    }
}
