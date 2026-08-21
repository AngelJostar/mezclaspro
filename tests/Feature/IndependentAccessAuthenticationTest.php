<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IndependentAccessAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_authenticate_with_software_credentials(): void
    {
        $user = $this->createAccessUser();

        $this->post('/login', [
            'username' => 'usuario-software',
            'password' => 'clave-software',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('access_context'));
    }

    public function test_user_can_authenticate_with_training_credentials(): void
    {
        $user = $this->createAccessUser();

        $this->post('/login', [
            'username' => 'usuario-capacitacion',
            'password' => 'clave-capacitacion',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('training', session('access_context'));
    }

    public function test_blocked_user_cannot_authenticate_with_either_access(): void
    {
        $this->createAccessUser(['is_active' => false]);

        $this->post('/login', [
            'username' => 'usuario-software',
            'password' => 'clave-software',
        ]);

        $this->assertGuest();

        $this->post('/login', [
            'username' => 'usuario-capacitacion',
            'password' => 'clave-capacitacion',
        ]);

        $this->assertGuest();
    }

    private function createAccessUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'username' => 'usuario-software',
            'password' => Hash::make('clave-software'),
            'credential_password' => 'clave-software',
            'training_username' => 'usuario-capacitacion',
            'training_password' => Hash::make('clave-capacitacion'),
            'training_credential_password' => 'clave-capacitacion',
            'hospital_id' => null,
            'is_active' => true,
        ], $attributes));
    }
}
