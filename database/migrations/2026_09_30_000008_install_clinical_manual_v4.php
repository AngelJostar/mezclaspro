<?php

use App\Services\Clinical\ClinicalEvidence;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            if (Artisan::call('clinical:import-manual', ['path' => resource_path(ClinicalEvidence::MANUAL_FILE), '--manual-version' => '4']) !== 0) {
                throw new RuntimeException('No se pudo instalar el Manual Maestro de Validacion V4.');
            }
            foreach (DB::table('ai_agents')->where('integration_key', ClinicalEvidence::KEY)->get(['id', 'instructions']) as $agent) {
                if (str_contains($agent->instructions ?? '', ClinicalEvidence::MANUAL_POLICY)) continue;
                DB::table('ai_agents')->where('id', $agent->id)->update([
                    'instructions' => trim(ClinicalEvidence::MANUAL_POLICY."\n\n".($agent->instructions ?? '')),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Never reactivate superseded clinical criteria or erase historical reviews on rollback.
    }
};
