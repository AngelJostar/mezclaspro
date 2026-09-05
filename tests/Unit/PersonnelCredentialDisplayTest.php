<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\PersonnelCredentialDisplay;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PersonnelCredentialDisplayTest extends TestCase
{
    public function test_hash_only_accounts_are_configured_without_claiming_a_visible_password(): void
    {
        $user = new User(['password' => Hash::make('software-test'), 'training_password' => Hash::make('training-test')]);
        $attributes = $user->getAttributes();
        $display = PersonnelCredentialDisplay::forUser($user);

        foreach (['software', 'training'] as $context) {
            $this->assertTrue($display[$context]['configured']);
            $this->assertSame('', $display[$context]['value']);
            $this->assertSame('Configurada (no recuperable)', $display[$context]['empty_label']);
        }
        $this->assertSame($attributes, $user->getAttributes());
    }

    public function test_each_access_displays_only_its_own_verified_current_password(): void
    {
        $user = new User([
            'password' => Hash::make('software-test'), 'credential_password' => 'software-test',
            'training_password' => Hash::make('training-test'), 'training_credential_password' => 'training-test',
        ]);
        $display = PersonnelCredentialDisplay::forUser($user);
        $this->assertSame('software-test', $display['software']['value']);
        $this->assertSame('training-test', $display['training']['value']);
    }

    public function test_stale_copies_are_not_presented_as_current_passwords(): void
    {
        $user = new User([
            'password' => Hash::make('new-software'), 'credential_password' => 'old-software',
            'training_password' => Hash::make('new-training'), 'training_credential_password' => 'old-training',
        ]);
        $display = PersonnelCredentialDisplay::forUser($user);
        $this->assertSame('', $display['software']['value']);
        $this->assertSame('', $display['training']['value']);
    }

    public function test_a_copy_from_the_other_context_is_used_only_when_it_matches_the_current_hash(): void
    {
        $user = new User([
            'password' => Hash::make('shared-test'), 'training_password' => Hash::make('shared-test'),
            'training_credential_password' => 'shared-test',
        ]);
        $display = PersonnelCredentialDisplay::forUser($user);
        $this->assertSame('shared-test', $display['software']['value']);

        $user->password = Hash::make('different-test');
        $display = PersonnelCredentialDisplay::forUser($user);
        $this->assertSame('', $display['software']['value']);
        $this->assertSame('shared-test', $display['training']['value']);
    }

    public function test_no_hash_is_reported_as_unconfigured_even_if_an_old_copy_exists(): void
    {
        $user = new User(['credential_password' => 'obsolete-test']);
        $display = PersonnelCredentialDisplay::forUser($user);
        $this->assertFalse($display['software']['configured']);
        $this->assertSame('Sin configurar', $display['software']['empty_label']);
        $this->assertSame('', $display['software']['value']);
    }

    public function test_an_unreadable_encrypted_copy_does_not_break_the_list(): void
    {
        $user = new User(['password' => Hash::make('test-password')]);
        $user->setRawAttributes(array_merge($user->getAttributes(), ['credential_password' => 'corrupt-ciphertext']));
        $display = PersonnelCredentialDisplay::forUser($user);
        $this->assertTrue($display['software']['configured']);
        $this->assertSame('', $display['software']['value']);

        $user->id = 100;
        $html = Blade::render('<x-inline-user-credential-editor :user="$user" field="password" :display-value="$display[\'value\']" :empty-label="$display[\'empty_label\']" :empty-hint="$display[\'empty_hint\']" />', [
            'user' => $user, 'display' => $display['software'],
        ]);
        $this->assertStringContainsString('Configurada (no recuperable)', $html);
        $this->assertStringNotContainsString('corrupt-ciphertext', $html);
    }

    public function test_empty_state_is_not_prefilled_as_a_new_password(): void
    {
        $user = new User(['password' => Hash::make('test-password')]);
        $user->id = 100;
        $html = Blade::render('<x-inline-user-credential-editor :user="$user" field="password" display-value="" empty-label="Configurada (no recuperable)" />', ['user' => $user]);
        $this->assertStringContainsString('name="password" value=""', $html);
        $this->assertStringContainsString('data-original-value=""', $html);
        $this->assertStringNotContainsString($user->password, $html);
    }
}
