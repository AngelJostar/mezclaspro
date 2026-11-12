<?php

namespace App\Services\Agents;

use App\Mail\AgentConciliationSent;
use App\Models\AiAgentDeliveryEmail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class ConciliationDeliveryMailer
{
    public function pending(): void
    {
        AiAgentDeliveryEmail::whereIn('status', ['pending', 'failed'])->where('attempts', '<', 5)
            ->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->orderBy('id')->limit(100)->pluck('id')->each(fn ($id) => $this->send($id));
    }

    public function send(int $id): void
    {
        $lock = Cache::lock('agent-delivery-mail:'.$id, 120);
        if (!$lock->get()) return;
        try {
            $mail = AiAgentDeliveryEmail::with('delivery.agent', 'delivery.submission')->findOrFail($id);
            $agent = $mail->delivery->agent;
            if ($mail->status === 'sent' || !$agent->is_active || !($agent->configuration['email_enabled'] ?? false)) return;
            $actor = User::find($agent->configured_by);
            if (!$actor?->hasRole('Super Admin') || (isset($actor->is_active) && !$actor->is_active)) return;
            $transport = config('mail.mailers.'.config('mail.default').'.transport');
            if (in_array($transport, [null, 'log', 'array'], true)) {
                $mail->update(['error' => 'Configura un transporte real de correo antes de enviar notificaciones.', 'next_attempt_at' => now()->addHour()]);
                return;
            }
            $mail->increment('attempts');
            try {
                Mail::to($mail->recipient)->send(new AgentConciliationSent($mail->delivery->submission));
                $mail->update(['status' => 'sent', 'sent_at' => now(), 'error' => null, 'next_attempt_at' => null]);
            } catch (\Throwable $e) {
                report($e);
                $mail->update(['status' => 'failed', 'error' => 'No se pudo enviar el correo. Revisa la configuración de correo y el destinatario.',
                    'next_attempt_at' => now()->addMinutes(15)]);
            }
        } finally { $lock->release(); }
    }
}
