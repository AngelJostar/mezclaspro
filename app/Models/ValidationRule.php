<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationRule extends Model
{
    public const ENGINES = ['composition', 'mathematical'];
    public const POPULATIONS = ['adult', 'pediatric', 'both'];
    public const SEVERITIES = ['blocking', 'authorization', 'advisory', 'information'];
    public const EDITABLE_STATUSES = ['draft', 'review', 'inactive'];

    protected $fillable = [
        'code', 'name', 'engine', 'population', 'severity', 'status', 'description',
        'configuration', 'version', 'is_enforced', 'created_by', 'updated_by',
        'approved_by', 'approved_at',
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_enforced' => 'boolean',
        'version' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
