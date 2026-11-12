<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAgentDeliveryEmail extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['next_attempt_at' => 'datetime', 'sent_at' => 'datetime'];
    public function delivery() { return $this->belongsTo(AiAgentDelivery::class, 'ai_agent_delivery_id'); }
}
