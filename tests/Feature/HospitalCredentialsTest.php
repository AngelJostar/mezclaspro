<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\HospitalController;
use App\Models\Institucion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Fixtures\HospitalCredentialList as Fixture;
use Tests\TestCase;

class HospitalCredentialsTest extends TestCase
{
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = Fixture::seed();
    }

    public function test_super_admin_sees_each_hospital_account_without_reveal_controls_or_password_changes(): void
    {
        $hospital = Fixture::hospital();
        $first = Fixture::account($hospital, 'hospital.uno', 'clave-uno');
        Fixture::account($hospital, 'hospital.dos', 'clave-dos', 'Cliente')->update(['is_active' => false]);
        Fixture::account($hospital, 'administrador.interno', 'no-mostrar-admin', 'Admin');
        Fixture::account($hospital, 'solo.cursos', 'no-mostrar-cursos', 'Capacitacion');
        $before = $first->fresh()->getRawOriginal();
        $response = $this->index();
        $html = $response->getContent();
        foreach (['hospital.uno', 'hospital.dos', 'clave-uno', 'clave-dos'] as $value) {
            $this->assertStringContainsString($value, $html);
        }
        foreach (['administrador.interno', 'no-mostrar-admin', 'solo.cursos', 'no-mostrar-cursos', 'type="password"'] as $value) {
            $this->assertStringNotContainsString($value, $html);
        }
        $this->assertSame($before, $first->fresh()->getRawOriginal());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $xpath = $this->xpath($html);
        $this->assertSame(8, $xpath->query('//table[@id="hospitalsTable"]/thead/tr/th')->length);
        $this->assertSame(8, $xpath->query('//table[@id="hospitalsTable"]/tbody/tr[1]/*')->length);
        $this->assertSame(2, $xpath->query('//dl[@class="hospital-credential"]')->length);
        $this->assertStringContainsString('targets: [3,6,7]', $html);
    }

    public function test_other_roles_cannot_receive_credentials_even_with_hospital_permission_and_forged_query(): void
    {
        $hospital = Fixture::hospital();
        Fixture::account($hospital, 'acceso.privado', 'clave-privada');
        $this->manager->givePermissionTo(Permission::create(['name' => 'hospitales', 'guard_name' => 'web']));
        foreach (['Admin', 'Cliente', 'Institucion'] as $role) {
            $this->manager->syncRoles($role);
            DB::enableQueryLog();
            DB::flushQueryLog();
            $response = $this->index(['canViewHospitalCredentials' => true]);
            $html = $response->getContent();
            $this->assertStringNotContainsString('acceso.privado', $html);
            $this->assertStringNotContainsString('clave-privada', $html);
            $this->assertStringNotContainsString('Usuario y contrase', $html);
            $this->assertSame(7, $this->xpath($html)->query('//table[@id="hospitalsTable"]/thead/tr/th')->length);
            $this->assertFalse($response->getOriginalContent()->getData()['hospitals']->first()->relationLoaded('users'));
            $this->assertStringNotContainsString('credential_password', implode(' ', array_column(DB::getQueryLog(), 'query')));
            $this->assertStringContainsString('targets: [3,6]', $html);
        }
    }

    public function test_unavailable_stale_and_corrupt_passwords_have_honest_empty_states(): void
    {
        $hospital = Fixture::hospital();
        Fixture::account($hospital, 'solo.hash')->update(['credential_password' => null]);
        Fixture::account($hospital, 'antigua')->update(['credential_password' => 'clave-vieja']);
        $corrupt = Fixture::account($hospital, 'cifrado.roto');
        DB::table('users')->where('id', $corrupt->id)->update(['credential_password' => 'invalid-ciphertext']);
        Fixture::account($hospital, 'sin.clave')->update(['password' => null]);
        $html = $this->index()->getContent();
        $this->assertSame(3, substr_count($html, 'Configurada (no recuperable)'));
        $this->assertStringContainsString('Sin configurar', $html);
        foreach (['clave-prueba', 'clave-vieja', 'invalid-ciphertext', $corrupt->password] as $value) {
            $this->assertStringNotContainsString($value, $html);
        }
    }

    public function test_institution_filter_limits_both_hospitals_and_their_credentials(): void
    {
        $institution = Institucion::create(['nombre' => 'Institucion de prueba']);
        $hospital = Fixture::hospital('Hospital incluido');
        $hospital->instituciones()->attach($institution);
        Fixture::account($hospital, 'incluido', 'clave-incluida');
        Fixture::account(Fixture::hospital('Hospital excluido'), 'excluido', 'clave-excluida');
        $html = $this->index(['institution_id' => $institution->id])->getContent();
        $this->assertStringContainsString('clave-incluida', $html);
        $this->assertStringNotContainsString('clave-excluida', $html);
        $this->assertStringNotContainsString('Hospital excluido', $html);
    }

    public function test_missing_accounts_and_empty_catalog_render_without_errors(): void
    {
        $this->assertSame(200, $this->index()->getStatusCode());
        Fixture::hospital();
        $html = $this->index()->getContent();
        $this->assertStringContainsString('Sin usuario asignado', $html);
        $this->assertStringNotContainsString('type="password"', $html);
    }

    public function test_a_catalog_with_only_one_hospital_role_still_loads(): void
    {
        \Spatie\Permission\Models\Role::where('name', 'Cliente')->delete();
        Fixture::account(Fixture::hospital(), 'hospital.unico', 'clave-unica');
        $this->assertStringContainsString('clave-unica', $this->index()->getContent());
    }

    public function test_passwords_are_escaped_and_only_software_credentials_are_shown(): void
    {
        $account = Fixture::account(Fixture::hospital(), 'hospital.seguro', '<script>alert(1)</script>');
        $account->update(['training_username' => 'capacitacion.oculta', 'training_credential_password' => 'clave-cursos']);
        $html = $this->index()->getContent();
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        foreach (['<script>alert(1)</script>', 'capacitacion.oculta', 'clave-cursos'] as $value) {
            $this->assertStringNotContainsString($value, $html);
        }
    }

    public function test_guest_must_log_in_before_accessing_the_hospital_list(): void
    {
        auth()->logout();
        $this->get(route('admin.hospitals.index'))->assertRedirect(route('login'));
    }

    private function index(array $query = []): Response
    {
        $request = Request::create(route('admin.hospitals.index'), 'GET', $query);
        $request->setUserResolver(fn () => $this->manager);

        return app(HospitalController::class)->index($request);
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new \DOMXPath($document);
    }
}
