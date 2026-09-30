<?php

use App\Services\Clinical\ClinicalEvidence;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach (DB::table('ai_agents')->where('integration_key', ClinicalEvidence::KEY)->get(['id', 'instructions']) as $agent) {
            $instructions = $agent->instructions ?? '';
            if (str_contains($instructions, ClinicalEvidence::PARAMETER_SCOPE_POLICY)) continue;
            DB::table('ai_agents')->where('id', $agent->id)->update([
                'instructions' => trim($instructions."\n\n".ClinicalEvidence::PARAMETER_SCOPE_POLICY),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Preserve subsequent profile edits and historical review records.
    }
};
