<?php

namespace Tests\Feature;

use App\Models\MixtureMessage;
use App\Models\User;
use App\Services\MixtureMessagingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\UnifiedRequestExportData;
use Tests\TestCase;

class DemoMixtureMessagesTest extends TestCase
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
        $this->central = User::forceCreate(['id' => 3, 'name' => 'Prodifem prueba', 'is_active' => true]);
        $this->central->assignRole(Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']));
        $this->central->givePermissionTo('oncologicos_solicitudes_index');
    }

    private function generate(array $ids = [1, 2, 4])
    {
        return $this->artisan('demo:mixture-messages', [
            'mixtures' => $ids, '--hospital-user' => $this->hospital->id, '--central-user' => $this->central->id,
        ]);
    }

    public function test_demo_conversations_are_identified_unread_and_do_not_modify_mixtures(): void
    {
        $before = DB::table('mezclas')->orderBy('id')->get()->toJson();
        $this->generate()->expectsOutput('8 mensajes DEMO creados en 3 mezclas. Las conversaciones existentes no se modificaron.')->assertSuccessful();
        $this->assertDatabaseCount('mixture_messages', 8);
        $this->assertDatabaseCount('mixture_message_reads', 0);
        $this->assertSame($before, DB::table('mezclas')->orderBy('id')->get()->toJson());
        foreach (MixtureMessage::all() as $message) {
            $this->assertStringStartsWith('[DEMO] ', $message->body);
            $this->assertStringEndsWith('(DEMO)', $message->author_name);
            $this->assertSame(1, (int) $message->hospital_id);
        }
        $messaging = app(MixtureMessagingService::class);
        $keys = ['oncologicos:1', 'antibioticos:2', 'oncologicos:4'];
        $centralSummary = $messaging->summaries($this->central, $messaging->targets($this->central, $keys));
        $hospitalSummary = $messaging->summaries($this->hospital, $messaging->targets($this->hospital, $keys));
        $this->assertSame([1, 2, 2], array_column(array_map(fn ($key) => $centralSummary[$key], $keys), 'unread'));
        $this->assertSame([0, 1, 2], array_column(array_map(fn ($key) => $hospitalSummary[$key], $keys), 'unread'));
        $this->generate()->assertSuccessful();
        $this->assertDatabaseCount('mixture_messages', 8);
    }

    public function test_existing_real_messages_are_not_mixed_with_demo_content(): void
    {
        $messaging = app(MixtureMessagingService::class);
        $message = $messaging->send($this->hospital, $messaging->resolve($this->hospital, 'oncologicos', 1),
            'Mensaje existente', (string) \Illuminate\Support\Str::uuid());
        $this->generate([1])->assertSuccessful();
        $this->assertDatabaseCount('mixture_messages', 1);
        $this->assertSame('Mensaje existente', $message->fresh()->body);
    }

    public function test_foreign_mixture_or_inactive_account_prevents_all_inserts(): void
    {
        $this->generate([1, 3])->assertFailed();
        $this->assertDatabaseCount('mixture_messages', 0);
        $this->hospital->update(['is_active' => false]);
        $this->generate()->assertFailed();
        $this->assertDatabaseCount('mixture_messages', 0);
    }

    public function test_production_is_not_seeded(): void
    {
        app()->instance('env', 'production');
        $this->generate()->assertFailed();
        $this->assertDatabaseCount('mixture_messages', 0);
    }
}
