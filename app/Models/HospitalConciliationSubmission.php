<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalConciliationSubmission extends Model
{
    protected $fillable = [
        'submission_key', 'hospital_id', 'submitted_by', 'hospital_name', 'sender_name',
        'period_from', 'period_to', 'filters', 'mixture_count', 'conciliable_count', 'snapshot', 'request_hash',
    ];

    protected $casts = [
        'hospital_id' => 'integer', 'submitted_by' => 'integer', 'period_from' => 'date', 'period_to' => 'date',
        'filters' => 'array', 'snapshot' => 'array', 'mixture_count' => 'integer', 'conciliable_count' => 'integer',
    ];

    public function folio(): string { return 'CON-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT); }

    public function summary(): array
    {
        return \App\Services\HospitalConciliationSummary::summarize($this->snapshot, $this->hospital_name,
            $this->period_from?->toDateString(), $this->period_to?->toDateString());
    }

    public function periodLabel(): string
    {
        if (! $this->period_from && ! $this->period_to) return 'Todo el historial';
        return ($this->period_from?->format('d/m/Y') ?? 'Sin fecha inicial').' - '.($this->period_to?->format('d/m/Y') ?? 'Sin fecha final');
    }
}
