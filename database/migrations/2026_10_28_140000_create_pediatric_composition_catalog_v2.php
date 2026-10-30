<?php

use App\Models\ValidationRule;
use App\Support\PediatricNutritionalCompositionCatalogV2;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('validation_rules')) {
            return;
        }

        $current = ValidationRule::query()
            ->where('code', 'NP.R6.PEDIATRIC.COMP.CATALOG')
            ->first();
        $currentCompositions = data_get($current?->configuration, 'allowed_compositions', []);
        $documentCompositions = PediatricNutritionalCompositionCatalogV2::compositions();

        $compositions = collect([...$currentCompositions, ...$documentCompositions])
            ->unique(fn (array $composition) => $this->signature($composition))
            ->values()
            ->all();

        ValidationRule::query()->firstOrCreate(
            ['code' => 'NP.R6.PEDIATRIC.COMP.CATALOG.V2'],
            [
                'name' => 'Combinaciones permitidas R6 para pediatría versión 2',
                'engine' => 'composition',
                'population' => 'pediatric',
                'severity' => 'blocking',
                'status' => 'review',
                'description' => 'Catálogo pediátrico ampliado con las combinaciones nutricionales validadas en la revisión 2.0.',
                'configuration' => [
                    'mode' => 'allowed_catalog',
                    'allow_any_single_component' => (bool) data_get($current?->configuration, 'allow_any_single_component', true),
                    'manual_declared_count' => count($compositions),
                    'allowed_compositions' => $compositions,
                    'supersedes_codes' => $current ? [$current->code] : [],
                    'review_notes' => [
                        'Incluye 156 combinaciones del catálogo pediátrico 2.0 y conserva alternativas pediátricas vigentes no duplicadas.',
                        'Las 156 alternativas incorporadas desde el catálogo 2.0 requieren aminoácidos pediátricos 10%.',
                        'Al aprobar esta versión se desactiva automáticamente el catálogo pediátrico anterior.',
                    ],
                ],
                'version' => 1,
                'is_enforced' => false,
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('validation_rules')) {
            return;
        }

        $rule = ValidationRule::query()->where('code', 'NP.R6.PEDIATRIC.COMP.CATALOG.V2')->first();
        if ($rule && ! $rule->is_enforced && ! $rule->approved_at) {
            $rule->delete();
        }
    }

    private function signature(array $composition): string
    {
        $parts = collect([
            'groups' => $composition['groups'] ?? [],
            'component_terms' => $composition['component_terms'] ?? [],
            'forbidden_component_terms' => $composition['forbidden_component_terms'] ?? [],
        ])->map(function ($values) {
            $values = array_values(array_unique($values));
            sort($values);

            return implode(',', $values);
        });

        return $parts->implode('|');
    }
};
