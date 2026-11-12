<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAgentDelivery extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['period_from' => 'date', 'period_to' => 'date'];
    public function agent() { return $this->belongsTo(AiAgent::class, 'ai_agent_id'); }
    public function submission() { return $this->belongsTo(HospitalConciliationSubmission::class, 'submission_id'); }
    public function emails() { return $this->hasMany(AiAgentDeliveryEmail::class); }
}
