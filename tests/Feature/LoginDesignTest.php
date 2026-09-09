<?php

namespace Tests\Feature;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class LoginDesignTest extends TestCase
{
    public function test_login_keeps_the_authentication_contract_with_the_promesa_design(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('Acceso al portal | PROMESA')
            ->assertSee('promesa-logo.png')
            ->assertSee('promesa-home-reference.png')
            ->assertSee('name="username"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('login').'"', false)
            ->assertDontSee('Logo_Prodifem.png');
    }

    public function test_validation_status_and_previous_username_are_preserved_and_escaped(): void
    {
        $this->withSession([
            '_old_input' => ['username' => 'usuario-prueba', 'password' => 'NEVER-RENDER-THIS-PASSWORD'],
            'status' => 'Contraseña actualizada.',
            'errors' => (new ViewErrorBag())->put('default', new MessageBag(['username' => 'Usuario no válido <script>alert(1)</script>'])),
        ])->get('/login')->assertOk()
            ->assertSee('value="usuario-prueba"', false)
            ->assertSee('role="alert"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="login-errors"', false)
            ->assertSee('Contraseña actualizada.')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('NEVER-RENDER-THIS-PASSWORD');
    }

    public function test_connection_label_does_not_claim_ssl_over_http(): void
    {
        $this->get('http://localhost/login')->assertOk()->assertSee('Conexión local')->assertDontSee('Conexión segura SSL');
        $this->get('https://localhost/login')->assertOk()->assertSee('Conexión segura SSL');
    }
}
