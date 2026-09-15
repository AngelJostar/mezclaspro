<?php

namespace App\Notifications;

use App\Models\MixtureAdjustment;
use Illuminate\Notifications\Notification;

class MixtureAdjustmentRequested extends Notification
{
    public function __construct(private MixtureAdjustment $adjustment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->adjustment->label.' para la mezcla #'.$this->adjustment->target_id.'.',
            'adjustment_id' => $this->adjustment->id,
        ];
    }
}
