<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabelPrint extends Model
{
    public const TYPE_INITIAL = 'initial';
    public const TYPE_REPRINT = 'reprint';

    protected $fillable = [
        'document_type',
        'document_id',
        'copies',
        'print_type',
        'requested_by',
        'authorized_by',
        'ip_address',
    ];

    protected $casts = [
        'copies' => 'integer',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function authorizer()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
