<?php

use App\Services\Clinical\ClinicalEvidence;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach (DB::table('ai_agents')->where('integration_key', ClinicalEvidence::KEY)->get(['id', 'instructions']) as $agent) {
            if (str_contains($agent->instructions ?? '', ClinicalEvidence::SUGGESTION_POLICY)) continue;
            DB::table('ai_agents')->where('id', $agent->id)->update([
                'instructions' => trim(($agent->instructions ?? '')."\n\n".ClinicalEvidence::SUGGESTION_POLICY),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Preserve the clinical policy and subsequent edits to agent instructions.
    }
};
