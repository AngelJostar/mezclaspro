<?php

namespace App\Services;

use App\Models\MixtureMessage;
use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\Mezcla;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MixtureMessagingService
{
    public function side(User $user): string
    {
        return $user->hasAnyRole(['Cliente', 'Institucion']) ? 'hospital' : 'central';
    }

    public function targets(User $user, array $keys): Collection
    {
        if (!$user->hasAnyRole(['Admin', 'Super Admin', 'Cliente', 'Institucion']) || $user->is_active === false) {
            return collect();
        }
        $hospitalSide = $this->side($user) === 'hospital';
        if ($hospitalSide && !$user->hospital_id) return collect();
        $result = collect();
        foreach (['nutricionales', 'oncologicos', 'antibioticos'] as $kind) {
            $permission = $kind === 'nutricionales' ? 'nutricionales_solicitudes_index' : 'oncologicos_solicitudes_index';
            if (!$user->can($permission)) continue;
            $ids = collect($keys)->filter(fn ($key) => str_starts_with($key, $kind.':'))
                ->map(fn ($key) => (int) substr($key, strlen($kind) + 1))->unique();
            if ($ids->isEmpty()) continue;
            if ($kind === 'nutricionales') {
                $query = Solicitud::with(['hospital', 'solicitud_patient'])->whereIn('id', $ids);
                if ($hospitalSide) $query->where('user_id', $user->id);
            } else {
                $query = Mezcla::with('solicitud.hospital')->whereIn('id', $ids)
                    ->whereHas('solicitud', function ($query) use ($kind, $hospitalSide, $user) {
                        $query->where('tipo_solicitud', $kind);
                        if ($hospitalSide) $query->where('hospital_id', $user->hospital_id);
                    });
            }
            foreach ($query->get() as $model) {
                $hospital = $kind === 'nutricionales' ? $model->hospital : $model->solicitud?->hospital;
                if (!$hospital || ($hospitalSide && (int) $hospital->id !== (int) $user->hospital_id)) continue;
                $result->put($kind.':'.$model->id, [
                    'kind' => $kind, 'id' => $model->id, 'hospital_id' => $hospital->id,
                    'hospital' => $hospital->name,
                    'patient' => $kind === 'nutricionales'
                        ? trim(($model->solicitud_patient?->nombre_paciente ?? '').' '.($model->solicitud_patient?->apellidos_paciente ?? ''))
                        : ($model->solicitud?->nombre_paciente ?? ''),
                    'model' => $model,
                ]);
            }
        }
        return $result;
    }

    public function resolve(User $user, string $kind, int $id): array
    {
        $target = $this->targets($user, [$kind.':'.$id])->get($kind.':'.$id);
        abort_unless($target, 404);
        return $target;
    }

    public function messages(array $target)
    {
        return MixtureMessage::query()->where('kind', $target['kind'])->where('target_id', $target['id'])
            ->where('hospital_id', $target['hospital_id']);
    }

    public function summaries(User $user, Collection $targets): array
    {
        if ($targets->isEmpty()) return [];
        $query = DB::table('mixture_messages as messages')
            ->leftJoin('mixture_message_reads as reads', function ($join) use ($user) {
                $join->on('reads.kind', '=', 'messages.kind')->on('reads.target_id', '=', 'messages.target_id')
                    ->on('reads.hospital_id', '=', 'messages.hospital_id')->where('reads.user_id', $user->id);
            })->where(function ($query) use ($targets) {
                foreach ($targets as $target) {
                    $query->orWhere(fn ($query) => $query->where('messages.kind', $target['kind'])
                        ->where('messages.target_id', $target['id'])->where('messages.hospital_id', $target['hospital_id']));
                }
            })->select('messages.kind', 'messages.target_id')
            ->selectRaw('COUNT(*) as total, MAX(messages.id) as latest_id')
            ->selectRaw('SUM(CASE WHEN messages.sender_side <> ? AND messages.id > COALESCE(reads.last_message_id, 0) THEN 1 ELSE 0 END) as unread', [$this->side($user)])
            ->groupBy('messages.kind', 'messages.target_id')->get()
            ->keyBy(fn ($row) => $row->kind.':'.$row->target_id);
        return $targets->map(function ($target, $key) use ($query) {
            $row = $query->get($key);
            return ['total' => (int) ($row?->total ?? 0), 'unread' => (int) ($row?->unread ?? 0), 'latest_id' => (int) ($row?->latest_id ?? 0)];
        })->all();
    }

    public function send(User $user, array $target, string $body, string $token): MixtureMessage
    {
        return DB::transaction(function () use ($user, $target, $body, $token) {
            $target['model']->newQuery()->whereKey($target['id'])->lockForUpdate()->firstOrFail();
            $target = $this->resolve($user, $target['kind'], $target['id']);
            $side = $this->side($user);
            abort_if($side === 'central' && !$this->messages($target)->where('sender_side', 'hospital')->exists(), 403,
                'El hospital debe iniciar la conversación.');
            $message = $this->messages($target)->firstOrCreate([
                'author_id' => $user->id, 'client_token' => $token,
            ], [
                'kind' => $target['kind'], 'target_id' => $target['id'], 'hospital_id' => $target['hospital_id'],
                'author_name' => $user->name, 'sender_side' => $side, 'body' => $body,
            ]);
            abort_unless($message->body === $body, 409, 'Este envío ya fue registrado con otro contenido.');
            return $message;
        });
    }

    public function markRead(User $user, array $target, int $throughId): void
    {
        abort_unless($this->messages($target)->whereKey($throughId)->exists(), 422);
        $key = ['user_id' => $user->id, 'kind' => $target['kind'], 'target_id' => $target['id'], 'hospital_id' => $target['hospital_id']];
        DB::table('mixture_message_reads')->insertOrIgnore($key + ['last_message_id' => 0, 'updated_at' => now()]);
        DB::table('mixture_message_reads')->where($key)->where('last_message_id', '<', $throughId)
            ->update(['last_message_id' => $throughId, 'updated_at' => now()]);
    }

    public function serialize(MixtureMessage $message): array
    {
        return ['id' => $message->id, 'author' => $message->author_name, 'side' => $message->sender_side,
            'body' => $message->body, 'sent_at' => $message->created_at->toIso8601String()];
    }
}
