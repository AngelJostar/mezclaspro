<?php

namespace Tests\Feature;

use App\Livewire\Admin\ClinicalAgentChat;
use App\Models\AiAgent;
use App\Models\ClinicalAgentConversation;
use App\Models\ClinicalReview;
use App\Models\ClinicalSource;
use App\Models\User;
use App\Services\Clinical\ClinicalEvidence;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Fixtures\AgentCenter as AgentFixture;
use Tests\TestCase;

class ClinicalAgentChatTest extends TestCase
{
    private AiAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        AgentFixture::seed();
        (require database_path('migrations/2026_09_29_000001_create_clinical_support.php'))->up();
        (require database_path('migrations/2026_09_30_000007_version_clinical_manual_sources.php'))->up();
        (require database_path('migrations/2026_09_29_000003_create_clinical_agent_conversations.php'))->up();
        $this->agent = AiAgent::forceCreate(['name' => ClinicalEvidence::NAME, 'integration_key' => ClinicalEvidence::KEY,
            'instructions' => ClinicalEvidence::INSTRUCTIONS, 'is_active' => true]);
        ClinicalSource::create(['title' => 'Manual sintetico', 'reference' => 'Referencia de prueba', 'category' => 'nutricionales',
            'content' => 'Solo datos sinteticos para pruebas de software.', 'sha256' => str_repeat('a', 64), 'is_manual' => true]);
        config(['services.openai.api_key' => 'sk-test-secret-not-real', 'services.openai.model' => 'gpt-4.1-mini']);
        Http::preventStrayRequests();
    }

    private function chat()
    {
        return Livewire::test(ClinicalAgentChat::class, ['agentId' => $this->agent->id]);
    }

    private function fake(string $answer = 'Respuesta de prueba [S1].', array $citations = [['source_id' => 'S1', 'section' => 'Seccion de prueba']]): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::response(['status' => 'completed', 'output' => [
            ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode(compact('answer', 'citations'))]]],
        ]])]);
    }

    public function test_chat_uses_sources_and_stores_encrypted_history_without_changing_requests(): void
    {
        $this->fake();
        $chat = $this->chat()->set('draft', 'Consulta sintetica inicial')->call('send')->assertHasNoErrors()
            ->assertSee('Respuesta de prueba')->assertSee('Pendiente de revision; no es evidencia aprobada')->assertSet('draft', '');
        $conversation = ClinicalAgentConversation::first();
        $this->assertSame(1, count($conversation->messages));
        $raw = DB::table('clinical_agent_conversations')->value('messages');
        $this->assertStringNotContainsString('Consulta sintetica', $raw);
        $this->assertStringNotContainsString('Respuesta de prueba', $raw);
        $this->assertArrayNotHasKey('messages', $conversation->toArray());
        $this->assertSame(0, ClinicalReview::count());
        $this->assertNull(ClinicalSource::first()->approved_at);
        Http::assertSent(function ($request) {
            $input = json_decode($request['input'], true);
            $this->assertFalse($request['store']);
            $this->assertFalse($input['sources'][0]['reviewed']);
            $this->assertFalse(isset($request['tools']));
            $this->assertStringNotContainsString('Administrador de prueba', $request['input']);
            $this->assertSame([], $input['history']);
            $this->assertSame('clinical_agent_chat', $request['text']['format']['name']);
            return true;
        });
        $chat->set('draft', 'Consulta de seguimiento')->call('send')->assertHasNoErrors();
        $this->assertCount(2, $conversation->fresh()->messages);
        Http::assertSent(fn ($request) => count(json_decode($request['input'], true)['history']) === 1);
        $this->chat()->assertSee('Consulta sintetica inicial')->assertSee('Consulta de seguimiento')->assertSet('draft', '');
    }

    public function test_new_conversation_and_history_selection_are_private_to_the_user(): void
    {
        $this->fake();
        $chat = $this->chat()->set('draft', 'Mensaje privado')->call('send');
        $first = ClinicalAgentConversation::first()->id;
        $chat->call('newConversation')->assertDontSee('Mensaje privado')->assertSet('revision', 0);
        $this->assertSame(2, ClinicalAgentConversation::count());
        $chat->call('newConversation');
        $this->assertSame(2, ClinicalAgentConversation::count());
        $chat->call('selectConversation', $first)->assertSee('Mensaje privado');
        $user = User::forceCreate(['name' => 'Otro administrador']);
        $user->assignRole('Super Admin');
        $this->actingAs($user);
        $other = $this->chat()->assertDontSee('Mensaje privado')->assertSet('conversationId', null);
        $other->call('selectConversation', $first)->assertNotFound();
    }

    public function test_chat_rechecks_roles_and_cannot_use_an_operational_agent(): void
    {
        $chat = $this->chat()->set('draft', 'Hola');
        $otherAgent = AiAgent::create(['name' => 'Otro agente']);
        Livewire::test(ClinicalAgentChat::class, ['agentId' => $otherAgent->id])->assertNotFound();
        $this->actingAs(User::forceCreate(['name' => 'Sin permisos']));
        $chat->call('send')->assertForbidden();
        $this->chat()->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_chat_rejects_locked_history_tampering(): void
    {
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        $this->chat()->set('conversationId', 999);
    }

    public function test_empty_long_inactive_and_unconfigured_messages_do_not_call_openai(): void
    {
        $chat = $this->chat()->set('draft', '   ')->call('send')->assertHasErrors('draft');
        $chat->set('draft', str_repeat('a', 4001))->call('send')->assertHasErrors('draft');
        $this->agent->update(['is_active' => false]);
        $chat->set('draft', 'Hola')->call('send')->assertHasErrors('chat')->assertSee('Activa el agente');
        $this->agent->update(['is_active' => true]);
        config(['services.openai.api_key' => null]);
        $chat->call('send')->assertHasErrors('chat')->assertSet('draft', 'Hola')->assertSee('Falta configurar');
        $this->assertSame(0, ClinicalAgentConversation::count());
        Http::assertNothingSent();
    }

    public function test_errors_preserve_draft_and_hide_provider_secrets(): void
    {
        Http::fake(['*' => Http::response(['error' => 'sk-test-secret-not-real PRIVATE_BODY'], 429)]);
        $this->chat()->set('draft', 'Mensaje para reintentar')->call('send')->assertHasErrors('chat')
            ->assertSet('draft', 'Mensaje para reintentar')->assertSee('limite de uso o cuota')
            ->assertDontSee('sk-test-secret-not-real')->assertDontSee('PRIVATE_BODY');
        $this->assertSame(0, ClinicalAgentConversation::count());
    }

    public function test_unknown_citations_and_incomplete_responses_are_not_saved(): void
    {
        $this->fake('Respuesta no verificable', [['source_id' => 'S999', 'section' => 'Inventada']]);
        $chat = $this->chat()->set('draft', 'Consulta')->call('send')->assertHasErrors('chat')->assertDontSee('Respuesta no verificable');
        $this->assertSame(0, ClinicalAgentConversation::count());
        Http::fake(['*' => Http::response(['status' => 'incomplete', 'output' => []])]);
        $chat->call('send')->assertHasErrors('chat')->assertSet('draft', 'Consulta');
        $this->assertSame(0, ClinicalAgentConversation::count());
    }

    public function test_answers_and_questions_are_escaped_and_not_rendered_as_html(): void
    {
        $this->fake('<script>alert("x")</script>', []);
        $this->chat()->set('draft', '<img src=x onerror=alert(1)>')->call('send')->assertHasNoErrors()
            ->assertDontSeeHtml('<script>alert("x")</script>')->assertDontSeeHtml('<img src=x onerror=alert(1)>');
    }

    public function test_lock_and_stale_revision_prevent_duplicate_calls(): void
    {
        $this->fake();
        $chat = $this->chat()->set('draft', 'Primera consulta');
        $other = $this->chat()->set('draft', 'Otra consulta');
        $lock = Cache::lock('clinical-chat-send:'.auth()->id().':'.$this->agent->id, 90);
        $lock->get();
        try { $chat->call('send')->assertHasErrors('chat'); Http::assertNothingSent(); }
        finally { $lock->release(); }
        $chat->call('send')->assertHasNoErrors();
        $other->call('send')->assertHasErrors('chat')->assertSee('otra ventana')->assertSet('draft', 'Otra consulta');
        Http::assertSentCount(1);
        $this->assertCount(1, ClinicalAgentConversation::first()->messages);
    }
}
