<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVIEW = 'review';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'assigned_buyer_id',
        'name',
        'commercial_name',
        'rfc',
        'contact_name',
        'phone',
        'email',
        'fax',
        'category',
        'subcategory',
        'location',
        'municipality',
        'postal_code',
        'address',
        'bank_details',
        'bank_name',
        'bank_account',
        'bank_clabe',
        'bank_reference',
        'tax_certificate_path',
        'bank_cover_path',
        'additional_document_path',
        'status',
        'created_by',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Activo',
            self::STATUS_REVIEW => 'En revision',
            self::STATUS_INACTIVE => 'Inactivo',
        ];
    }

    public function scopeAvailableForPurchases(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedBuyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_buyer_id');
    }
}
