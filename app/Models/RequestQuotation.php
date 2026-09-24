<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestQuotation extends Model
{
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

        return ($user->isSalesperson() || $user->can($type.'_solicitudes_index'))
            && (!$user->hasSalesOnlyAccess() || (int) $this->created_by === (int) $user->id
                || ((int) $this->seller_id === (int) $user->id && $this->status !== 'borrador'))
            && (!$user->hasAnyRole(['Cliente', 'Institucion'])
                || ($user->hospital_id && (int) $user->hospital_id === (int) $this->hospital_id));
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
        return $query->when($user->hasSalesOnlyAccess(), fn ($query) => $query->where(
            fn ($query) => $query->where('created_by', $user->id)->orWhere(
                fn ($assigned) => $assigned->where('seller_id', $user->id)->where('status', '<>', 'borrador')
            )
        ));
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
