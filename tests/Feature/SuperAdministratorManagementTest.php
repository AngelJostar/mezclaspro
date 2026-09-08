<?php

namespace Tests\Feature;

use App\Livewire\Nutricionales\SolicitudesTable as NutritionSolicitudesTable;
use App\Models\DistributionRoute;
use App\Models\User;
use App\Services\InstitutionBillingPendingSummaryService;
use App\Support\SolicitudStatusFilter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SuperAdministratorManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
        Role::query()->firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_only_a_super_administrator_can_open_the_management_page(): void
    {
        $superAdministrator = $this->userWithRole('Super Admin');
        $administrator = $this->userWithRole('Admin');
        $personnel = $this->userWithRole('Quimico o Tecnico');

        $this->mock(InstitutionBillingPendingSummaryService::class, function ($mock) {
            $mock->shouldReceive('counts')->once()->andReturn(['yellow' => 0, 'red' => 0]);
        });

        $this->actingAs($superAdministrator)
            ->get(route('admin.superadministrator.index'))
            ->assertOk()
            ->assertSee('Superadministrador')
            ->assertSee($administrator->username)
            ->assertSee($personnel->username);

        $this->actingAs($administrator)
            ->get(route('admin.superadministrator.index'))
            ->assertForbidden();
    }

    public function test_super_administrator_panel_starts_collapsed_and_shows_waste_report_filters(): void
    {
        $superAdministrator = $this->userWithRole('Super Admin');
        $this->userWithRole('Admin');

        $this->mock(InstitutionBillingPendingSummaryService::class, function ($mock) {
            $mock->shouldReceive('counts')->once()->andReturn(['yellow' => 0, 'red' => 0]);
        });

        $this->actingAs($superAdministrator)
            ->get(route('admin.superadministrator.index'))
            ->assertOk()
            ->assertSee('Autorizaciones')
            ->assertDontSee('Administradores actuales')
            ->assertSee('Reporte de mermas')
            ->assertSee('Merma de remanente')
            ->assertSee('Merma de frasco')
            ->assertSee('Solicitudes de Merma')
            ->assertSee('Marca')
            ->assertSee('Precio costo por')
            ->assertSee('mililitro')
            ->assertSee('frasco')
            ->assertSee('(Precio de compra)')
            ->assertSee('name="waste_from_month"', false)
            ->assertSee('name="waste_from_year"', false)
            ->assertSee('name="waste_to_month"', false)
            ->assertSee('name="waste_to_year"', false)
            ->assertSee('id="waste-report-table"', false)
            ->assertSee('js-waste-column-filter', false)
            ->assertSee('js-waste-column-sort', false)
            ->assertSee('min-height: clamp(500px, 62vh, 760px);', false)
            ->assertSee('Aplicar filtro')
            ->assertSee('Borrar filtro')
            ->assertSee('authorizationsOpen: false', false)
            ->assertSee("wasteOpen: false, wasteFilter: 'all'", false)
            ->assertSee('aria-controls="authorizations-table"', false)
            ->assertSee('aria-controls="waste-report-content"', false);
    }

    public function test_waste_report_normalizes_a_month_range_and_stays_open(): void
    {
        $superAdministrator = $this->userWithRole('Super Admin');

        $this->mock(InstitutionBillingPendingSummaryService::class, function ($mock) {
            $mock->shouldReceive('counts')->once()->andReturn(['yellow' => 0, 'red' => 0]);
        });

        $this->actingAs($superAdministrator)
            ->get(route('admin.superadministrator.index', [
                'waste_from_month' => '09',
                'waste_from_year' => '2026',
                'waste_to_month' => '08',
                'waste_to_year' => '2026',
            ]))
            ->assertOk()
            ->assertSee('value="08" selected', false)
            ->assertSee('value="09" selected', false)
            ->assertSee('value="2026" selected', false)
            ->assertSee("wasteOpen: true, wasteFilter: 'all'", false)
            ->assertSee('Borrar filtro');
    }

    public function test_oncology_container_waste_is_only_applied_after_super_administrator_approval(): void
    {
        $requester = $this->userWithRole('Admin');
        $superAdministrator = $this->userWithRole('Super Admin');
        [$laboratoryId, $warehouseId] = $this->inventoryLocation();
        $catalogId = DB::table('medicines_catalog')->insertGetId([
            'denominacion' => 'Medicamento de prueba',
            'catalog_category' => 'oncologicos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $presentationId = DB::table('medicine_presentations')->insertGetId([
            'catalog_id' => $catalogId,
            'presentacion' => 'Frasco 100 ml',
            'contenido_valor' => 100,
            'contenido_unidad' => 'ml',
            'marca' => 'Marca prueba',
            'volumen_diluyente' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $batchId = DB::table('medicine_batches')->insertGetId([
            'laboratory_id' => $laboratoryId,
            'warehouse_id' => $warehouseId,
            'medicine_presentation_id' => $presentationId,
            'lote' => 'LOTE-ONCO-1',
            'stock_inicial' => 10,
            'stock_actual' => 10,
            'stock_reservado' => 2,
            'stock_ml_inicial' => 1000,
            'stock_ml_actual' => 1000,
            'costo_unitario' => 500,
            'is_current' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($requester)
            ->post(route('admin.oncologicos.inventory.registrarMerma', $batchId), [
                'unit' => 'frasco',
                'quantity' => 2,
                'notes' => 'Frascos dañados durante el traslado interno.',
            ])
            ->assertRedirect();

        $wasteRequestId = (int) DB::table('waste_authorization_requests')->value('id');

        $this->assertDatabaseHas('waste_authorization_requests', [
            'id' => $wasteRequestId,
            'medicine_batch_id' => $batchId,
            'requested_by' => $requester->id,
            'quantity_containers' => 2,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('medicine_batches', [
            'id' => $batchId,
            'stock_actual' => 10,
        ]);
        $this->assertDatabaseCount('medicine_batch_movements', 0);

        $this->actingAs($superAdministrator)
            ->patch(route('admin.superadministrator.waste-requests.approve', $wasteRequestId), [
                'review_notes' => 'Daño físico confirmado.',
            ])
            ->assertRedirect(route('admin.superadministrator.index', [
                'waste_open' => 1,
                'waste_view' => 'requests',
            ]));

        $this->assertDatabaseHas('waste_authorization_requests', [
            'id' => $wasteRequestId,
            'status' => 'approved',
            'reviewed_by' => $superAdministrator->id,
        ]);
        $this->assertDatabaseHas('medicine_batches', [
            'id' => $batchId,
            'stock_actual' => 8,
            'stock_ml_actual' => 800,
        ]);
        $this->assertDatabaseHas('medicine_batch_movements', [
            'medicine_batch_id' => $batchId,
            'movement_type' => 'merma',
            'quantity' => 2,
            'quantity_ml' => 200,
            'reference_type' => 'WasteAuthorizationRequest',
            'reference_id' => $wasteRequestId,
        ]);
    }

    public function test_nutrition_container_waste_rejection_does_not_change_inventory(): void
    {
        $requester = $this->userWithRole('Admin');
        $superAdministrator = $this->userWithRole('Super Admin');
        [$laboratoryId, $warehouseId] = $this->inventoryLocation();
        $catalogId = DB::table('nutrition_medicines_catalog')->insertGetId([
            'denominacion_generica' => 'Nutriente de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $presentationId = DB::table('nutrition_medicine_presentations')->insertGetId([
            'nutrition_medicine_catalog_id' => $catalogId,
            'denominacion_comercial' => 'Nutrición prueba',
            'presentacion' => 'Frasco 250 ml',
            'presentacion_ml' => 250,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $stockId = DB::table('medicine_laboratory_stocks')->insertGetId([
            'nutrition_medicine_presentation_id' => $presentationId,
            'laboratory_id' => $laboratoryId,
            'warehouse_id' => $warehouseId,
            'stock_ml_inicial' => 1000,
            'stock_ml_actual' => 1000,
            'frascos_iniciales' => 4,
            'frascos_actuales' => 4,
            'lote' => 'LOTE-NPT-1',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($requester)
            ->post(route('admin.nutricionales.stocks.registrarMerma', $stockId), [
                'quantity' => 1,
                'notes' => 'Frasco reportado con sello irregular.',
            ])
            ->assertRedirect();

        $wasteRequestId = (int) DB::table('waste_authorization_requests')->value('id');

        $this->actingAs($superAdministrator)
            ->patch(route('admin.superadministrator.waste-requests.reject', $wasteRequestId), [
                'review_notes' => 'El frasco fue inspeccionado y está íntegro.',
            ])
            ->assertRedirect(route('admin.superadministrator.index', [
                'waste_open' => 1,
                'waste_view' => 'requests',
            ]));

        $this->assertDatabaseHas('waste_authorization_requests', [
            'id' => $wasteRequestId,
            'status' => 'rejected',
            'reviewed_by' => $superAdministrator->id,
        ]);
        $this->assertDatabaseHas('medicine_laboratory_stocks', [
            'id' => $stockId,
            'stock_ml_actual' => 1000,
            'frascos_actuales' => 4,
        ]);
        $this->assertDatabaseCount('medicine_stock_movements', 0);
    }

    public function test_non_super_administrator_cannot_authorize_container_waste(): void
    {
        $administrator = $this->userWithRole('Admin');

        $requestId = DB::table('waste_authorization_requests')->insertGetId([
            'domain' => 'oncologico',
            'requested_by' => $administrator->id,
            'quantity_containers' => 1,
            'quantity_ml' => 100,
            'reason' => 'Solicitud de control de acceso.',
            'status' => 'pending',
            'snapshot_product' => 'Producto de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($administrator)
            ->patch(route('admin.superadministrator.waste-requests.approve', $requestId))
            ->assertForbidden();

        $this->assertDatabaseHas('waste_authorization_requests', [
            'id' => $requestId,
            'status' => 'pending',
        ]);
    }

    public function test_a_super_administrator_can_dismiss_an_administrator_and_revoke_access(): void
    {
        config(['session.driver' => 'database']);

        $superAdministrator = $this->userWithRole('Super Admin');
        $administrator = $this->userWithRole('Admin');
        $token = $administrator->createToken('administration-access');

        DB::table('sessions')->insert([
            'id' => 'dismissed-administrator-session',
            'user_id' => $administrator->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($superAdministrator)
            ->patch(route('admin.superadministrator.administrators.dismiss', $administrator))
            ->assertRedirect(route('admin.superadministrator.index'));

        $administrator->refresh();

        $this->assertFalse($administrator->is_active);
        $this->assertFalse($administrator->hasRole('Admin'));
        $this->assertDatabaseMissing('sessions', ['user_id' => $administrator->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_a_super_administrator_can_appoint_eligible_personnel(): void
    {
        $superAdministrator = $this->userWithRole('Super Admin');
        $personnel = $this->userWithRole('Quimico o Tecnico', ['is_active' => false]);

        $this->actingAs($superAdministrator)
            ->patch(route('admin.superadministrator.personnel.appoint', $personnel))
            ->assertRedirect(route('admin.superadministrator.index'));

        $personnel->refresh();

        $this->assertTrue($personnel->is_active);
        $this->assertTrue($personnel->hasRole('Admin'));
        $this->assertCount(1, $personnel->roles);
    }

    public function test_an_institution_account_cannot_be_appointed_as_administrator(): void
    {
        $superAdministrator = $this->userWithRole('Super Admin');
        $institution = $this->userWithRole('Institucion');

        $this->actingAs($superAdministrator)
            ->from(route('admin.superadministrator.index'))
            ->patch(route('admin.superadministrator.personnel.appoint', $institution))
            ->assertRedirect(route('admin.superadministrator.index'))
            ->assertSessionHasErrors('personnel');

        $this->assertFalse($institution->fresh()->hasRole('Admin'));
    }

    public function test_a_blocked_user_cannot_log_in_or_keep_using_an_existing_session(): void
    {
        $blockedUser = User::factory()->create([
            'lastname' => 'Bloqueado',
            'hospital_id' => null,
            'is_active' => false,
        ]);

        $this->post('/login', [
            'username' => $blockedUser->username,
            'password' => 'password',
        ])->assertSessionHasErrors();

        $this->assertGuest();

        $this->actingAs($blockedUser)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_operational_personnel_roles_are_renamed_and_created_without_losing_access(): void
    {
        $generalRole = Role::query()->create([
            'name' => 'Usuario general',
            'guard_name' => 'web',
        ]);
        $permission = Permission::query()->create([
            'name' => 'solicitudes',
            'guard_name' => 'web',
        ]);
        $personnel = User::factory()->create([
            'lastname' => 'Prueba',
            'hospital_id' => null,
            'is_active' => true,
        ]);

        $generalRole->givePermissionTo($permission);
        $personnel->assignRole($generalRole);

        $migration = require database_path('migrations/2026_08_26_000002_update_operational_personnel_roles.php');
        $migration->up();
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertDatabaseMissing('roles', ['name' => 'Usuario general', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'Quimico o Tecnico', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'Mensajero', 'guard_name' => 'web']);
        $this->assertTrue($personnel->fresh()->hasRole('Quimico o Tecnico'));
        $this->assertTrue($personnel->fresh()->can('solicitudes'));
    }

    public function test_personnel_is_separated_between_contracted_and_inactive_sections(): void
    {
        $superAdministrator = $this->userWithRole('Super Admin');
        $laboratoryId = DB::table('laboratories')->insertGetId([
            'nombre' => 'Central de prueba',
            'direccion' => 'Direccion central',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $northLaboratoryId = DB::table('laboratories')->insertGetId([
            'nombre' => 'Central Norte',
            'direccion' => 'Direccion central norte',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $mainWarehouseId = DB::table('warehouses')->insertGetId([
            'laboratory_id' => $laboratoryId,
            'name' => 'Almacen principal',
            'address' => 'Direccion principal',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $northWarehouseId = DB::table('warehouses')->insertGetId([
            'laboratory_id' => $northLaboratoryId,
            'name' => 'Almacen Norte',
            'address' => 'Direccion norte',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $contracted = $this->userWithRole('Quimico o Tecnico', ['warehouse_id' => $mainWarehouseId]);
        $contracted->forceFill(['username' => 'aaa-personnel'])->save();
        $lastContracted = $this->userWithRole('Mensajero', ['warehouse_id' => $northWarehouseId]);
        $lastContracted->forceFill(['username' => 'zzz-personnel'])->save();
        $inactive = $this->userWithRole('Mensajero', [
            'is_active' => false,
            'warehouse_id' => $mainWarehouseId,
        ]);
        $institution = $this->userWithRole('Institucion');

        $this->mock(InstitutionBillingPendingSummaryService::class, function ($mock) {
            $mock->shouldReceive('counts')->times(4)->andReturn(['yellow' => 0, 'red' => 0]);
        });

        $this->actingAs($superAdministrator)
            ->get(route('admin.users.index', ['status' => 'contratados']))
            ->assertOk()
            ->assertSee('Contratados')
            ->assertSee('Dar de baja')
            ->assertSee('Ordenar por personal')
            ->assertSee('Selecciona una central')
            ->assertSee('Central de prueba')
            ->assertSee('Central Norte')
            ->assertSee($contracted->username)
            ->assertDontSee($inactive->username)
            ->assertDontSee($institution->username);

        $this->get(route('admin.users.index', ['status' => 'bajas']))
            ->assertOk()
            ->assertSee('Bajas')
            ->assertSee($inactive->username)
            ->assertDontSee($contracted->username)
            ->assertDontSee($institution->username);

        $this->get(route('admin.users.index', [
            'status' => 'contratados',
            'sort' => 'usuario',
            'direction' => 'desc',
        ]))
            ->assertOk()
            ->assertSeeInOrder([$lastContracted->username, $contracted->username]);

        $this->get(route('admin.users.index', [
            'status' => 'contratados',
            'laboratory_id' => $laboratoryId,
        ]))
            ->assertOk()
            ->assertSee($contracted->username)
            ->assertDontSee($lastContracted->username);
    }

    public function test_deactivating_personnel_moves_them_to_inactive_and_revokes_all_access(): void
    {
        config(['session.driver' => 'database']);

        $superAdministrator = $this->userWithRole('Super Admin');
        $personnel = $this->userWithRole('Capacitacion');
        $token = $personnel->createToken('training-access');

        DB::table('sessions')->insert([
            'id' => 'inactive-personnel-session',
            'user_id' => $personnel->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($superAdministrator)
            ->patch(route('admin.users.deactivate', $personnel))
            ->assertRedirect(route('admin.users.index', ['status' => 'bajas']))
            ->assertSessionHas('status');

        $personnel->refresh();

        $this->assertFalse($personnel->is_active);
        $this->assertTrue($personnel->hasRole('Capacitacion'));
        $this->assertDatabaseMissing('sessions', ['user_id' => $personnel->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);

        $this->actingAs($personnel)
            ->get(route('admin.capacitaciones.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_distribution_routes_page_lists_real_routes_with_filter_and_order_controls(): void
    {
        $superAdministrator = $this->userWithRole('Super Admin');
        $centralLaboratoryId = DB::table('laboratories')->insertGetId([
            'nombre' => 'Central CDMX',
            'estado' => 'Ciudad de Mexico',
            'direccion' => 'Direccion Central CDMX',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $northLaboratoryId = DB::table('laboratories')->insertGetId([
            'nombre' => 'Central Norte',
            'estado' => 'Estado de Mexico',
            'direccion' => 'Direccion Central Norte',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $firstHospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'Hospital Central',
            'laboratory_id' => $centralLaboratoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $secondHospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'Hospital Norte',
            'laboratory_id' => $northLaboratoryId,
            'latitude' => 19.5000000,
            'longitude' => -99.1000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('hospitals')->insert([
            'name' => 'Hospital Disponible',
            'laboratory_id' => $centralLaboratoryId,
            'latitude' => 19.4326000,
            'longitude' => -99.1332000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $morningRoute = DistributionRoute::query()->create([
            'name' => 'Ruta Matutina',
            'code' => 'R-CDMX-001',
            'route_type' => 'vehicular',
            'schedule_start' => '07:00:00',
            'schedule_end' => '11:00:00',
            'status' => 'active',
            'qr_token' => (string) Str::uuid(),
            'created_by' => $superAdministrator->id,
        ]);
        $eveningRoute = DistributionRoute::query()->create([
            'name' => 'Ruta Vespertina',
            'code' => 'R-CDMX-002',
            'route_type' => 'dron',
            'schedule_start' => '13:00:00',
            'schedule_end' => '18:00:00',
            'status' => 'pending',
            'qr_token' => (string) Str::uuid(),
            'created_by' => $superAdministrator->id,
        ]);

        DB::table('distribution_route_hospital')->insert([
            [
                'distribution_route_id' => $morningRoute->id,
                'hospital_id' => $firstHospitalId,
                'stop_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'distribution_route_id' => $eveningRoute->id,
                'hospital_id' => $secondHospitalId,
                'stop_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->mock(InstitutionBillingPendingSummaryService::class, function ($mock) {
            $mock->shouldReceive('counts')->twice()->andReturn(['yellow' => 0, 'red' => 0]);
        });

        $this->actingAs($superAdministrator)
            ->get(route('admin.distribution.routes.index', [
                'sort' => 'name',
                'direction' => 'desc',
            ]))
            ->assertOk()
            ->assertSee('Rutas de distribuci&oacute;n', false)
            ->assertSee('Cat&aacute;logo de Rutas', false)
            ->assertSee('Programaci&oacute;n de Entregas', false)
            ->assertSee('Crear nueva ruta')
            ->assertSee('Selecciona una central')
            ->assertSee('Todas las centrales')
            ->assertSee('Cat&aacute;logo consolidado', false)
            ->assertSee('Central CDMX')
            ->assertSee('Central Norte')
            ->assertSee('data-route-laboratory-card', false)
            ->assertSee('laboratory_id='.$centralLaboratoryId, false)
            ->assertSee('data-route-type-open', false)
            ->assertSee('route-type-modal', false)
            ->assertSee('Selecciona el tipo de ruta que deseas crear')
            ->assertSee('Ruta vehicular')
            ->assertSee('Ruta de dron')
            ->assertSee('Siguiente')
            ->assertSee('Cat&aacute;logo de mensajeros', false)
            ->assertSee('Buscar hospital')
            ->assertSee('Hospitales seleccionados')
            ->assertSee('Mapa para seleccionar hospitales')
            ->assertSee('Hospital Disponible')
            ->assertSee('"available":true', false)
            ->assertSee('"available":false', false)
            ->assertSee('"has_coordinates":true', false)
            ->assertSee('"has_coordinates":false', false)
            ->assertSee(route('admin.distribution.routes.create'), false)
            ->assertSee(route('admin.distribution.messengers.index'), false)
            ->assertDontSee('Buscar ruta...')
            ->assertDontSee('El mensajero debe escanear el c&oacute;digo QR', false)
            ->assertSee('Ordenar por nombre de la ruta')
            ->assertSee('Ordenar por tipo de ruta')
            ->assertSee('Vehicular')
            ->assertSee('Dron')
            ->assertSee('Ver QR')
            ->assertSee('data-route-qr-open', false)
            ->assertSee('route-qr-modal', false)
            ->assertSee('Descargar QR')
            ->assertSee('Editar')
            ->assertSee('data-route-modal-edit', false)
            ->assertSee(route('admin.distribution.routes.update', $morningRoute), false)
            ->assertSee('Hospital Central')
            ->assertSeeInOrder(['Ruta Vespertina', 'Ruta Matutina']);

        $this->actingAs($superAdministrator)
            ->get(route('admin.distribution.routes.index', [
                'laboratory_id' => $centralLaboratoryId,
            ]))
            ->assertOk()
            ->assertSee('aria-current="true"', false)
            ->assertSee('id="route-'.$morningRoute->id.'"', false)
            ->assertDontSee('id="route-'.$eveningRoute->id.'"', false);

        $qrResponse = $this->get(route('admin.distribution.routes.qr', $morningRoute));

        $qrResponse
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $qrResponse->getContent());
    }

    public function test_creating_a_route_preserves_stop_order_and_rejects_assigned_hospitals(): void
    {
        $superAdministrator = $this->userWithRole('Super Admin');
        $assignedHospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'Hospital Asignado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $firstAvailableHospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'Hospital Disponible Uno',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $secondAvailableHospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'Hospital Disponible Dos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $existingRoute = DistributionRoute::query()->create([
            'name' => 'Ruta Existente',
            'code' => 'R-CDMX-001',
            'schedule_start' => '07:00:00',
            'schedule_end' => '18:00:00',
            'status' => 'pending',
            'qr_token' => (string) Str::uuid(),
            'created_by' => $superAdministrator->id,
        ]);
        $existingRoute->hospitals()->attach($assignedHospitalId, ['stop_order' => 1]);

        $this->actingAs($superAdministrator)
            ->post(route('admin.distribution.routes.store'), [
                'name' => 'Ruta Nueva',
                'route_type' => 'vehicular',
                'hospital_ids' => [$secondAvailableHospitalId, $firstAvailableHospitalId],
            ])
            ->assertRedirect(route('admin.distribution.routes.index'))
            ->assertSessionHas('swal');

        $newRoute = DistributionRoute::query()->where('name', 'Ruta Nueva')->firstOrFail();

        $this->assertSame('R-CDMX-002', $newRoute->code);
        $this->assertSame('07:00', substr($newRoute->schedule_start, 0, 5));
        $this->assertSame('18:00', substr($newRoute->schedule_end, 0, 5));
        $this->assertSame('pending', $newRoute->status);
        $this->assertSame('vehicular', $newRoute->route_type);
        $this->assertSame(
            [$secondAvailableHospitalId, $firstAvailableHospitalId],
            $newRoute->hospitals()->pluck('hospitals.id')->all()
        );
        $this->assertSame([1, 2], $newRoute->hospitals()->pluck('stop_order')->all());

        $this->actingAs($superAdministrator)
            ->from(route('admin.distribution.routes.index', ['create' => 1]))
            ->post(route('admin.distribution.routes.store'), [
                'name' => 'Ruta Invalida',
                'hospital_ids' => [$assignedHospitalId],
            ])
            ->assertRedirect(route('admin.distribution.routes.index', ['create' => 1]))
            ->assertSessionHasErrors('hospital_ids');

        $this->assertDatabaseMissing('distribution_routes', ['name' => 'Ruta Invalida']);
    }

    public function test_drone_routes_require_registered_hospital_coordinates(): void
    {
        $superAdministrator = $this->userWithRole('Super Admin');
        $hospitalWithCoordinatesId = DB::table('hospitals')->insertGetId([
            'name' => 'Hospital Con Coordenadas',
            'latitude' => 19.4326000,
            'longitude' => -99.1332000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $hospitalWithoutCoordinatesId = DB::table('hospitals')->insertGetId([
            'name' => 'Hospital Sin Coordenadas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($superAdministrator)
            ->from(route('admin.distribution.routes.index', ['create' => 1]))
            ->post(route('admin.distribution.routes.store'), [
                'name' => 'Ruta Dron Invalida',
                'route_type' => 'dron',
                'hospital_ids' => [$hospitalWithoutCoordinatesId],
            ])
            ->assertRedirect(route('admin.distribution.routes.index', ['create' => 1]))
            ->assertSessionHasErrors('hospital_ids');

        $this->assertDatabaseMissing('distribution_routes', ['name' => 'Ruta Dron Invalida']);

        $this->actingAs($superAdministrator)
            ->post(route('admin.distribution.routes.store'), [
                'name' => 'Ruta Dron Valida',
                'route_type' => 'dron',
                'hospital_ids' => [$hospitalWithCoordinatesId],
            ])
            ->assertRedirect(route('admin.distribution.routes.index'))
            ->assertSessionHas('swal');

        $this->assertDatabaseHas('distribution_routes', [
            'name' => 'Ruta Dron Valida',
            'route_type' => 'dron',
        ]);
    }

    public function test_editing_a_route_updates_its_name_and_order_without_taking_hospitals_from_another_route(): void
    {
        $superAdministrator = $this->userWithRole('Super Admin');
        $firstHospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'Hospital Ruta Uno',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $secondHospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'Hospital Ruta Dos',
            'latitude' => 19.4100000,
            'longitude' => -99.1700000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $availableHospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'Hospital Disponible Nuevo',
            'latitude' => 19.4200000,
            'longitude' => -99.1600000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $assignedHospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'Hospital En Otra Ruta',
            'latitude' => 19.4300000,
            'longitude' => -99.1500000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $route = DistributionRoute::query()->create([
            'name' => 'Ruta Antes de Editar',
            'code' => 'R-CDMX-010',
            'route_type' => 'dron',
            'schedule_start' => '07:00:00',
            'schedule_end' => '18:00:00',
            'status' => 'pending',
            'qr_token' => (string) Str::uuid(),
            'created_by' => $superAdministrator->id,
        ]);
        $otherRoute = DistributionRoute::query()->create([
            'name' => 'Ruta Ajena',
            'code' => 'R-CDMX-011',
            'schedule_start' => '07:00:00',
            'schedule_end' => '18:00:00',
            'status' => 'pending',
            'qr_token' => (string) Str::uuid(),
            'created_by' => $superAdministrator->id,
        ]);

        $route->hospitals()->attach([
            $firstHospitalId => ['stop_order' => 1],
            $secondHospitalId => ['stop_order' => 2],
        ]);
        $otherRoute->hospitals()->attach($assignedHospitalId, ['stop_order' => 1]);

        $this->actingAs($superAdministrator)
            ->get(route('admin.distribution.routes.edit', $route))
            ->assertRedirect(route('admin.distribution.routes.index', ['edit' => $route->id]));

        $this->actingAs($superAdministrator)
            ->patch(route('admin.distribution.routes.update', $route), [
                'route_id' => $route->id,
                'name' => 'Ruta Editada',
                'hospital_ids' => [$secondHospitalId, $availableHospitalId],
            ])
            ->assertRedirect(route('admin.distribution.routes.index'))
            ->assertSessionHas('swal');

        $route->refresh();

        $this->assertSame('Ruta Editada', $route->name);
        $this->assertSame('dron', $route->route_type);
        $this->assertSame(
            [$secondHospitalId, $availableHospitalId],
            $route->hospitals()->pluck('hospitals.id')->all()
        );
        $this->assertSame([1, 2], $route->hospitals()->pluck('stop_order')->all());
        $this->assertDatabaseMissing('distribution_route_hospital', [
            'distribution_route_id' => $route->id,
            'hospital_id' => $firstHospitalId,
        ]);

        $this->actingAs($superAdministrator)
            ->from(route('admin.distribution.routes.index', ['edit' => $route->id]))
            ->patch(route('admin.distribution.routes.update', $route), [
                'route_id' => $route->id,
                'name' => 'Ruta No Valida',
                'hospital_ids' => [$assignedHospitalId],
            ])
            ->assertRedirect(route('admin.distribution.routes.index', ['edit' => $route->id]))
            ->assertSessionHasErrors('hospital_ids')
            ->assertSessionHasInput('route_id', $route->id);

        $this->assertSame('Ruta Editada', $route->fresh()->name);
    }

    public function test_delivery_schedule_combines_nutrition_and_oncology_requests(): void
    {
        $superAdministrator = $this->userWithRole('Super Admin');
        $laboratoryId = DB::table('laboratories')->insertGetId([
            'nombre' => 'Central de Prueba',
            'estado' => 'Ciudad de Mexico',
            'direccion' => 'Direccion de la central',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $hospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'Hospital Programado',
            'laboratory_id' => $laboratoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $superAdministrator->forceFill(['hospital_id' => $hospitalId])->save();
        $warehouseId = DB::table('warehouses')->insertGetId([
            'laboratory_id' => $laboratoryId,
            'name' => 'Almacen de Prueba',
            'address' => 'Direccion de prueba',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $route = DistributionRoute::query()->create([
            'name' => 'Ruta Programada',
            'code' => 'R-TEST-001',
            'schedule_start' => '07:00:00',
            'schedule_end' => '13:00:00',
            'status' => 'pending',
            'qr_token' => (string) Str::uuid(),
            'created_by' => $superAdministrator->id,
        ]);
        DB::table('distribution_route_hospital')->insert([
            'distribution_route_id' => $route->id,
            'hospital_id' => $hospitalId,
            'stop_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $detailId = DB::table('solicitud_details')->insertGetId([
            'fecha_hora_entrega' => '2026-09-01 09:30:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patientId = DB::table('solicitud_patients')->insertGetId([
            'nombre_paciente' => 'Paciente',
            'apellidos_paciente' => 'Nutricional',
            'servicio' => 'Nutricion Parenteral',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('solicituds')->insert([
            'user_id' => $superAdministrator->id,
            'solicitud_detail_id' => $detailId,
            'solicitud_patient_id' => $patientId,
            'estado' => 'preparada',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $oncologyRequestId = DB::table('solicitud_oncos')->insertGetId([
            'user_id' => $superAdministrator->id,
            'hospital_id' => $hospitalId,
            'tipo_solicitud' => 'oncologicos',
            'nombre_paciente' => 'Paciente Oncologico',
            'servicio' => 'UCI Adultos',
            'fecha_entrega' => '2026-09-01 08:00:00',
            'estado' => 'aprobada',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $mixtureId = DB::table('mezclas')->insertGetId([
            'solicitud_id' => $oncologyRequestId,
            'estado' => 'aprobada',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mezcla_medicamentos')->insert([
            'mezcla_id' => $mixtureId,
            'nombre_medicamento' => 'Vancomicina 1 g',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mock(InstitutionBillingPendingSummaryService::class, function ($mock) {
            $mock->shouldReceive('counts')->once()->andReturn(['yellow' => 0, 'red' => 0]);
        });

        $this->actingAs($superAdministrator)
            ->get(route('admin.distribution.deliveries.index', [
                'laboratory_id' => $laboratoryId,
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-01',
            ]))
            ->assertOk()
            ->assertSee('Programaci&oacute;n de Entregas', false)
            ->assertSee('Aplicar rango')
            ->assertSee('Todas')
            ->assertSee('Central de Prueba')
            ->assertDontSee('Almacen de Prueba')
            ->assertSee('Hospitales en proceso de distribuci&oacute;n', false)
            ->assertSee('Paciente Nutricional')
            ->assertSee('Paciente Oncologico')
            ->assertSee('Hospital Programado')
            ->assertSee('Vancomicina 1 g')
            ->assertSee('Ver mezclas')
            ->assertSee('Mandar a ruta');

        $this->post(route('admin.distribution.deliveries.store'), [
            'laboratory_id' => $laboratoryId,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-01',
        ])->assertRedirect(route('admin.distribution.deliveries.index', [
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-01',
            'laboratory_id' => $laboratoryId,
        ]));

        $this->assertDatabaseHas('distribution_delivery_schedules', [
            'warehouse_id' => $warehouseId,
            'hospital_id' => $hospitalId,
            'distribution_route_id' => $route->id,
            'status' => 'scheduled',
        ]);

        $this->patch(route('admin.distribution.deliveries.send'), [
            'laboratory_id' => $laboratoryId,
            'hospital_id' => $hospitalId,
            'date' => '2026-09-01',
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-01',
        ])->assertRedirect(route('admin.distribution.deliveries.index', [
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-01',
            'laboratory_id' => $laboratoryId,
        ]));

        $this->assertDatabaseHas('distribution_delivery_schedules', [
            'warehouse_id' => $warehouseId,
            'hospital_id' => $hospitalId,
            'status' => 'sent',
            'sent_by' => $superAdministrator->id,
        ]);
    }

    public function test_request_status_filters_separate_preparation_route_and_history(): void
    {
        $superAdministrator = $this->userWithRole('Super Admin');
        $hospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'Hospital de filtros',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $superAdministrator->forceFill(['hospital_id' => $hospitalId])->save();
        $warehouseId = DB::table('warehouses')->insertGetId([
            'laboratory_id' => 1,
            'name' => 'Almacen de filtros',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $createRequest = function (string $patientName, string $status, string $deliveryAt) use ($superAdministrator): void {
            $detailId = DB::table('solicitud_details')->insertGetId([
                'fecha_hora_entrega' => $deliveryAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $patientId = DB::table('solicitud_patients')->insertGetId([
                'nombre_paciente' => $patientName,
                'apellidos_paciente' => 'Filtro',
                'servicio' => 'Pruebas',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('solicituds')->insert([
                'user_id' => $superAdministrator->id,
                'solicitud_detail_id' => $detailId,
                'solicitud_patient_id' => $patientId,
                'estado' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        };

        $createRequest('Paciente Pendiente', 'pendiente', '2026-09-02 08:00:00');
        $createRequest('Paciente Preparacion', 'aprobada', '2026-09-03 08:00:00');
        $createRequest('Paciente Ruta', 'preparada', '2026-09-04 08:00:00');
        $createRequest('Paciente Entregada', 'entregada', '2026-09-05 08:00:00');
        $createRequest('Paciente Cancelada', 'cancelada', '2026-09-06 08:00:00');

        DB::table('distribution_delivery_schedules')->insert([
            'warehouse_id' => $warehouseId,
            'hospital_id' => $hospitalId,
            'scheduled_date' => '2026-09-04',
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($superAdministrator)
            ->test(NutritionSolicitudesTable::class, [
                'statusFilter' => SolicitudStatusFilter::PREPARATION,
            ])
            ->assertSee('Paciente Preparacion')
            ->assertDontSee('Paciente Ruta');

        Livewire::actingAs($superAdministrator)
            ->test(NutritionSolicitudesTable::class, [
                'statusFilter' => SolicitudStatusFilter::IN_ROUTE,
            ])
            ->assertSee('Paciente Ruta')
            ->assertDontSee('Paciente Preparacion');

        Livewire::actingAs($superAdministrator)
            ->test(NutritionSolicitudesTable::class, [
                'statusFilter' => SolicitudStatusFilter::HISTORY,
            ])
            ->assertSee('Paciente Entregada')
            ->assertSee('Paciente Cancelada')
            ->assertDontSee('Paciente Pendiente');
    }

    public function test_super_administrator_can_delete_a_warehouse(): void
    {
        $superAdministrator = $this->userWithRole('Super Admin');
        $warehouseId = DB::table('warehouses')->insertGetId([
            'laboratory_id' => 25,
            'name' => 'Almacen temporal',
            'address' => 'Direccion temporal',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($superAdministrator)
            ->delete(route('admin.warehouses.destroy', $warehouseId))
            ->assertRedirect(route('admin.warehouses.index', ['laboratory_id' => 25]))
            ->assertSessionHas('success', 'El almacen Almacen temporal se elimino correctamente.');

        $this->assertDatabaseMissing('warehouses', ['id' => $warehouseId]);
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        $role = Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create(array_merge([
            'lastname' => 'Prueba',
            'hospital_id' => null,
            'is_active' => true,
        ], $attributes));

        $user->assignRole($role);

        return $user;
    }

    private function inventoryLocation(): array
    {
        $laboratoryId = DB::table('laboratories')->insertGetId([
            'nombre' => 'Central de inventario',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'laboratory_id' => $laboratoryId,
            'name' => 'Almacén de inventario',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$laboratoryId, $warehouseId];
    }

    private function createTestSchema(): void
    {
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('laboratory_id')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lastname');
            $table->string('username')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->boolean('is_active')->default(true);
            $table->foreignId('hospital_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type');
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['tokenable_type', 'tokenable_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id']);
        });

        Schema::create('solicitud_details', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha_hora_entrega');
            $table->timestamps();
        });

        Schema::create('solicitud_patients', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_paciente');
            $table->string('apellidos_paciente');
            $table->string('servicio');
            $table->timestamps();
        });

        Schema::create('solicituds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('solicitud_detail_id');
            $table->foreignId('solicitud_patient_id');
            $table->string('estado')->default('pendiente');
            $table->timestamps();
        });

        Schema::create('solicitud_oncos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('hospital_id')->nullable();
            $table->string('tipo_solicitud')->default('oncologicos');
            $table->string('nombre_paciente');
            $table->string('servicio');
            $table->dateTime('fecha_entrega')->nullable();
            $table->string('estado')->nullable();
            $table->timestamps();
        });

        Schema::create('mezclas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id');
            $table->string('estado');
            $table->dateTime('fecha_entrega')->nullable();
            $table->timestamps();
        });

        Schema::create('mezcla_medicamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mezcla_id');
            $table->string('nombre_medicamento')->nullable();
            $table->timestamps();
        });

        Schema::create('laboratories', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('estado')->nullable();
            $table->string('direccion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_id');
            $table->string('name');
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('medicines_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('denominacion');
            $table->string('catalog_category')->default('oncologicos');
            $table->timestamps();
        });

        Schema::create('medicine_presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_id');
            $table->string('presentacion')->nullable();
            $table->decimal('contenido_valor', 12, 4)->nullable();
            $table->string('contenido_unidad')->nullable();
            $table->string('marca')->nullable();
            $table->decimal('volumen_diluyente', 12, 4)->nullable();
            $table->decimal('precio_frasco', 12, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('medicine_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_id');
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('medicine_presentation_id');
            $table->string('lote');
            $table->date('caducidad')->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->integer('stock_inicial')->default(0);
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_reservado')->default(0);
            $table->decimal('stock_ml_inicial', 12, 4)->default(0);
            $table->decimal('stock_ml_actual', 12, 4)->default(0);
            $table->decimal('costo_unitario', 12, 4)->nullable();
            $table->boolean('is_current')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('medicine_batch_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_batch_id');
            $table->foreignId('laboratory_id');
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('movement_type');
            $table->integer('quantity');
            $table->decimal('quantity_ml', 12, 4)->nullable();
            $table->integer('stock_actual_before')->default(0);
            $table->integer('stock_actual_after')->default(0);
            $table->decimal('stock_ml_before', 12, 4)->nullable();
            $table->decimal('stock_ml_after', 12, 4)->nullable();
            $table->integer('stock_reservado_before')->default(0);
            $table->integer('stock_reservado_after')->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('nutrition_medicines_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('denominacion_generica');
            $table->timestamps();
        });

        Schema::create('nutrition_medicine_presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutrition_medicine_catalog_id');
            $table->string('denominacion_comercial')->nullable();
            $table->string('presentacion')->nullable();
            $table->decimal('presentacion_ml', 12, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('medicine_laboratory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutrition_medicine_presentation_id');
            $table->foreignId('laboratory_id');
            $table->foreignId('warehouse_id')->nullable();
            $table->decimal('stock_ml_inicial', 12, 2)->default(0);
            $table->decimal('stock_ml_actual', 12, 2)->default(0);
            $table->decimal('frascos_iniciales', 12, 2)->default(0);
            $table->decimal('frascos_actuales', 12, 2)->default(0);
            $table->string('lote');
            $table->date('caducidad')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('medicine_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_laboratory_stock_id');
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('tipo');
            $table->decimal('cantidad_ml', 12, 2);
            $table->decimal('stock_antes', 12, 2);
            $table->decimal('stock_despues', 12, 2);
            $table->decimal('cantidad_frascos', 12, 2)->nullable();
            $table->decimal('frascos_antes', 12, 2)->nullable();
            $table->decimal('frascos_despues', 12, 2)->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('waste_authorization_requests', function (Blueprint $table) {
            $table->id();
            $table->string('domain');
            $table->foreignId('medicine_batch_id')->nullable();
            $table->foreignId('medicine_laboratory_stock_id')->nullable();
            $table->foreignId('requested_by')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->unsignedInteger('quantity_containers');
            $table->decimal('quantity_ml', 12, 4);
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('snapshot_product');
            $table->string('snapshot_presentation')->nullable();
            $table->string('snapshot_brand')->nullable();
            $table->string('snapshot_lot')->nullable();
            $table->string('snapshot_laboratory')->nullable();
            $table->string('snapshot_warehouse')->nullable();
            $table->decimal('snapshot_cost_per_ml', 12, 4)->nullable();
            $table->decimal('snapshot_cost_per_container', 12, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('distribution_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('route_type')->default('vehicular');
            $table->time('schedule_start');
            $table->time('schedule_end');
            $table->string('status')->default('pending');
            $table->uuid('qr_token')->unique();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('distribution_route_hospital', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_route_id');
            $table->foreignId('hospital_id');
            $table->unsignedInteger('stop_order')->default(1);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['distribution_route_id', 'hospital_id']);
        });

        Schema::create('distribution_route_messenger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_route_id');
            $table->foreignId('user_id');
            $table->timestamps();
            $table->unique(['distribution_route_id', 'user_id']);
        });

        Schema::create('distribution_delivery_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id');
            $table->foreignId('hospital_id');
            $table->foreignId('distribution_route_id')->nullable();
            $table->date('scheduled_date');
            $table->string('status')->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('sent_by')->nullable();
            $table->timestamps();
            $table->unique(['warehouse_id', 'hospital_id', 'scheduled_date']);
        });
    }
}
