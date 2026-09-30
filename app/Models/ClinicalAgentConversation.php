<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalAgentConversation extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['messages'];
    protected $casts = ['messages' => 'encrypted:array'];
}
