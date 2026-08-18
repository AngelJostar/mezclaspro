<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionReportTemplate extends Model
{
    protected $fillable = [
        'report_key',
        'title',
        'subtitle',
        'columns',
        'info_boxes',
        'free_fields',
    ];

    protected $casts = [
        'columns' => 'array',
        'info_boxes' => 'array',
        'free_fields' => 'array',
    ];
}
