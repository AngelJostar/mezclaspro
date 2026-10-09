<?php

namespace App\Services\ValidationRules;

class CompositionRuleEngine
{
    /**
     * Evaluates normalized composition facts. It does not read or alter a request.
     *
     * @param array{active_groups?: array, active_component_ids?: array, calculated_water?: bool} $facts
     */
    public function evaluate(array $configuration, array $facts): array
    {
        if (($configuration['mode'] ?? null) === 'allowed_catalog') {
            return $this->evaluateCatalog($configuration, $facts);
        }

        $groups = array_values(array_unique($facts['active_groups'] ?? []));
        if (($configuration['count_calculated_water'] ?? false) && ($facts['calculated_water'] ?? false)) {
            $groups[] = 'water';
            $groups = array_values(array_unique($groups));
        }
        $components = array_map('intval', array_values(array_unique($facts['active_component_ids'] ?? [])));
        $requiredGroups = $configuration['required_groups'] ?? [];
        $forbiddenGroups = $configuration['forbidden_groups'] ?? [];
        $requiredComponents = array_map('intval', $configuration['required_components'] ?? []);
        $forbiddenComponents = array_map('intval', $configuration['forbidden_components'] ?? []);

        $missingGroups = array_values(array_diff($requiredGroups, $groups));
        $presentForbiddenGroups = array_values(array_intersect($forbiddenGroups, $groups));
        $missingComponents = array_values(array_diff($requiredComponents, $components));
        $presentForbiddenComponents = array_values(array_intersect($forbiddenComponents, $components));
        $unexpectedGroups = ($configuration['match_mode'] ?? 'contains') === 'exact'
            ? array_values(array_diff($groups, $requiredGroups))
            : [];
        $minimum = $configuration['minimum_active_components'] ?? null;
        $minimumMet = $minimum === null || count($components) >= (int) $minimum;

        return [
            'passed' => !$missingGroups && !$presentForbiddenGroups && !$missingComponents
                && !$presentForbiddenComponents && !$unexpectedGroups && $minimumMet,
            'result' => [
                'missing_groups' => $missingGroups,
                'forbidden_groups_present' => $presentForbiddenGroups,
                'missing_component_ids' => $missingComponents,
                'forbidden_component_ids_present' => $presentForbiddenComponents,
                'unexpected_groups' => $unexpectedGroups,
                'minimum_components_met' => $minimumMet,
            ],
        ];
    }

    private function evaluateCatalog(array $configuration, array $facts): array
    {
        $groups = array_values(array_unique($facts['active_groups'] ?? []));
        $terms = array_values(array_unique($facts['active_component_terms'] ?? []));
        $componentCount = count(array_unique($facts['active_component_ids'] ?? $terms));
        if (($configuration['allow_any_single_component'] ?? false) && $componentCount === 1) {
            return ['passed' => true, 'result' => ['matched_code' => 'single-component-exception']];
        }

        $comparisons = [];
        foreach ($configuration['allowed_compositions'] ?? [] as $candidate) {
            $expectedGroups = array_values(array_unique($candidate['groups'] ?? []));
            $requiredTerms = $candidate['component_terms'] ?? [];
            $forbiddenTerms = $candidate['forbidden_component_terms'] ?? [];
            $missingGroups = array_values(array_diff($expectedGroups, $groups));
            $unexpectedGroups = array_values(array_diff($groups, $expectedGroups));
            $missingTerms = array_values(array_diff($requiredTerms, $terms));
            $forbiddenTermsPresent = array_values(array_intersect($forbiddenTerms, $terms));

            if (! $missingGroups && ! $unexpectedGroups && ! $missingTerms && ! $forbiddenTermsPresent) {
                return ['passed' => true, 'result' => ['matched_code' => $candidate['code'] ?? null]];
            }

            $comparisons[] = [
                'code' => $candidate['code'] ?? null,
                'label' => $candidate['label'] ?? $candidate['code'] ?? 'Composición permitida',
                'missing_groups' => $missingGroups,
                'unexpected_groups' => $unexpectedGroups,
                'missing_terms' => $missingTerms,
                'forbidden_terms_present' => $forbiddenTermsPresent,
                'similarity' => count(array_intersect($expectedGroups, $groups))
                    / max(1, count(array_unique(array_merge($expectedGroups, $groups)))),
                'distance' => count($missingGroups) + count($unexpectedGroups)
                    + count($missingTerms) + count($forbiddenTermsPresent),
            ];
        }

        usort($comparisons, fn (array $left, array $right) =>
            ($right['similarity'] <=> $left['similarity'])
            ?: ($left['distance'] <=> $right['distance'])
        );

        return ['passed' => false, 'result' => [
            'matched_code' => null,
            'reason' => 'composition_not_in_allowed_catalog',
            'detected_groups' => $groups,
            'detected_component_names' => array_values(array_unique($facts['active_component_names'] ?? [])),
            'closest_alternatives' => array_slice($comparisons, 0, 3),
        ]];
    }
}
