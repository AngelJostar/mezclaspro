<?php

use App\Models\ValidationRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('validation_rules')) return;

        ValidationRule::query()
            ->where('engine', 'mathematical')
            ->where('configuration->source_variable', 'groups.calcium_phosphate.total_meq')
            ->get()
            ->each(function (ValidationRule $rule): void {
                $configuration = $rule->configuration ?? [];
                if (($configuration['formula_template'] ?? null) !== 'amount_per_1000_ml') return;

                $configuration['formula_template'] = 'volume_capacity_per_1000_ml';
                $configuration['unit'] = 'mEq';
                $configuration['failure_message'] = 'La suma de calcio y fosfato excede la capacidad máxima que soporta el volumen total.';
                $rule->forceFill([
                    'configuration' => $configuration,
                    'version' => $rule->version + 1,
                ])->save();
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('validation_rules')) return;

        ValidationRule::query()
            ->where('engine', 'mathematical')
            ->where('configuration->source_variable', 'groups.calcium_phosphate.total_meq')
            ->get()
            ->each(function (ValidationRule $rule): void {
                $configuration = $rule->configuration ?? [];
                if (($configuration['formula_template'] ?? null) !== 'volume_capacity_per_1000_ml') return;

                $configuration['formula_template'] = 'amount_per_1000_ml';
                $configuration['unit'] = 'mEq/1000 mL';
                $rule->forceFill([
                    'configuration' => $configuration,
                    'version' => $rule->version + 1,
                ])->save();
            });
    }
};
