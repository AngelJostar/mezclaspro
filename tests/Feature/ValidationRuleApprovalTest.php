<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ValidationRule;
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
            ->assertSee('Aprobar y activar')
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
