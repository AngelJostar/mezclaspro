<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('solicituds')
            ->whereNull('validated_at')
            ->whereIn('estado', ['aprobada', 'preparada', 'revisada', 'entregada'])
            ->orderBy('id')
            ->chunkById(100, function ($requests) {
                foreach ($requests as $request) {
                    $movement = DB::table('medicine_stock_movements as movement')
                        ->join('users as actor', 'actor.id', '=', 'movement.user_id')
                        ->where('movement.reference_type', 'Solicitud')
                        ->where('movement.reference_id', $request->id)
                        ->where('movement.tipo', 'salida')
                        ->whereNotNull('movement.created_at')
                        ->orderBy('movement.created_at')
                        ->orderBy('movement.id')
                        ->first(['movement.created_at', 'actor.username', 'actor.name', 'actor.lastname']);

                    if (! $movement
                        || ($request->fecha_hora_preparacion
                            && $movement->created_at > $request->fecha_hora_preparacion)) {
                        continue;
                    }

                    $name = trim((string) ($movement->username ?: trim(($movement->name ?? '').' '.($movement->lastname ?? ''))));
                    if ($name === '') {
                        continue;
                    }

                    DB::table('solicituds')->where('id', $request->id)->update([
                        'validated_by' => $name,
                        'validated_at' => $movement->created_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // La traza recuperada es evidencia histórica; no se elimina al revertir.
    }
};
