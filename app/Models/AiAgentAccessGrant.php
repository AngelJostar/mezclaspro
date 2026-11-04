<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAgentAccessGrant extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['can_view' => 'boolean', 'can_run' => 'boolean', 'can_edit' => 'boolean'];
}
