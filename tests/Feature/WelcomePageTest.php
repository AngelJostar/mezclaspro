<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    public function test_guests_keep_the_login_privacy_and_contact_links(): void
    {
        $html = $this->renderPage('http://localhost/');

        $this->assertStringContainsString('href="'.route('login').'"', $html);
        $this->assertStringContainsString('href="'.url('aviso-de-privacidad').'"', $html);
        $this->assertStringContainsString('href="tel:+525591862620"', $html);
        $this->assertStringContainsString('href="mailto:contacto@prodifem.com.mx"', $html);
        $this->assertStringContainsString('Precisión y seguridad', $html);
        $this->assertStringContainsString('promesa-logo.png', $html);
        $this->assertStringContainsString('promesa-home-reference.png', $html);
        $this->assertStringNotContainsString('data-institution-hospital-map', $html);
        $this->assertStringNotContainsString('logo-sidebar', $html);
    }

    public function test_signed_in_users_keep_the_dashboard_destination(): void
    {
        $this->actingAs(new User(['id' => 999, 'name' => 'Usuario de prueba']));
        $html = $this->renderPage('http://localhost/');

        $this->assertStringContainsString('href="'.route('admin.dashboard').'"', $html);
        $this->assertStringNotContainsString('href="'.route('login').'"', $html);
    }

    public function test_ssl_label_is_only_shown_for_an_https_connection(): void
    {
        $https = $this->renderPage('https://promesa.example/');
        $this->assertStringContainsString('Conexión segura SSL', $https);
        $this->assertStringContainsString('data-welcome-icon="lock-keyhole"', $https);

        $local = $this->renderPage('http://localhost/');
        $this->assertStringContainsString('Conexión local', $local);
        $this->assertStringNotContainsString('Conexión segura SSL', $local);

        $http = $this->renderPage('http://promesa.example/');
        $this->assertStringContainsString('Conexión no cifrada', $http);
        $this->assertStringNotContainsString('Conexión segura SSL', $http);
    }

    private function renderPage(string $url): string
    {
        $this->app->instance('request', Request::create($url));

        return view('welcome')->render();
    }
}
