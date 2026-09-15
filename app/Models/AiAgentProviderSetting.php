<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAgentProviderSetting extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['api_key'];
    protected $casts = ['api_key' => 'encrypted'];
}
