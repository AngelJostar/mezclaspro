<?php

namespace Tests\Feature;

use App\Models\Institucion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Spatie\Permission\Models\Permission;
use Tests\Fixtures\HospitalCredentialList;
use Tests\TestCase;

class InstitutionAccountTest extends TestCase
{
    private User $manager;
    private Institucion $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = HospitalCredentialList::seed()->refresh();
        Schema::table('clientes', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
        Schema::table('users', fn (Blueprint $table) => $table->rememberToken());
        Schema::table('users', fn (Blueprint $table) => $table->string('lastname')->default(''));
        $this->institution = Institucion::create(['nombre' => 'Institución de prueba']);
        $this->actingAs($this->manager);
    }

    private function createAccount(array $data = [])
    {
        return $this->postJson(route('admin.instituciones.account.store', $this->institution),
            $data + ['username' => 'institucion-prueba', 'password' => 'Clave-institucion-2026']);
    }

    public function test_admin_creates_and_edits_an_independent_read_only_institution_account(): void
    {
        $hospital = HospitalCredentialList::hospital();
        $this->institution->hospitals()->attach($hospital);
        $legacy = HospitalCredentialList::account($hospital, 'hospital-prueba');
        $this->createAccount(['username' => ' Institucion-Prueba '])->assertCreated();
        $account = $this->institution->account()->firstOrFail();
        $this->assertSame('institucion-prueba', $account->username);
        $this->assertNull($account->hospital_id);
        $this->assertTrue($account->isInstitutionAccount());
        $this->assertTrue($account->hasRole('Institucion'));
        $this->assertTrue(Hash::check('Clave-institucion-2026', $account->password));
        $this->assertNotSame('Clave-institucion-2026', $account->getRawOriginal('credential_password'));
        $this->assertSame([$hospital->id], $account->accessibleHospitalIds());
        $this->assertSame($hospital->id, $legacy->fresh()->hospital_id);
        $this->assertFalse($legacy->fresh()->isInstitutionAccount());
        $this->assertArrayNotHasKey('credential_password', $account->toArray());
        $this->patchJson(route('admin.instituciones.account.update', [$this->institution, 'username']), ['username' => 'institucion-nueva'])->assertOk();
        $this->patchJson(route('admin.instituciones.account.update', [$this->institution, 'password']), ['password' => 'Otra-clave-2026'])->assertOk();
        $this->assertSame('institucion-nueva', $account->fresh()->username);
        $this->assertTrue(Hash::check('Otra-clave-2026', $account->fresh()->password));
        $this->assertSame('Otra-clave-2026', $account->fresh()->credential_password);
    }

    public function test_creation_validates_unique_accesses_and_requires_account_management_permission(): void
    {
        User::create(['name' => 'Existente', 'training_username' => 'ocupado']);
        $this->createAccount(['username' => 'ocupado'])->assertUnprocessable()->assertJsonValidationErrors('username');
        $this->createAccount(['password' => '123'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->createAccount()->assertCreated();
        $this->createAccount(['username' => 'otra-cuenta'])->assertConflict();
        $manager = User::create(['name' => 'Gestor sin credenciales', 'username' => 'gestor-limitado', 'is_active' => true]);
        $manager->givePermissionTo(Permission::findOrCreate('menu.instituciones.list'));
        $this->actingAs($manager);
        $this->createAccount(['username' => 'no-permitido'])->assertForbidden();
        $this->patchJson(route('admin.instituciones.account.update', [$this->institution, 'password']), ['password' => 'No-autorizado'])->assertForbidden();
    }

    public function test_membership_and_institution_blocking_apply_immediately_and_deletion_removes_access(): void
    {
        $this->createAccount()->assertCreated();
        $account = $this->institution->account()->firstOrFail();
        $hospital = HospitalCredentialList::hospital();
        $this->assertSame([], $account->accessibleHospitalIds());
        $this->institution->hospitals()->attach($hospital);
        $this->assertTrue($account->canConsultHospital($hospital->id));
        $this->institution->hospitals()->detach($hospital);
        $this->assertFalse($account->canConsultHospital($hospital->id));
        $this->institution->update(['is_active' => false]);
        $this->assertTrue($account->isBlockedByOrganization());
        $this->actingAs($account)->getJson(route('admin.solicitudes.index'))->assertForbidden();
        $this->institution->delete();
        $remainingAccount = User::find($account->id);
        $this->assertTrue(!$remainingAccount || $remainingAccount->isBlockedByOrganization());
    }

    public function test_institution_credentials_log_in_to_the_client_home_and_blocked_institutions_cannot_log_in(): void
    {
        $this->createAccount()->assertCreated();
        $id = $this->institution->account()->value('id');
        auth()->logout();
        $credentials = ['username' => 'institucion-prueba', 'password' => 'Clave-institucion-2026'];
        $this->post('/login', $credentials)->assertRedirect(route('admin.solicitudes.index'));
        $this->assertAuthenticatedAs(User::find($id));
        $this->post('/logout')->assertRedirect();
        $this->institution->update(['is_active' => false]);
        $this->post('/login', $credentials)->assertSessionHasErrors('username');
        $this->assertGuest();
    }
}
