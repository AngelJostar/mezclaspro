<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array', 'cache.default' => 'array', 'services.openai.api_key' => null]);
Illuminate\Support\Facades\Http::preventStrayRequests();
Illuminate\Support\Facades\DB::purge('sqlite');
if (($argv[1] ?? '') === 'promesa') {
    Tests\Fixtures\AgentCenter::seed();
    (new Database\Seeders\PromesaAiAgentsSeeder())->run();
} else {
    Tests\Fixtures\AgentCenter::seed((int) ($argv[1] ?? 8));
}
Tests\Fixtures\AgentAuditData::seed();
if (($argv[1] ?? '') === 'clinical-chat') {
    (require database_path('migrations/2026_09_29_000001_create_clinical_support.php'))->up();
    (require database_path('migrations/2026_09_29_000003_create_clinical_agent_conversations.php'))->up();
    $clinicalAgent = App\Models\AiAgent::forceCreate(['name' => App\Services\Clinical\ClinicalEvidence::NAME,
        'integration_key' => App\Services\Clinical\ClinicalEvidence::KEY, 'instructions' => App\Services\Clinical\ClinicalEvidence::INSTRUCTIONS, 'is_active' => true]);
    App\Models\ClinicalSource::create(['title' => 'Manual sintetico', 'reference' => 'Referencia para pruebas', 'category' => 'nutricionales',
        'content' => 'Fuente sintetica; no es evidencia clinica.', 'sha256' => str_repeat('a', 64), 'is_manual' => true]);
    config(['services.openai.api_key' => 'sk-test-browser-only', 'services.openai.model' => 'test-model']);
    Illuminate\Support\Facades\Http::fake(['api.openai.com/v1/responses' => function ($request) {
        $input = json_decode($request['input'], true);
        if ($input['question'] === 'Simular error') return Illuminate\Support\Facades\Http::response(['error' => 'PRIVATE_ERROR'], 429);
        return Illuminate\Support\Facades\Http::response(['status' => 'completed', 'output' => [['type' => 'message', 'content' => [
            ['type' => 'output_text', 'text' => json_encode(['answer' => count($input['history']) ? 'Seguimiento de prueba recibido.' : 'Respuesta de prueba. Se requiere revision profesional. [S1]',
                'citations' => [['source_id' => 'S1', 'section' => 'Seccion de prueba']]])],
        ]]]]);
    }]);
}
Illuminate\Support\Facades\DB::table('medicine_batches')->insert(['lote' => 'LOTE-PRUEBA', 'stock_actual' => 3, 'stock_reservado' => 0, 'caducidad' => now()->addDays(5)->toDateString(), 'is_active' => 1, 'costo_unitario' => 100, 'laboratory_id' => 1, 'warehouse_id' => 1]);

// Keep the synthetic database alive while the browser exercises real Livewire updates.
while (($line = fgets(STDIN)) !== false) {
    try {
        $command = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        if (($command['type'] ?? '') === 'render') {
            $component = Livewire\Livewire::test(App\Livewire\Admin\AgentCenter::class);
            $result = [
                'html' => $component->html(),
                'styles' => Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(),
            ];
        } else {
            $components = [];
            foreach ($command['components'] as $component) {
                [$snapshot, $effects] = Livewire\Livewire::update(
                    json_decode($component['snapshot'], true, 512, JSON_THROW_ON_ERROR),
                    $component['updates'], $component['calls']
                );
                $components[] = ['snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR), 'effects' => $effects];
            }
            $result = ['components' => $components, 'assets' => []];
        }
        echo json_encode($result, JSON_THROW_ON_ERROR).PHP_EOL;
    } catch (Throwable $exception) {
        echo json_encode(['fixture_error' => $exception->getMessage()], JSON_THROW_ON_ERROR).PHP_EOL;
    }
    flush();
}
