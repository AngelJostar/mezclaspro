<?php

namespace App\Models;

use App\Models\Oncologicos\Laboratory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceAppointment extends Model
{
    public const STATUSES = ['pending' => 'Pendiente', 'scheduled' => 'Programado', 'completed' => 'Realizado', 'cancelled' => 'Cancelado'];
    public const REPORT_STATUSES = ['pending' => 'Pendiente', 'review' => 'Por revisar', 'closed' => 'Cerrado'];
    protected $fillable = ['service_id', 'provider_id', 'laboratory_id', 'asset', 'location', 'responsible_name', 'frequency', 'starts_at', 'ends_at', 'next_service_at', 'status', 'report_status', 'report_notes', 'reviewed_by', 'closed_at'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'next_service_at' => 'date', 'closed_at' => 'datetime'];

    public function service() { return $this->belongsTo(MaintenanceService::class, 'service_id'); }
    public function provider() { return $this->belongsTo(MaintenanceProvider::class, 'provider_id'); }
    public function laboratory() { return $this->belongsTo(Laboratory::class); }
    public function documents() { return $this->hasMany(MaintenanceDocument::class, 'appointment_id')->latest(); }
    public function getFolioAttribute(): string { return 'MTO-'.str_pad($this->id, 5, '0', STR_PAD_LEFT); }
}
