<?php

namespace App\Services\Agents;

use App\Models\AiAgent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgentOrder
{
    public static function agents(User $user)
    {
        $ids = json_decode(DB::table('ai_agent_user_orders')->where('user_id', $user->id)->value('agent_ids') ?? '[]', true);
        $positions = array_flip($ids);
        return AiAgent::orderBy('name')->get()->sortBy(fn ($agent) => $positions[$agent->id] ?? PHP_INT_MAX)->values();
    }

    public static function save(User $user, array $ids): void
    {
        abort_unless($user->hasRole('Super Admin'), 403);
        $ids = validator(['ids' => $ids], ['ids' => 'required|array|max:1000', 'ids.*' => 'required|integer|distinct'])->validate()['ids'];
        $ids = array_map('intval', $ids);
        $current = AiAgent::pluck('id')->all();
        if (count($ids) !== count($current) || array_diff($ids, $current)) {
            throw ValidationException::withMessages(['agentOrder' => 'La lista de agentes cambió. Recarga la pantalla para volver a ordenarla.']);
        }
        DB::table('ai_agent_user_orders')->updateOrInsert(['user_id' => $user->id], [
            'agent_ids' => json_encode($ids), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
