<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

class LivewireAssetPathTest extends TestCase
{
    public function test_livewire_script_uses_the_installation_path(): void
    {
        foreach (['', '/mezclaspro/public'] as $basePath) {
            $request = Request::create('http://localhost'.$basePath.'/admin/solicitudes', 'GET', [], [], [], [
                'SCRIPT_NAME' => $basePath.'/index.php',
                'PHP_SELF' => $basePath.'/index.php',
                'SCRIPT_FILENAME' => '/var/www/public/index.php',
            ]);
            $this->app->instance('request', $request);

            $this->assertSame($basePath, $request->getBaseUrl());
            $html = view('layouts.includes.livewire-scripts')->render();
            $this->assertStringContainsString('src="'.$basePath.'/livewire/livewire.js?id=', $html);
            $this->assertStringContainsString('data-update-uri', $html);
        }
    }
}
