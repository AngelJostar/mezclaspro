<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceProvider extends Model
{
    protected $fillable = ['name', 'specialty', 'contact_name', 'email', 'phone', 'coverage', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function services()
    {
        return $this->belongsToMany(MaintenanceService::class, 'maintenance_provider_service', 'provider_id', 'service_id');
    }

    public function documents()
    {
        return $this->hasMany(MaintenanceDocument::class, 'provider_id')->latest();
    }
}
