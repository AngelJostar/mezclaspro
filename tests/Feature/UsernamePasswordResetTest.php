<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsernamePasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Password recovery tests require an in-memory database.');
        }

        foreach ([
            '2014_10_12_000000_create_users_table.php',
            '2014_10_12_100000_create_password_reset_tokens_table.php',
            '2014_10_12_200000_add_two_factor_columns_to_users_table.php',
            '2024_04_11_013606_create_permission_tables.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Schema::table('users', function (Blueprint $table) {
            $table->text('credential_password')->nullable();
            $table->string('training_username')->nullable();
            $table->string('training_password')->nullable();
            $table->text('training_credential_password')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
        });
        config(['hashing.bcrypt.rounds' => 4]);
    }

    public function test_server_issued_link_renders_username_form_without_changing_password(): void
    {
        $user = $this->account();
        $before = $user->fresh()->getRawOriginal();
        $this->assertSame(0, Artisan::call('users:password-reset-link', ['username' => ' RECUPERACION ']));
        preg_match('~https?://\S+~', Artisan::output(), $matches);
        $this->assertNotEmpty($matches);
        $url = $matches[0];
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->assertSame($user->username, $query['username']);
        $this->assertTrue(Password::tokenExists($user, $token));
        $this->assertSame($before, $user->fresh()->getRawOriginal());
        $this->assertNotSame($token, DB::table('password_reset_tokens')->value('token'));

        $this->withSession(['_old_input' => ['username' => null]])->get($url)->assertOk()
            ->assertSee('name="username"', false)
            ->assertSee('value="recuperacion"', false)
            ->assertDontSee('name="email"', false)
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_reset_preserves_role_and_training_password_and_allows_software_login(): void
    {
        $user = $this->account();
        $user->assignRole(Role::create(['name' => 'Super Admin', 'guard_name' => 'web']));
        $before = $user->fresh()->getRawOriginal();
        $token = Password::createToken($user);
        $this->post('/reset-password', $this->input($user, $token))
            ->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('Nueva-segura-123', $user->password));
        $this->assertSame('Nueva-segura-123', $user->credential_password);
        $this->assertTrue($user->hasRole('Super Admin'));
        $this->assertTrue($user->is_active);
        $this->assertSame($before['training_password'], $user->getRawOriginal('training_password'));
        $this->assertSame($before['training_credential_password'], $user->getRawOriginal('training_credential_password'));
        $this->assertNotSame($before['remember_token'], $user->remember_token);
        $this->assertFalse(Password::tokenExists($user, $token));
        $this->assertGuest();

        $this->post('/reset-password', $this->input($user, $token))->assertSessionHasErrors('username');
        $this->post('/login', ['username' => $user->username, 'password' => 'Nueva-segura-123'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_expired_and_other_users_tokens_cannot_reset_an_account(): void
    {
        $user = $this->account();
        $other = $this->account('otro-usuario');
        $before = $user->password;
        $this->post('/reset-password', $this->input($user, str_repeat('a', 64)))->assertSessionHasErrors('username');
        $this->post('/reset-password', $this->input($user, Password::createToken($other)))->assertSessionHasErrors('username');
        $token = Password::createToken($user);
        DB::table('password_reset_tokens')->where('email', $user->username)->update([
            'created_at' => now()->subMinutes(config('auth.passwords.users.expire') + 1),
        ]);
        $this->post('/reset-password', $this->input($user, $token))->assertSessionHasErrors('username');
        $this->assertSame($before, $user->fresh()->password);
    }

    public function test_new_link_invalidates_previous_link(): void
    {
        $user = $this->account();
        $old = Password::createToken($user);
        $new = Password::createToken($user);
        $this->assertFalse(Password::tokenExists($user, $old));
        $this->assertTrue(Password::tokenExists($user, $new));
    }

    public function test_blocked_accounts_cannot_get_or_use_a_recovery_link(): void
    {
        $user = $this->account();
        $token = Password::createToken($user);
        $user->update(['is_active' => false]);
        $before = $user->fresh()->getRawOriginal();
        $this->assertSame(1, Artisan::call('users:password-reset-link', ['username' => $user->username]));
        $this->post('/reset-password', $this->input($user, $token))->assertSessionHasErrors('username');
        $this->assertSame($before, $user->fresh()->getRawOriginal());
        $this->assertGuest();
    }

    public function test_unknown_accounts_do_not_receive_a_recovery_token(): void
    {
        $this->assertSame(1, Artisan::call('users:password-reset-link', ['username' => 'no-existe']));
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    public function test_password_must_be_confirmed_and_meet_password_rules(): void
    {
        $user = $this->account();
        $token = Password::createToken($user);
        $before = $user->password;
        $this->post('/reset-password', array_replace($this->input($user, $token), [
            'password_confirmation' => 'otra-contrasena',
        ]))->assertSessionHasErrors('password');
        $this->post('/reset-password', array_replace($this->input($user, $token), [
            'password' => 'corta', 'password_confirmation' => 'corta',
        ]))->assertSessionHasErrors('password');
        $this->assertSame($before, $user->fresh()->password);
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_reset_submission_is_rate_limited(): void
    {
        $user = $this->account();
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/reset-password', $this->input($user, str_repeat('b', 64)))->assertUnprocessable();
        }
        $this->postJson('/reset-password', $this->input($user, str_repeat('b', 64)))->assertStatus(429);
    }

    private function account(string $username = 'recuperacion'): User
    {
        return User::factory()->create([
            'username' => $username,
            'hospital_id' => null,
            'password' => Hash::make('anterior-segura'),
        ]);
    }

    private function input(User $user, string $token): array
    {
        return [
            'username' => $user->username,
            'token' => $token,
            'password' => 'Nueva-segura-123',
            'password_confirmation' => 'Nueva-segura-123',
        ];
    }
}
