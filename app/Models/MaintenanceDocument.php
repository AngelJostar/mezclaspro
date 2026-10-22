<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceDocument extends Model
{
    protected $fillable = ['provider_id', 'appointment_id', 'name', 'path', 'mime', 'size', 'description'];
}
