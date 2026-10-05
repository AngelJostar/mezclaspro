<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestQuotation extends Model
{
    protected static function booted(): void
    {
        static::saved(fn (self $quotation) => app(\App\Services\QuotationWorkflowService::class)->record($quotation));
    }

    public const STATUS_FILTERS = [
        'todas' => 'Todas',
        'recibidas' => 'Recibidas',
        'enviadas' => 'Enviadas',
        'autorizadas' => 'Autorizadas',
        'preparacion' => 'En preparacion',
    ];

    protected $guarded = ['id', 'status', 'authorized_by', 'authorized_at', 'request_id'];

    protected $casts = [
        'clinical_data' => 'array',
        'pricing_snapshot' => 'array',
        'total' => 'decimal:2',
        'sent_at' => 'datetime',
        'authorized_at' => 'datetime',
    ];

    protected $hidden = ['attachment_path', 'submission_key'];

    public static function canCreate(User $user, string $category): bool
    {
        $permissionType = $category === 'antibioticos' ? 'oncologicos' : $category;
        return in_array($category, ['oncologicos', 'nutricionales', 'antibioticos'], true)
            && ($user->isSalesperson() || ($user->can($permissionType.'_solicitudes_index')
                && $user->can($permissionType.'_solicitudes_create')));
    }

    public function canBeViewedBy(User $user): bool
    {
        $type = $this->category === 'nutricionales' ? 'nutricionales' : 'oncologicos';
        $laboratoryId = $user->hasAnyRole(['Admin', 'Super Admin', 'Cliente', 'Institucion']) || $user->isSalesperson()
            ? null : $user->personnelProfile?->laboratory_id;

        return ($user->isSalesperson() || $user->can($type.'_solicitudes_index'))
            && (!$user->hasSalesOnlyAccess() || (int) $this->created_by === (int) $user->id
                || ((int) $this->seller_id === (int) $user->id && $this->status !== 'borrador')
                || (!$this->seller_id && $this->status !== 'borrador' && $this->hospital?->salespeople()->where('users.id', $user->id)->exists()))
            && (!$user->hasAnyRole(['Cliente', 'Institucion'])
                || ($user->hospital_id && (int) $user->hospital_id === (int) $this->hospital_id))
            && (!$laboratoryId || (int) $this->hospital?->laboratory_id === (int) $laboratoryId);
    }

    public function canBeEditedBy(User $user): bool
    {
        return $this->status === 'borrador' && $this->canBeViewedBy($user)
            && static::canCreate($user, $this->category)
            && ((int) $this->created_by === (int) $user->id || !$user->hasAnyRole(['Cliente', 'Institucion']));
    }

    public function canPrepareBy(User $user): bool
    {
        $type = $this->category === 'nutricionales' ? 'nutricionales' : 'oncologicos';
        return $this->canBeViewedBy($user) && $user->can($type.'_solicitudes_create')
            && $user->can($type.'_solicitudes_store');
    }

    public function canAttachRequestBy(User $user): bool
    {
        return $this->canBeViewedBy($user)
            && (static::canCreate($user, $this->category) || $this->canBeAuthorizedBy($user));
    }

    public function canStartPreparationBy(User $user): bool
    {
        return !$this->request_id && $this->status === 'autorizada' && $this->canPrepareBy($user)
            && ($this->documents_count ?? $this->documents()->count()) > 0;
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RequestQuotationDocument::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institucion::class, 'institution_id');
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function getSellerNameAttribute(): string
    {
        return $this->seller ? trim($this->seller->name.' '.$this->seller->lastname) : 'Sin asignar';
    }

    public function scopeForSalesperson($query, User $user)
    {
        $laboratoryId = $user->hasAnyRole(['Admin', 'Super Admin', 'Cliente', 'Institucion']) || $user->isSalesperson()
            ? null : $user->personnelProfile?->laboratory_id;
        return $query->when($laboratoryId, fn ($q) => $q->whereHas('hospital', fn ($h) => $h->where('laboratory_id', $laboratoryId)))
            ->when($user->hasSalesOnlyAccess(), fn ($query) => $query->where(
            fn ($query) => $query->where('created_by', $user->id)->orWhere(
                fn ($assigned) => $assigned->where('seller_id', $user->id)->where('status', '<>', 'borrador')
            )->orWhere(fn ($unassigned) => $unassigned->whereNull('seller_id')->where('status', '<>', 'borrador')
                ->whereHas('hospital.salespeople', fn ($s) => $s->where('users.id', $user->id)))
            )
        );
    }

    public function getFolioAttribute(): string
    {
        return 'COT-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function canBeAuthorizedBy(User $user): bool
    {
        $permissionType = $this->category === 'nutricionales' ? 'nutricionales' : 'oncologicos';

        return $this->canBeViewedBy($user) && !$user->hasAnyRole(['Cliente', 'Institucion'])
            && $user->can($permissionType.'_solicitudes_index')
            && $user->can($permissionType.'_solicitudes_update');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'enviada' => 'Enviada',
            'autorizada' => 'Autorizada',
            'preparacion' => 'En preparacion',
            default => 'Borrador',
        };
    }
}
