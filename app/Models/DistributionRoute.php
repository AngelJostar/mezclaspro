<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DistributionRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'route_type',
        'schedule_start',
        'schedule_end',
        'status',
        'qr_token',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hospitals(): BelongsToMany
    {
        return $this->belongsToMany(Hospital::class, 'distribution_route_hospital')
            ->withPivot(['id', 'stop_order', 'completed_at'])
            ->withTimestamps()
            ->orderByPivot('stop_order');
    }

    public function messengers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'distribution_route_messenger')
            ->withTimestamps();
    }
}
