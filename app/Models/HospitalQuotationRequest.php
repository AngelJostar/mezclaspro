<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalQuotationRequest extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['items' => 'array', 'capture_data' => 'array'];
    protected $hidden = ['attachment_path', 'submission_key'];

    public function hospital() { return $this->belongsTo(Hospital::class); }
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
    public function quotation() { return $this->belongsTo(RequestQuotation::class, 'quotation_id'); }

    public function getFolioAttribute(): string { return 'SC-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT); }

    public function scopeForSeller($query, User $user)
    {
        return $query->where(fn ($q) => $q->where('seller_id', $user->id)
            ->orWhere(fn ($q) => $q->whereNull('seller_id')->whereHas('hospital.salespeople', fn ($s) => $s->where('users.id', $user->id))));
    }

    public function canQuote(User $user): bool
    {
        if ($user->hasAnyRole(['Cliente', 'Institucion']) || !$user->is_active) return false;
        if ($user->hasSalesOnlyAccess()) return static::forSeller($user)->whereKey($this->id)->exists();
        return RequestQuotation::canCreate($user, $this->category);
    }

    public function summary(): array
    {
        return ['id' => $this->id, 'folio' => $this->folio, 'hospital_id' => $this->hospital_id,
            'hospital' => $this->hospital?->name, 'category' => $this->category, 'patient_name' => $this->patient_name,
            'items' => $this->items, 'observations' => $this->observations, 'capture_data' => $this->capture_data,
            'has_attachment' => (bool) $this->attachment_path, 'created_at' => $this->created_at?->toIso8601String(),
            'seller_id' => $this->seller_id, 'attachment_type' => $this->attachment_path ? pathinfo($this->attachment_path, PATHINFO_EXTENSION) : null,
            'seller' => $this->seller ? trim($this->seller->name.' '.$this->seller->lastname) : 'Pendiente de asignación',
            'quotation_id' => $this->quotation_id, 'quotation_folio' => $this->quotation?->folio,
            'status' => $this->quotation ? ($this->quotation->status === 'borrador' ? 'En revisión por Ventas' : 'Cotización disponible') : 'Enviada a Ventas'];
    }
}
