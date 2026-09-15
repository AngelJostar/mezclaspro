<?php

namespace Tests\Feature;

use App\Models\MixtureMessage;
use App\Models\User;
use App\Http\Controllers\Admin\UnifiedSolicitudController;
use App\Livewire\Nutricionales\SolicitudesTable as NutritionTable;
use App\Livewire\Oncologicos\SolicitudesTable as OncologyTable;
use App\Exports\UnifiedSolicitudesExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\UnifiedRequestExportData;
use Tests\TestCase;

class MixtureMessagesTest extends TestCase
{
    private User $hospital;
    private User $central;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hospital = UnifiedRequestExportData::seed();
        foreach (['users' => 'is_active', 'hospitals' => 'access_is_active', 'clientes' => 'is_active'] as $table => $column) {
            Schema::table($table, fn (Blueprint $schema) => $schema->boolean($column)->default(true));
        }
        $this->hospital->refresh();
        (require database_path('migrations/2026_09_14_000007_create_mixture_messages.php'))->up();
        $this->central = User::forceCreate(['id' => 3, 'name' => 'Central Ana', 'is_active' => true]);
        $this->central->assignRole(Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']));
        $this->central->givePermissionTo(['nutricionales_solicitudes_index', 'oncologicos_solicitudes_index']);
    }

    private function url(string $kind = 'oncologicos', int $id = 1): string
    {
        return route('admin.solicitudes.mensajes.show', ['kind' => $kind, 'target' => $id]);
    }

    private function send(User $user, string $kind = 'oncologicos', int $id = 1, string $body = 'Confirmar la entrega', ?string $token = null)
    {
        return $this->actingAs($user)->postJson($this->url($kind, $id), [
            'body' => $body, 'client_token' => $token ?? (string) Str::uuid(),
            'author_id' => 999, 'author_name' => 'Falso', 'sender_side' => 'central', 'hospital_id' => 999,
        ]);
    }

    private function summary(User $user, array $keys = ['oncologicos:1'])
    {
        return $this->actingAs($user)->getJson(route('admin.solicitudes.mensajes.summary', ['targets' => $keys]));
    }

    public function test_hospital_starts_and_central_replies_with_immutable_server_authorship(): void
    {
        foreach (['oncologicos' => 1, 'antibioticos' => 2, 'nutricionales' => 11] as $kind => $id) {
            $key = $kind.':'.$id;
            $this->summary($this->central, [$key])->assertOk()->assertJsonPath('summaries.'.$key.'.total', 0);
            $this->send($this->central, $kind, $id)->assertForbidden();
            $hospitalMessage = $this->send($this->hospital, $kind, $id)->assertCreated()
                ->assertJsonPath('message.side', 'hospital')->assertJsonPath('message.author', $this->hospital->name);
            $this->summary($this->central, [$key])->assertJsonPath('summaries.'.$key.'.unread', 1);
            $this->send($this->central, $kind, $id, 'Estamos verificando la entrega.')->assertCreated();
            $this->summary($this->hospital, [$key])->assertJsonPath('summaries.'.$key.'.unread', 1);
            $this->actingAs($this->central)->getJson($this->url($kind, $id))->assertOk()
                ->assertHeader('Cache-Control', 'no-store, private')->assertJsonCount(2, 'messages')
                ->assertJsonPath('messages.0.id', $hospitalMessage->json('message.id'))->assertJsonPath('can_send', true);
        }
        $this->assertSame('preparada', DB::table('mezclas')->where('id', 1)->value('estado'));
        $this->assertDatabaseCount('mixture_messages', 6);
        $this->assertDatabaseMissing('mixture_messages', ['author_id' => 999]);
    }

    public function test_messages_are_isolated_by_mixture_kind_hospital_and_permissions(): void
    {
        $this->send($this->hospital)->assertCreated();
        $this->actingAs($this->hospital)->getJson($this->url('oncologicos', 4))->assertOk()->assertJsonCount(0, 'messages');
        $this->actingAs($this->hospital)->getJson($this->url('antibioticos', 1))->assertNotFound();
        $this->send($this->hospital, 'oncologicos', 3)->assertNotFound();
        $this->send($this->hospital, 'nutricionales', 12)->assertNotFound();
        $this->summary($this->hospital, ['oncologicos:1', 'oncologicos:3', 'nutricionales:12'])->assertOk()
            ->assertJsonMissingPath('summaries.oncologicos:3')->assertJsonMissingPath('summaries.nutricionales:12');
        $this->central->revokePermissionTo('oncologicos_solicitudes_index');
        $this->actingAs($this->central)->getJson($this->url())->assertNotFound();
        $this->send($this->central)->assertNotFound();
        $this->hospital->forceFill(['is_active' => false]);
        $this->actingAs($this->hospital)->getJson($this->url())->assertForbidden();
    }

    public function test_retry_is_idempotent_and_blank_oversize_or_invalid_requests_fail(): void
    {
        $token = (string) Str::uuid();
        $first = $this->send($this->hospital, token: $token)->assertCreated()->json('message.id');
        $this->send($this->hospital, token: $token)->assertOk()->assertJsonPath('message.id', $first);
        $this->send($this->hospital, body: 'Otro contenido', token: $token)->assertStatus(409);
        $this->send($this->hospital, body: '   ')->assertUnprocessable();
        $this->send($this->hospital, body: str_repeat('a', 4001))->assertUnprocessable();
        $this->send($this->hospital, token: 'invalid')->assertUnprocessable();
        $this->assertDatabaseCount('mixture_messages', 1);
        $this->actingAs($this->hospital)->getJson(route('admin.solicitudes.mensajes.summary', ['targets' => ['bad:1']]))->assertUnprocessable();
    }

    public function test_read_receipts_are_per_user_monotonic_and_do_not_hide_history(): void
    {
        $first = $this->send($this->hospital)->json('message.id');
        $second = $this->send($this->hospital, body: 'Segundo mensaje')->json('message.id');
        $url = $this->url().'/leidos';
        $this->actingAs($this->central)->postJson($url, ['through_id' => $second])->assertOk();
        $this->actingAs($this->central)->postJson($url, ['through_id' => $first])->assertOk();
        $this->summary($this->central)->assertJsonPath('summaries.oncologicos:1.unread', 0)->assertJsonPath('summaries.oncologicos:1.total', 2);
        $this->send($this->hospital, body: 'Tercer mensaje')->assertCreated();
        $this->summary($this->central)->assertJsonPath('summaries.oncologicos:1.unread', 1);
        $foreign = $this->send($this->hospital, 'antibioticos', 2)->json('message.id');
        $this->actingAs($this->central)->postJson($url, ['through_id' => $foreign])->assertUnprocessable();
        $otherCentral = User::forceCreate(['name' => 'Otra central', 'is_active' => true]);
        $otherCentral->assignRole('Admin')->givePermissionTo('oncologicos_solicitudes_index');
        $this->summary($otherCentral)->assertJsonPath('summaries.oncologicos:1.unread', 3);
        $this->actingAs($this->central)->getJson($this->url())->assertJsonCount(3, 'messages');
    }

    public function test_history_paginates_in_order_and_returns_only_new_messages(): void
    {
        for ($i = 1; $i <= 56; $i++) {
            MixtureMessage::create(['kind' => 'oncologicos', 'target_id' => 1, 'hospital_id' => 1,
                'author_id' => 1, 'author_name' => 'Hospital', 'sender_side' => 'hospital', 'body' => 'Mensaje '.$i,
                'client_token' => (string) Str::uuid()]);
        }
        $this->actingAs($this->central)->getJson($this->url())->assertJsonCount(50, 'messages')
            ->assertJsonPath('messages.0.body', 'Mensaje 7')->assertJsonPath('has_older', true);
        $this->getJson($this->url().'?before_id=7')->assertJsonCount(6, 'messages')->assertJsonPath('has_older', false);
        $this->getJson($this->url().'?after_id=55')->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.body', 'Mensaje 56');
        $this->getJson($this->url().'?after_id=56')->assertJsonCount(0, 'messages');
    }

    public function test_no_edit_or_delete_routes_and_database_rejects_history_changes(): void
    {
        $id = $this->send($this->hospital)->json('message.id');
        $this->putJson($this->url(), ['body' => 'Cambio'])->assertStatus(405);
        $this->deleteJson($this->url())->assertStatus(405);
        foreach (['update', 'delete'] as $action) {
            try {
                $query = DB::table('mixture_messages')->where('id', $id);
                $action === 'update' ? $query->update(['body' => 'Cambio']) : $query->delete();
                $this->fail('The database must reject message changes.');
            } catch (\Illuminate\Database\QueryException $exception) {
                $this->assertStringContainsString('Messages are immutable', $exception->getMessage());
            }
        }
        $this->assertDatabaseHas('mixture_messages', ['id' => $id, 'body' => 'Confirmar la entrega']);
        $this->expectException(\LogicException::class);
        MixtureMessage::findOrFail($id)->delete();
    }

    public function test_guest_cannot_access_conversations(): void
    {
        auth()->logout();
        auth()->forgetGuards();
        $this->getJson($this->url())->assertUnauthorized();
        $this->postJson($this->url(), ['body' => 'Hola', 'client_token' => (string) Str::uuid()])->assertUnauthorized();
    }

    public function test_messaging_filter_keeps_conversations_in_all_categories_without_duplicate_rows(): void
    {
        foreach (['todas', 'nutricionales', 'oncologicos', 'antibioticos'] as $category) {
            $this->assertSame([], $this->filteredIds($category, $this->central));
        }
        $this->send($this->hospital)->assertCreated();
        $this->send($this->hospital, body: 'Segundo mensaje')->assertCreated();
        $this->send($this->hospital, 'oncologicos', 4)->assertCreated();
        $this->send($this->hospital, 'nutricionales', 11)->assertCreated();
        $this->send($this->hospital, 'antibioticos', 2)->assertCreated();
        foreach ([$this->hospital, $this->central] as $user) {
            $this->assertEqualsCanonicalizing([1, 4, 11, 2], $this->filteredIds('todas', $user));
            $this->assertSame([11], $this->filteredIds('nutricionales', $user));
            $this->assertEqualsCanonicalizing([1, 4], $this->filteredIds('oncologicos', $user));
            $this->assertSame([2], $this->filteredIds('antibioticos', $user));
        }
        $this->actingAs($this->central)->postJson($this->url().'/leidos', ['through_id' => 2])->assertOk();
        $this->assertEqualsCanonicalizing([1, 4], $this->filteredIds('oncologicos', $this->central));
        Excel::fake();
        app(UnifiedSolicitudController::class)->exportarExcel($this->filterRequest($this->central));
        Excel::assertDownloaded('solicitudes_todas.xlsx', fn (UnifiedSolicitudesExport $export) => $export->collection()->count() === 4);
    }

    public function test_messaging_filter_respects_hospital_scope_current_ownership_and_category_permissions(): void
    {
        $other = User::findOrFail(2);
        $other->assignRole('Institucion')->givePermissionTo('oncologicos_solicitudes_index');
        $this->send($other, 'oncologicos', 3)->assertCreated();
        $this->assertSame([], $this->filteredIds('todas', $this->hospital));
        $this->assertSame([], $this->filteredIds('oncologicos', $this->hospital));
        $this->assertSame([3], $this->filteredIds('todas', $this->central));
        DB::table('solicitud_oncos')->where('id', 3)->update(['hospital_id' => 1]);
        $this->assertSame([], $this->filteredIds('todas', $this->central));
        $this->assertSame([], $this->filteredIds('oncologicos', $this->central));
        $this->send($this->hospital)->assertCreated();
        $this->central->revokePermissionTo('oncologicos_solicitudes_index');
        $this->assertSame([], $this->filteredIds('todas', $this->central));
    }

    private function filterRequest(User $user): Request
    {
        $request = Request::create(route('admin.solicitudes.index'), 'GET', ['estado' => 'mensajeria']);
        $request->setUserResolver(fn () => $user);
        return $request;
    }

    private function filteredIds(string $category, User $user): array
    {
        $this->actingAs($user);
        if ($category === 'todas') {
            return app(UnifiedSolicitudController::class)->index($this->filterRequest($user))->getData()['requests']->pluck('id')->all();
        }
        if ($category === 'nutricionales') {
            $component = new NutritionTable;
            $component->mount('mensajeria');
            return $component->render()->getData()['solicitudes']->pluck('id')->all();
        }
        $component = new OncologyTable;
        $component->mount($category, 'mensajeria');
        return $component->render()->getData()['mezclas']->pluck('id')->all();
    }
}
