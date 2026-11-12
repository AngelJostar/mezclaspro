<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\User;
use App\Models\ValidationRule;
use App\Services\Clinical\ClinicalEvidence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ValidationRuleApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_rule_can_be_approved_and_then_deactivated(): void
    {
        $admin = $this->superadministrator();
        $rule = $this->rule(['status' => 'review']);

        $this->actingAs($admin)
            ->patch(route('admin.superadministrator.validation-rules.approve', $rule))
            ->assertRedirect()
            ->assertSessionHas('status');

        $rule->refresh();
        $this->assertSame('active', $rule->status);
        $this->assertTrue($rule->is_enforced);
        $this->assertSame($admin->id, $rule->approved_by);
        $this->assertNotNull($rule->approved_at);

        $this->actingAs($admin)
            ->patch(route('admin.superadministrator.validation-rules.deactivate', $rule))
            ->assertRedirect()
            ->assertSessionHas('status');

        $rule->refresh();
        $this->assertSame('inactive', $rule->status);
        $this->assertFalse($rule->is_enforced);
        $this->assertSame($admin->id, $rule->approved_by);
        $this->assertNotNull($rule->approved_at);
    }

    public function test_draft_rule_cannot_be_approved(): void
    {
        $admin = $this->superadministrator();
        $rule = $this->rule(['status' => 'draft']);

        $this->actingAs($admin)
            ->patch(route('admin.superadministrator.validation-rules.approve', $rule))
            ->assertSessionHasErrors('rule');

        $rule->refresh();
        $this->assertSame('draft', $rule->status);
        $this->assertFalse($rule->is_enforced);
        $this->assertNull($rule->approved_at);
    }

    public function test_health_responsible_assistant_can_view_and_approve_but_cannot_manage_rules(): void
    {
        $assistant = User::factory()->create(['hospital_id' => null]);
        $assistant->assignRole(Role::firstOrCreate([
            'name' => 'Auxiliar de responsable sanitario',
            'guard_name' => 'web',
        ]));
        $rule = $this->rule(['status' => 'review']);

        $this->actingAs($assistant)
            ->get(route('admin.validation-rules.index'))
            ->assertOk()
            ->assertSee('Aprobar')
            ->assertDontSee('Nueva regla de composición')
            ->assertDontSee('Editar')
            ->assertDontSee('Eliminar');

        $this->actingAs($assistant)
            ->patch(route('admin.validation-rules.approve', $rule))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertTrue($rule->fresh()->is_enforced);

        $this->actingAs($assistant)
            ->get(route('admin.superadministrator.validation-rules.create'))
            ->assertForbidden();
        $this->actingAs($assistant)
            ->patch(route('admin.superadministrator.validation-rules.deactivate', $rule))
            ->assertForbidden();
    }

    public function test_user_authorized_to_edit_clinical_agent_can_complete_rule_editing_cycle(): void
    {
        $super = $this->superadministrator();
        $user = User::factory()->create(['hospital_id' => null]);
        $user->assignRole(Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']));
        $agent = AiAgent::forceCreate([
            'name' => ClinicalEvidence::NAME,
            'integration_key' => ClinicalEvidence::KEY,
            'is_active' => true,
        ]);
        $agent->accessGrants()->create([
            'subject_type' => 'user',
            'subject_key' => (string) $user->id,
            'can_view' => true,
            'can_run' => true,
            'can_edit' => true,
            'granted_by' => $super->id,
        ]);
        $rule = $this->rule(['status' => 'review']);

        $this->actingAs($user)
            ->get(route('admin.validation-rules.index'))
            ->assertOk()
            ->assertSee('Edición autorizada:')
            ->assertSee('>Editar<', false)
            ->assertSee('>Aprobar<', false)
            ->assertSee('>Desactivar<', false);

        $this->get(route('admin.validation-rules.edit', $rule))
            ->assertOk()
            ->assertSee('Editar regla')
            ->assertSee(route('admin.validation-rules.update', $rule), false);

        $activeRule = $this->rule(['status' => 'active', 'is_enforced' => true]);
        $this->patch(route('admin.validation-rules.deactivate', $activeRule))
            ->assertRedirect()
            ->assertSessionHas('status');
        $this->assertSame('inactive', $activeRule->fresh()->status);
        $this->assertFalse($activeRule->fresh()->is_enforced);
        $this->get(route('admin.validation-rules.edit', $activeRule))->assertOk();

        $sidebar = view('layouts.includes.admin.aside', [
            'pendingSolicitudesCount' => 0,
            'myPurchaseOrdersCount' => 0,
        ])->render();
        $this->assertStringNotContainsString('aria-label="Motor de validaciones"', $sidebar);

        $this->get(route('admin.agents.show', $agent))
            ->assertOk()
            ->assertSee('Abrir motor de validaciones')
            ->assertSee(route('admin.validation-rules.index'), false);

        $this->patch(route('admin.validation-rules.approve', $rule))
            ->assertRedirect()
            ->assertSessionHas('status');
        $this->assertTrue($rule->fresh()->is_enforced);
        $this->assertSame('active', $rule->fresh()->status);
    }

    public function test_active_rule_can_be_viewed_without_deactivating_it(): void
    {
        $admin = $this->superadministrator();
        $rule = $this->rule([
            'status' => 'active',
            'is_enforced' => true,
            'engine' => 'mathematical',
            'configuration' => [
                'source_variable' => 'groups.magnesium.total_meq',
                'formula_template' => 'direct_value',
                'operator' => '<=',
                'threshold_value' => 30,
                'unit' => 'mEq/día',
                'failure_message' => 'Exceso de Magnesio: requiere valoración médica.',
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.validation-rules.show', $rule))
            ->assertOk()
            ->assertSee('Consulta de regla')
            ->assertSee('Magnesio total')
            ->assertSee('Expresión legible de la regla')
            ->assertSee('Magnesio total ≤ 30 mEq/día')
            ->assertSee('menor o igual que')
            ->assertSee('30')
            ->assertSee('mEq/día');

        $rule->refresh();
        $this->assertTrue($rule->is_enforced);
        $this->assertSame('active', $rule->status);

        $editable = $this->rule([
            'engine' => 'mathematical',
            'configuration' => $rule->configuration,
        ]);
        $this->actingAs($admin)
            ->get(route('admin.superadministrator.validation-rules.edit', $editable))
            ->assertOk()
            ->assertSee('Vista previa de la regla')
            ->assertSee('x-model="source"', false)
            ->assertSee('x-model="operator"', false);
    }

    public function test_rules_can_be_searched_by_name_or_code_without_losing_engine_filter(): void
    {
        $admin = $this->superadministrator();
        $matching = $this->rule([
            'code' => 'NP.R6.ADULT.CLIN.MAGNESIUM',
            'name' => 'Límite clínico de Magnesio en adulto',
            'engine' => 'mathematical',
        ]);
        $this->rule([
            'code' => 'NP.R6.ADULT.CLIN.CALCIUM',
            'name' => 'Límite clínico de Calcio en adulto',
            'engine' => 'mathematical',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.superadministrator.validation-rules.index', [
                'engine' => 'mathematical',
                'search' => 'MAGNESIUM',
            ]))
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee('Límite clínico de Calcio en adulto')
            ->assertSee('value="MAGNESIUM"', false)
            ->assertSee('engine=mathematical&amp;search=MAGNESIUM', false);
    }

    public function test_approving_replacement_rule_deactivates_superseded_catalog(): void
    {
        $admin = $this->superadministrator();
        $current = $this->rule([
            'code' => 'NP.R6.ADULT.COMP.CATALOG',
            'status' => 'active',
            'is_enforced' => true,
        ]);
        $replacement = $this->rule([
            'code' => 'NP.R6.ADULT.COMP.CATALOG.V2',
            'status' => 'review',
            'configuration' => [
                'mode' => 'allowed_catalog',
                'supersedes_codes' => [$current->code],
                'allowed_compositions' => [],
            ],
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.superadministrator.validation-rules.approve', $replacement))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('validation_rules', [
            'id' => $current->id,
            'status' => 'inactive',
            'is_enforced' => false,
        ]);
        $this->assertDatabaseHas('validation_rules', [
            'id' => $replacement->id,
            'status' => 'active',
            'is_enforced' => true,
        ]);
    }

    private function superadministrator(): User
    {
        $user = User::factory()->create(['hospital_id' => null]);
        $user->assignRole(Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]));

        return $user;
    }

    private function rule(array $attributes = []): ValidationRule
    {
        return ValidationRule::create(array_merge([
            'code' => 'TEST.APPROVAL.'.uniqid(),
            'name' => 'Regla de prueba de aprobación',
            'engine' => 'composition',
            'population' => 'adult',
            'severity' => 'blocking',
            'status' => 'draft',
            'configuration' => ['mode' => 'single'],
            'version' => 1,
            'is_enforced' => false,
        ], $attributes));
    }
}
