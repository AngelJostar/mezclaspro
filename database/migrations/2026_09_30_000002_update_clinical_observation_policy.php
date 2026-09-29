<?php

use App\Services\Clinical\ClinicalEvidence;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('clinical_sources', 'allows_chemical_medical_authorization')) {
            Schema::table('clinical_sources', fn (Blueprint $table) => $table->boolean('allows_chemical_medical_authorization')->default(false));
        }
        // Existing dose exceptions do not approve chemical exceptions. Preserve the agent's custom profile.
        foreach (DB::table('ai_agents')->where('integration_key', ClinicalEvidence::KEY)->get(['id', 'instructions']) as $agent) {
            if (str_contains($agent->instructions ?? '', ClinicalEvidence::OBSERVATION_POLICY)) continue;
            DB::table('ai_agents')->where('id', $agent->id)->update([
                'instructions' => trim(($agent->instructions ?? '')."\n\n".ClinicalEvidence::OBSERVATION_POLICY),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('clinical_sources', fn (Blueprint $table) => $table->dropColumn('allows_chemical_medical_authorization'));
        // Do not remove safety instructions or overwrite subsequent user edits on rollback.
    }
};
