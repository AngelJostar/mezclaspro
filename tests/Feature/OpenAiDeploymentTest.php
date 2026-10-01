<?php

namespace Tests\Feature;

use App\Console\Commands\ConfigureOpenAiDeployment;
use App\Models\AiAgent;
use App\Models\AiAgentProviderSetting;
use App\Models\ClinicalSource;
use App\Services\Clinical\ClinicalEvidence;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\AgentCenter;
use Tests\TestCase;

class OpenAiDeploymentTest extends TestCase
{
    private const KEY = 'sk-test-deployment-not-a-real-key';

    protected function setUp(): void
    {
        parent::setUp();
        AgentCenter::seed();
        config(['services.openai.api_key' => null, 'services.openai.model' => 'gpt-4.1-mini', 'services.openai.prefer_environment' => false]);
        Http::preventStrayRequests();
    }

    private function stdin(string $key): void
    {
        $command = new class($key) extends ConfigureOpenAiDeployment {
            public function __construct(private string $testKey) { parent::__construct(); }
            protected function readKey(): string { return trim($this->testKey); }
        };
        app(Kernel::class)->registerCommand($command);
    }

    public function test_deployment_verifies_and_encrypts_the_key_without_exposing_it(): void
    {
        $this->stdin(self::KEY);
        Http::fake(['api.openai.com/v1/models/gpt-4.1-mini' => Http::response(['id' => 'gpt-4.1-mini'])]);
        $this->assertSame(0, Artisan::call('agents:configure-openai', ['--from-stdin' => true, '--verify' => true]));
        $this->assertSame(self::KEY, AiAgentProviderSetting::find(1)->api_key);
        $this->assertStringNotContainsString(self::KEY, DB::table('ai_agent_provider_settings')->value('api_key'));
        $this->assertStringNotContainsString(self::KEY, Artisan::output());
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'https://api.openai.com/v1/models/gpt-4.1-mini'
            && $request->hasHeader('Authorization', 'Bearer '.self::KEY));
    }

    public function test_repeated_deploys_do_not_rotate_ciphertext_or_update_timestamps(): void
    {
        $this->stdin(self::KEY);
        Artisan::call('agents:configure-openai', ['--from-stdin' => true]);
        $before = (array) DB::table('ai_agent_provider_settings')->first();
        $this->travel(2)->minutes();
        $this->assertSame(0, Artisan::call('agents:configure-openai', ['--from-stdin' => true]));
        $this->assertSame($before, (array) DB::table('ai_agent_provider_settings')->first());
        Http::assertNothingSent();
    }

    public function test_empty_secret_preserves_the_existing_server_key_and_model(): void
    {
        AiAgentProviderSetting::create(['api_key' => self::KEY, 'model' => 'existing-model']);
        $this->stdin('');
        $this->assertSame(0, Artisan::call('agents:configure-openai', ['--from-stdin' => true]));
        $settings = AiAgentProviderSetting::find(1);
        $this->assertSame(self::KEY, $settings->api_key);
        $this->assertSame('existing-model', $settings->model);
        Http::assertNothingSent();
    }

    public function test_environment_key_works_without_copying_plaintext_to_the_database(): void
    {
        config(['services.openai.api_key' => self::KEY]);
        $this->assertSame(0, Artisan::call('agents:configure-openai'));
        $this->assertNull(AiAgentProviderSetting::find(1)->api_key);
        $this->assertSame('gpt-4.1-mini', AiAgentProviderSetting::find(1)->model);
    }

    public function test_missing_key_fails_instead_of_reporting_a_ready_integration(): void
    {
        $this->stdin('');
        $this->assertSame(1, Artisan::call('agents:configure-openai', ['--from-stdin' => true, '--verify' => true]));
        $this->assertStringContainsString('OpenAI sin configurar', Artisan::output());
        $this->assertDatabaseCount('ai_agent_provider_settings', 0);
        Http::assertNothingSent();
    }

    public function test_invalid_inputs_do_not_replace_existing_credentials_or_leak_them(): void
    {
        AiAgentProviderSetting::create(['api_key' => self::KEY, 'model' => 'existing-model']);
        foreach ([['invalid-secret', 'gpt-4.1-mini'], [self::KEY, 'invalid model']] as [$key, $model]) {
            $this->stdin($key);
            $this->assertSame(1, Artisan::call('agents:configure-openai', ['--from-stdin' => true, '--model' => $model]));
            $this->assertStringNotContainsString($key, Artisan::output());
            $this->assertSame(self::KEY, AiAgentProviderSetting::find(1)->api_key);
            $this->assertSame('existing-model', AiAgentProviderSetting::find(1)->model);
        }
        Http::assertNothingSent();
    }

    public function test_provider_failure_preserves_previous_credentials_without_logging_the_body(): void
    {
        AiAgentProviderSetting::create(['api_key' => self::KEY, 'model' => 'existing-model']);
        $newKey = 'sk-test-replacement-not-a-real-key';
        $this->stdin($newKey);
        Http::fake(['*' => Http::response(['error' => $newKey.' PRIVATE_PROVIDER_BODY'], 401)]);
        $this->assertSame(1, Artisan::call('agents:configure-openai', ['--from-stdin' => true, '--verify' => true]));
        $this->assertSame(self::KEY, AiAgentProviderSetting::find(1)->api_key);
        $this->assertStringNotContainsString($newKey, Artisan::output());
        $this->assertStringNotContainsString('PRIVATE_PROVIDER_BODY', Artisan::output());
    }

    public function test_successful_key_rotation_uses_the_current_servers_encryption(): void
    {
        AiAgentProviderSetting::create(['api_key' => self::KEY, 'model' => 'existing-model']);
        $newKey = 'sk-test-replacement-not-a-real-key';
        $this->stdin($newKey);
        Http::fake(['*' => Http::response(['id' => 'gpt-4.1-mini'])]);
        $this->assertSame(0, Artisan::call('agents:configure-openai', ['--from-stdin' => true, '--model' => 'gpt-4.1-mini', '--verify' => true]));
        $this->assertSame($newKey, AiAgentProviderSetting::find(1)->api_key);
        $this->assertStringNotContainsString($newKey, DB::table('ai_agent_provider_settings')->value('api_key'));
    }

    public function test_unreadable_encrypted_key_fails_without_generating_a_new_app_key(): void
    {
        DB::table('ai_agent_provider_settings')->insert(['id' => 1, 'api_key' => 'not-readable-ciphertext', 'model' => 'gpt-4.1-mini']);
        $appKey = config('app.key');
        $this->assertSame(1, Artisan::call('agents:configure-openai'));
        $this->assertSame($appKey, config('app.key'));
        $this->assertStringNotContainsString('not-readable-ciphertext', Artisan::output());
        Http::assertNothingSent();
    }

    public function test_bundled_manual_installs_idempotently_without_overwriting_human_decisions(): void
    {
        (require database_path('migrations/2026_09_29_000001_create_clinical_support.php'))->up();
        (require database_path('migrations/2026_09_30_000007_version_clinical_manual_sources.php'))->up();
        $path = resource_path(ClinicalEvidence::MANUAL_FILE);
        $this->assertSame(ClinicalEvidence::MANUAL_FILE_SHA256, hash_file('sha256', $path));
        $this->assertSame(0, Artisan::call('clinical:import-manual', ['path' => $path, '--manual-version' => '4']));
        $agent = AiAgent::where('integration_key', ClinicalEvidence::KEY)->sole();
        $agent->update(['is_active' => false, 'instructions' => 'Instrucciones institucionales personalizadas']);
        $source = ClinicalSource::sole();
        $this->assertFalse($source->isReviewed());
        $source->update(['title' => 'Titulo institucional personalizado']);
        $this->assertSame(0, Artisan::call('clinical:import-manual', ['path' => $path, '--manual-version' => '4']));
        $this->assertDatabaseCount('clinical_sources', 1);
        $this->assertFalse($agent->fresh()->is_active);
        $this->assertSame('Instrucciones institucionales personalizadas', $agent->fresh()->instructions);
        $this->assertSame('Titulo institucional personalizado', $source->fresh()->title);
        $this->assertFalse($source->fresh()->isReviewed());
        Http::assertNothingSent();
    }
}
