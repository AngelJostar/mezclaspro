<?php

namespace Tests\Feature;

use App\Livewire\Admin\AgentCenter;
use App\Models\AiAgent;
use Database\Seeders\PromesaAiAgentsSeeder;
use Livewire\Livewire;
use Tests\Fixtures\AgentCenter as AgentFixture;
use Tests\TestCase;

class PromesaAiAgentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AgentFixture::seed();
    }

    public function test_all_twelve_profiles_are_created_with_complete_instructions(): void
    {
        (new PromesaAiAgentsSeeder())->run();
        $agents = AiAgent::all();
        $this->assertCount(12, $agents);
        foreach ($agents as $agent) {
            $this->assertNotEmpty($agent->description);
            $this->assertLessThanOrEqual(120, mb_strlen($agent->name));
            $this->assertLessThanOrEqual(1000, mb_strlen($agent->description));
            $this->assertLessThanOrEqual(20000, mb_strlen($agent->instructions));
            foreach (['Fuentes y alcance:', 'Cuándo revisar', 'Reglas de alerta:', 'Cálculo del impacto:',
                'Responsable de seguimiento sugerido:', 'Acciones permitidas', 'Formato de cada alerta:',
                'Evidencia:', 'Prioridad y motivo:', 'Seguimiento:', 'sin motor de revisión',
                'No ejecutar ajustes de inventario', 'No convertir los ejemplos', 'No inventar costos',
            ] as $requirement) {
                $this->assertStringContainsString($requirement, $agent->instructions, $agent->name);
            }
        }
        foreach (['Auditoría de consumos', 'Inventarios y abasto', 'Remisiones y facturación'] as $name) {
            $this->assertStringContainsString('Primera etapa:', $agents->firstWhere('name', $name)->instructions);
        }
    }

    public function test_repeating_the_seed_preserves_existing_profiles_and_user_edits(): void
    {
        $existing = AiAgent::create(['name' => 'Auditoría de consumos', 'description' => 'Configuración propia', 'instructions' => 'No sobrescribir']);
        $custom = AiAgent::create(['name' => 'Agente personalizado']);
        (new PromesaAiAgentsSeeder())->run();
        $snapshot = AiAgent::orderBy('id')->get()->toArray();
        (new PromesaAiAgentsSeeder())->run();
        $this->assertSame($snapshot, AiAgent::orderBy('id')->get()->toArray());
        $this->assertSame(13, AiAgent::count());
        $this->assertSame('Configuración propia', $existing->fresh()->description);
        $this->assertSame('No sobrescribir', $existing->fresh()->instructions);
        $this->assertNotNull($custom->fresh());
    }

    public function test_profiles_are_visible_selectable_and_editable_in_the_agent_center(): void
    {
        (new PromesaAiAgentsSeeder())->run();
        $component = Livewire::test(AgentCenter::class)->assertSet('selection', '')->assertDontSee('No hay agentes registrados.');
        $this->assertSame(0, substr_count($component->html(), 'data-agent-information='));
        foreach (AiAgent::all() as $agent) {
            $component->assertSee($agent->name)->call('selectAgent', (string) $agent->id)
                ->assertSet('selection', (string) $agent->id)->assertSee($agent->description)
                ->call('editAgent', $agent->id)->assertSet('agentInstructions', $agent->instructions)
                ->call('closeAgentForm');
        }
        $this->assertSame(12, substr_count($component->html(), 'data-agent-select='));
        $component->call('selectAgent', 'all');
        $this->assertSame(12, substr_count($component->html(), 'data-agent-information='));
    }
}
