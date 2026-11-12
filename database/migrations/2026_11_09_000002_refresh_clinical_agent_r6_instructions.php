<?php

use App\Services\Clinical\ClinicalEvidence;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ai_agents')
            ->where('integration_key', ClinicalEvidence::KEY)
            ->where(function ($query) {
                $query->where('instructions', 'like', '%Manual Maestro de Validacion V4%')
                    ->orWhere('instructions', 'like', '%Estructura V4:%');
            })
            ->update([
                'instructions' => ClinicalEvidence::INSTRUCTIONS,
                'source_fingerprint' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No se restaura una política clínica sustituida para evitar reactivar instrucciones obsoletas.
    }
};
