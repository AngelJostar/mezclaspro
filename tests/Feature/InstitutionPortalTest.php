<?php

namespace Tests\Feature;

use App\Models\Institucion;
use App\Models\RequestQuotation;
use App\Models\User;
use App\Models\Nutricionales\Solicitud;
use App\Support\InstitutionPortal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\RequestQuotationData;
use Tests\TestCase;

class InstitutionPortalTest extends TestCase
{
    private User $account;

    protected function setUp(): void
    {
        parent::setUp();
        RequestQuotationData::seed();
        (require database_path('migrations/2026_10_28_120000_add_institution_account_to_users.php'))->up();
        (require database_path('migrations/2026_09_14_000007_create_mixture_messages.php'))->up();
        $this->account = User::create(['name' => 'Consulta institucional', 'username' => 'institucion', 'institution_id' => 1, 'is_active' => true]);
        $this->account->assignRole('Institucion');
        foreach (InstitutionPortal::PERMISSIONS as $name) $this->account->givePermissionTo(Permission::findOrCreate($name));
        DB::table('cliente_hospital')->insert(['cliente_id' => 1, 'hospital_id' => 2]);
        DB::table('hospitals')->insert(['id' => 3, 'name' => 'Hospital fuera de institución']);
        DB::table('cliente_hospital')->insert(['cliente_id' => 2, 'hospital_id' => 3]);
        DB::table('solicitud_oncos')->insert(['id' => 5, 'hospital_id' => 3, 'tipo_solicitud' => 'oncologicos',
            'estado' => 'pendiente', 'nombre_paciente' => 'Paciente reservado', 'created_at' => now()]);
        RequestQuotation::forceCreate(['id' => 6, 'hospital_id' => 3, 'institution_id' => 2, 'created_by' => 2,
            'category' => 'oncologicos', 'patient_name' => 'Paciente reservado', 'status' => 'enviada',
            'price_list_id' => 2, 'price_list_name' => 'Lista externa', 'total' => 100]);
        $this->actingAs($this->account);
    }

    public function test_home_aggregates_related_hospitals_and_filters_without_leaking_other_hospitals(): void
    {
        $this->get(route('admin.solicitudes.index'))->assertOk()
            ->assertViewHas('requests', fn ($rows) => $rows->count() === 7 && $rows->pluck('hospital_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all() === [1, 2])
            ->assertSeeText('Perfil Institución')->assertSeeText('Solo consulta')
            ->assertDontSeeText('Paciente reservado')->assertDontSee('data-request-create-menu', false);
        $this->get(route('admin.solicitudes.index', ['institution_hospital' => 2]))->assertOk()
            ->assertViewHas('requests', fn ($rows) => $rows->count() === 2 && $rows->every(fn ($row) => (int) $row['hospital_id'] === 2));
        $this->get(route('admin.solicitudes.index', ['institution_hospital' => 3]))->assertNotFound();
        $this->get(route('admin.solicitudes.index', ['institution_hospital' => '']))->assertOk()
            ->assertViewHas('requests', fn ($rows) => $rows->count() === 7);
        $this->get(route('admin.solicitudes.index', ['tipo' => 'antibioticos']))->assertOk()
            ->assertViewHas('requests', fn ($rows) => $rows->count() === 2 && $rows->every(fn ($row) => $row['type'] === 'antibioticos'));
    }

    public function test_quotations_are_readable_for_both_hospitals_but_foreign_ids_and_writes_are_denied(): void
    {
        $this->get(route('admin.solicitudes.cotizacion.index'))->assertOk()
            ->assertViewHas('quotations', fn ($rows) => $rows->modelKeys() === [4, 3, 5, 2, 1])
            ->assertDontSeeText('Paciente reservado')->assertDontSee('data-quotation-send=', false);
        foreach ([1, 5] as $id) $this->getJson(route('admin.solicitudes.cotizacion.show', $id))->assertOk()->assertJsonPath('editable', false);
        $this->getJson(route('admin.solicitudes.cotizacion.show', 6))->assertForbidden();
        $this->postJson(route('admin.solicitudes.cotizacion.store'), [])->assertForbidden();
        $this->putJson(route('admin.solicitudes.cotizacion.update', 1), [])->assertForbidden();
        $this->postJson(route('admin.solicitudes.cotizacion.email', 1), [])->assertForbidden();
        $this->get(route('admin.solicitudes.cotizacion.index', ['hospital_id' => 3]))->assertOk()
            ->assertViewHas('quotations', fn ($rows) => $rows->isEmpty());
    }

    public function test_read_only_boundary_blocks_management_forms_livewire_and_inherited_write_permissions(): void
    {
        Role::findByName('Institucion')->givePermissionTo(Permission::findOrCreate('oncologicos_solicitudes_create'));
        $this->account->unsetRelation('roles');
        $this->assertFalse($this->account->can('oncologicos_solicitudes_create'));
        $this->assertFalse(RequestQuotation::canCreate($this->account, 'oncologicos'));
        foreach (['admin.instituciones.index', 'admin.nutricionales.solicitudes.create', 'admin.capacitaciones.programas'] as $route) {
            if (\Illuminate\Support\Facades\Route::has($route)) $this->get(route($route))->assertForbidden();
        }
        $this->postJson('/livewire/update', [])->assertForbidden();
        $this->postJson(route('admin.nutricionales.solicitudes.store'), [])->assertForbidden();
        $this->putJson(route('admin.nutricionales.solicitudes.update', 11), [])->assertForbidden();
        $this->deleteJson('/admin/instituciones/1')->assertForbidden();
        $this->get(route('admin.oncologicos.mezclas.index', 5))->assertNotFound();
        $this->get(route('admin.oncologicos.mezclas.solicitudCompleta', 5))->assertNotFound();
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.solicitudes.index'));
        $this->account->syncRoles('Super Admin');
        $this->get(route('admin.solicitudes.index'))->assertForbidden();
    }

    public function test_removed_hospitals_and_empty_membership_cannot_be_recovered_by_stale_session_or_ids(): void
    {
        $this->get(route('admin.solicitudes.index', ['institution_hospital' => 2]))->assertOk();
        Institucion::find(1)->hospitals()->detach(2);
        $this->get(route('admin.solicitudes.index'))->assertOk()
            ->assertViewHas('requests', fn ($rows) => $rows->count() === 5);
        $this->getJson(route('admin.solicitudes.cotizacion.show', 5))->assertForbidden();
        $this->get(route('admin.oncologicos.mezclas.index', 3))->assertNotFound();
        $this->get(route('admin.nutricionales.solicitudes.show', 12))->assertNotFound();
        Institucion::find(1)->hospitals()->detach();
        $this->get(route('admin.solicitudes.index'))->assertOk()->assertViewHas('requests', fn ($rows) => $rows->isEmpty());
        $this->get(route('admin.solicitudes.cotizacion.index'))->assertOk()->assertViewHas('quotations', fn ($rows) => $rows->isEmpty());
    }

    public function test_nutrition_uses_the_recorded_hospital_before_a_legacy_author_hospital(): void
    {
        DB::table('solicituds')->insert(['id' => 13, 'user_id' => 1, 'hospital_id' => 3]);
        DB::table('solicituds')->insert(['id' => 14, 'user_id' => 2, 'hospital_id' => null]);
        $this->assertEqualsCanonicalizing([11, 12, 14], Solicitud::forRequestUser($this->account)->pluck('id')->all());
        Institucion::find(1)->hospitals()->detach(2);
        $this->assertSame([11], Solicitud::forRequestUser($this->account)->pluck('id')->all());
    }

    public function test_messages_and_exports_are_scoped_and_api_tokens_cannot_enable_writes(): void
    {
        $this->getJson(route('admin.solicitudes.mensajes.show', ['kind' => 'oncologicos', 'target' => 3]))
            ->assertOk()->assertJsonPath('read_only', true)->assertJsonPath('can_send', false);
        $this->postJson(route('admin.solicitudes.mensajes.store', ['kind' => 'oncologicos', 'target' => 3]), [])->assertForbidden();
        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->get(route('admin.solicitudes.exportar'))->assertOk();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('solicitudes_todas.xlsx', fn ($export) => $export->collection()->count() === 7);
        \Laravel\Sanctum\Sanctum::actingAs($this->account, ['*']);
        $this->getJson('/api/user')->assertForbidden();
        $this->postJson('/api/mobile/hospital/quotation-requests', [])->assertForbidden();
    }
}
