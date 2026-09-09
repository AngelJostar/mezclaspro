<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
if (($argv[1] ?? '') === 'promesa') {
    Tests\Fixtures\AgentCenter::seed();
    (new Database\Seeders\PromesaAiAgentsSeeder())->run();
} else {
    Tests\Fixtures\AgentCenter::seed((int) ($argv[1] ?? 8));
}

// Keep the synthetic database alive while the browser exercises real Livewire updates.
while (($line = fgets(STDIN)) !== false) {
    try {
        $command = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        if (($command['type'] ?? '') === 'render') {
            $result = [
                'html' => Livewire\Livewire::test(App\Livewire\Admin\AgentCenter::class)->html(),
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
