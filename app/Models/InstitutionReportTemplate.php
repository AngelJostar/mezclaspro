<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionReportTemplate extends Model
{
    protected $fillable = [
        'report_key',
        'name',
        'is_custom',
        'is_published',
        'data_source',
        'title',
        'subtitle',
        'columns',
        'info_boxes',
        'free_fields',
        'layout',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
        'is_published' => 'boolean',
        'columns' => 'array',
        'info_boxes' => 'array',
        'free_fields' => 'array',
        'layout' => 'array',
        'published_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
