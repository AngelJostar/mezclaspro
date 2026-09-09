<?php

namespace Tests\Feature;

use App\Livewire\Admin\AgentCenter;
use App\Models\AiAgent;
use App\Models\User;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Fixtures\AgentCenter as AgentFixture;
use Tests\TestCase;

class AgentCenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AgentFixture::seed();
    }

    public function test_create_persists_an_agent_and_selects_its_information(): void
    {
        $component = Livewire::test(AgentCenter::class)->assertSet('selection', '')
            ->assertDontSee('No hay agentes registrados.')->call('selectAgent', 'all')->assertSee('No hay agentes registrados.')
            ->call('openCreate')->set('agentName', '  Revision de prueba  ')
            ->set('agentDescription', 'Descripcion')->set('agentInstructions', "Paso uno\nPaso dos")
            ->call('saveAgent')->assertHasNoErrors()->assertSet('showAgentForm', false)
            ->assertSee('Agente creado.')->assertSee('Revision de prueba');
        $agent = AiAgent::firstOrFail();
        $this->assertSame('Revision de prueba', $agent->name);
        $this->assertSame("Paso uno\nPaso dos", $agent->instructions);
        $this->assertSame(auth()->id(), (int) $agent->created_by);
        $component->assertSet('selection', (string) $agent->id);
        Livewire::test(AgentCenter::class)->assertSet('selection', '')->assertSee($agent->name)
            ->assertDontSee($agent->instructions)->call('selectAgent', (string) $agent->id)->assertSee($agent->instructions);
    }

    public function test_selection_and_all_toggle_information_without_removing_carousel_buttons(): void
    {
        $first = AiAgent::create(['name' => 'Primero', 'description' => 'Detalle uno']);
        $second = AiAgent::create(['name' => 'Segundo', 'description' => 'Detalle dos']);
        Livewire::test(AgentCenter::class)->assertSet('selection', '')
            ->assertDontSee('Detalle uno')->assertDontSee('Detalle dos')
            ->call('selectAgent', 'all')->assertSee('Detalle uno')->assertSee('Detalle dos')
            ->call('selectAgent', (string) $first->id)->assertSee('Detalle uno')->assertDontSee('Detalle dos')
            ->assertSee('Segundo')->call('selectAgent', (string) $first->id)->assertSet('selection', '')
            ->assertDontSee('Detalle uno')->assertSee('Primero')
            ->call('selectAgent', (string) $second->id)->assertSee('Detalle dos')
            ->call('selectAgent', 'all')->assertSee('Detalle uno')->assertSee('Detalle dos')
            ->call('selectAgent', 'all')->assertSet('selection', '')->assertDontSee('Detalle dos');
        $this->assertSame(2, AiAgent::count());
    }

    public function test_create_cancel_and_edit_keep_existing_agents_intact_until_save(): void
    {
        $agent = AiAgent::create(['name' => 'Original', 'description' => 'Descripcion original']);
        $component = Livewire::test(AgentCenter::class)->call('openCreate')->set('agentName', 'Sin guardar')
            ->call('closeAgentForm')->assertSet('showAgentForm', false)
            ->call('openCreate')->assertSet('agentName', '')->call('closeAgentForm')
            ->call('editAgent', $agent->id)->assertSet('agentName', 'Original')
            ->set('agentName', 'Cambio cancelado')->call('closeAgentForm');
        $this->assertSame(1, AiAgent::count());
        $this->assertSame('Original', $agent->fresh()->name);
        $component->call('editAgent', $agent->id)->set('agentName', 'Actualizado')
            ->call('saveAgent')->assertHasNoErrors()->assertSee('Agente actualizado.');
        $this->assertSame('Actualizado', $agent->fresh()->name);
        $this->assertSame(1, AiAgent::count());
    }

    public function test_validation_rejects_blank_duplicate_and_oversized_values(): void
    {
        AiAgent::create(['name' => 'Existente']);
        $component = Livewire::test(AgentCenter::class)->call('openCreate')->set('agentName', '   ')
            ->call('saveAgent')->assertHasErrors(['agentName' => 'required'])->assertSet('showAgentForm', true)
            ->set('agentName', 'Existente')->call('saveAgent')->assertHasErrors(['agentName' => 'unique'])
            ->set('agentName', str_repeat('a', 121))->set('agentDescription', str_repeat('a', 1001))
            ->set('agentInstructions', str_repeat('a', 20001))->call('saveAgent')
            ->assertHasErrors(['agentName' => 'max', 'agentDescription' => 'max', 'agentInstructions' => 'max']);
        $this->assertSame(1, AiAgent::count());
    }

    public function test_agent_content_is_escaped_instead_of_executed(): void
    {
        AiAgent::create(['name' => '<script>alert(1)</script>', 'instructions' => '<img src=x onerror=alert(1)>']);
        Livewire::test(AgentCenter::class)->call('selectAgent', 'all')->assertSee('<script>alert(1)</script>')
            ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_only_super_administrators_can_access_or_write_the_center(): void
    {
        $other = User::forceCreate(['name' => 'Sin permiso']);
        $component = Livewire::test(AgentCenter::class)->call('openCreate')->set('agentName', 'No autorizado');
        $this->actingAs($other);
        $component->call('saveAgent')->assertForbidden();
        Livewire::test(AgentCenter::class)->assertForbidden();
        $this->assertSame(0, AiAgent::count());
    }

    public function test_editing_id_cannot_be_replaced_by_client_input(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(AgentCenter::class)->call('openCreate')->set('editingAgentId', 99);
    }

    public function test_invalid_selection_does_not_expose_another_record(): void
    {
        Livewire::test(AgentCenter::class)->call('selectAgent', 'unknown')->assertNotFound();
    }
}
