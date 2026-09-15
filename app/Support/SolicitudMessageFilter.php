<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class SolicitudMessageFilter
{
    public static function apply(Builder $query, string $kind): void
    {
        $table = $query->getModel()->getTable();
        $query->whereExists(function ($messages) use ($kind, $table) {
            $messages->selectRaw('1')->from('mixture_messages')
                ->where('mixture_messages.kind', $kind)
                ->whereColumn('mixture_messages.target_id', $table.'.id');
            if ($kind === 'nutricionales') {
                $messages->join('users as message_users', 'message_users.id', '=', $table.'.user_id')
                    ->whereColumn('mixture_messages.hospital_id', 'message_users.hospital_id');
            } else {
                $messages->join('solicitud_oncos as message_requests', 'message_requests.id', '=', $table.'.solicitud_id')
                    ->where('message_requests.tipo_solicitud', $kind)
                    ->whereColumn('mixture_messages.hospital_id', 'message_requests.hospital_id');
            }
        });
    }
}
