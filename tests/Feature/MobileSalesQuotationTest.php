<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\PersonnelProfile;
use App\Models\RequestQuotation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\RequestQuotationCaptureData;
use Tests\TestCase;

class MobileSalesQuotationTest extends TestCase
{
    public function test_hospital_wizard_keeps_mixtures_patient_prices_and_assignments_without_issuing_a_quote(): void
    {
        (require database_path('migrations/2026_10_02_000004_create_hospital_quotation_requests_table.php'))->up();
        (require database_path('migrations/2026_10_02_000006_add_capture_data_to_hospital_quotation_requests.php'))->up();
        Hospital::findOrFail(1)->salespeople()->syncWithoutDetaching([$this->seller->id]);
        $client = User::findOrFail(2);
        $client->forceFill(['hospital_id' => 1, 'is_active' => true])->save();
        $client->syncRoles(Role::findOrCreate('Cliente', 'web'));
        $this->actingAs($client)->getJson('/api/mobile/hospital/quotation-wizard/catalog?category=oncologicos')->assertOk()->assertJsonPath('hospital.id', 1);
        $body = $this->payload();
        $body['mixture_count'] = 2;
        $body['items'] = [['presentation_id' => 1, 'concentration' => 50, 'mixture_number' => 1], ['presentation_id' => 1, 'concentration' => 150, 'mixture_number' => 2]];
        $body['requirements'] = [['mixture_number' => 1, 'medicine' => 'Medicamento de prueba', 'concentration' => 50], ['mixture_number' => 2, 'medicine' => 'Medicamento de prueba', 'concentration' => 150]];
        $body['patient_name'] = 'Maria'; $body['patient_paternal_surname'] = 'Perez'; $body['patient_maternal_surname'] = 'Lopez'; $body['patient_platform_id'] = 'PAC-1';
        $before = RequestQuotation::count();
        $preview = $this->postJson('/api/mobile/hospital/quotation-wizard/preview', $body)->assertOk()->assertJsonPath('pricing_snapshot.mixtures', 2);
        $body['pricing_token'] = $preview->json('pricing_token');
        $created = $this->postJson('/api/mobile/hospital/quotation-wizard/requests', $body)->assertCreated()->assertJsonPath('patient_name', 'Maria Perez Lopez');
        $id = $created->json('id');
        $this->postJson('/api/mobile/hospital/quotation-wizard/requests', $body)->assertOk()->assertJsonPath('id', $id);
        $this->assertSame($before, RequestQuotation::count());
        $this->assertSame(1, \App\Models\HospitalQuotationRequest::count());
        $created->assertJsonPath('capture_data.clinical_data.items.1.mixture_number', 2)->assertJsonPath('capture_data.clinical_data.patient_platform_id', 'PAC-1');
        $this->assertSame(1, $this->seller->notifications()->where('data->quotation_request_id', $id)->count());
        $this->post('/api/mobile/hospital/quotation-wizard/requests/'.$id.'/attachment', ['file' => \Illuminate\Http\UploadedFile::fake()->image('receta.jpg')])->assertOk()->assertJsonPath('has_attachment', true);
        $this->actingAs($this->seller)->getJson('/api/mobile/sales/hospital-requests')->assertOk()->assertJsonPath('data.0.id', $id);
        $this->actingAs($client); $bad = $body; $bad['hospital_id'] = 2;
        $this->postJson('/api/mobile/hospital/quotation-wizard/preview', $bad)->assertForbidden();
        $bad = $body; $bad['items'][0]['unit_price_override'] = 1;
        $this->postJson('/api/mobile/hospital/quotation-wizard/preview', $bad)->assertUnprocessable();
        $bad = $body; $bad['institution_id'] = 2;
        $this->postJson('/api/mobile/hospital/quotation-wizard/preview', $bad)->assertUnprocessable();
        $bad = $body; $bad['submission_key'] = (string) Str::uuid(); $bad['pricing_token'] = str_repeat('0', 64);
        $this->postJson('/api/mobile/hospital/quotation-wizard/requests', $bad)->assertUnprocessable();
    }

    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->seller = RequestQuotationCaptureData::seed();
        Schema::table('hospitals', fn (Blueprint $table) => $table->unsignedBigInteger('antibiotic_medicine_list_id')->nullable());
        DB::table('medicine_lists')->insert(['id' => 2, 'name' => 'Lista antibioticos', 'catalog_category' => 'antibioticos', 'charge_by' => 'mg']);
        DB::table('medicines_catalog')->insert(['id' => 2, 'denominacion' => 'Antibiotico de prueba', 'state' => 1, 'catalog_category' => 'antibioticos']);
        DB::table('medicine_presentations')->insert(['id' => 3, 'catalog_id' => 2, 'presentacion' => 'Frasco 500 mg', 'contenido_valor' => 500, 'contenido_unidad' => 'mg', 'is_available' => 1]);
        DB::table('medicine_list_presentation')->insert(['medicine_list_id' => 2, 'medicine_presentation_id' => 3, 'precio' => 250, 'charge_by' => 'mg']);
        DB::table('hospitals')->where('id', 1)->update(['antibiotic_medicine_list_id' => 2]);
        Schema::table('hospitals', fn (Blueprint $table) => $table->string('short_name')->nullable());
        Schema::table('personnel_profiles', fn (Blueprint $table) => $table->string('employment_status')->default('hired'));
        $this->seller->syncRoles(Role::findOrCreate('Vendedor', 'web'));
        $this->seller->syncPermissions([]);
        PersonnelProfile::create(['user_id' => $this->seller->id, 'positions' => ['Vendedor', PersonnelProfile::POSITION_MOBILE], 'employment_status' => 'hired']);
        $this->seller = $this->seller->fresh();
        Hospital::findOrFail(1)->salespeople()->syncWithoutDetaching([$this->seller->id]);
        $this->actingAs($this->seller);
    }

    private function payload(string $category = 'oncologicos'): array
    {
        return ['flow' => 'commercial', 'category' => $category, 'hospital_id' => 1, 'institution_id' => 1,
            'no_commercial_relationship' => false, 'submission_key' => (string) Str::uuid(), 'action' => 'save',
            'mixture_count' => 1, 'items' => [['presentation_id' => $category === 'antibioticos' ? 3 : 1, 'concentration' => 50, 'mixture_number' => 1]]];
    }

    public function test_mobile_and_web_share_pricing_and_records_and_retries_do_not_duplicate(): void
    {
        foreach (['oncologicos' => 131, 'nutricionales' => 125, 'antibioticos' => 25] as $category => $total) {
            $data = $this->payload($category);
            $review = $this->postJson('/api/mobile/sales/quotations/preview', $data)->assertOk()->assertJsonPath('pricing_snapshot.total', $total);
            $data['pricing_token'] = $review->json('pricing_token');
            $saved = $this->postJson('/api/mobile/sales/quotations', $data)->assertOk();
            $id = $saved->json('id');
            $this->assertSame($this->seller->id, RequestQuotation::findOrFail($id)->seller_id);
            $before = RequestQuotation::count();
            $this->postJson('/api/mobile/sales/quotations', $data)->assertOk()->assertJsonPath('id', $id);
            $this->assertSame($before, RequestQuotation::count());
            $this->getJson(route('admin.solicitudes.cotizacion.show', $id))->assertOk()->assertJsonPath('pricing_snapshot.total', $total);
            $this->getJson('/api/mobile/sales/quotations/'.$id)->assertOk()->assertJsonPath('editable', true);
            $data['action'] = 'send';
            $this->putJson('/api/mobile/sales/quotations/'.$id, $data)->assertOk()->assertJsonPath('status', 'enviada');
            $this->getJson('/api/mobile/sales/quotations')->assertOk()->assertJsonFragment(['folio' => $saved->json('folio')]);
            $this->putJson('/api/mobile/sales/quotations/'.$id, $data)->assertForbidden();
            $data['submission_key'] = (string) Str::uuid();
            $web = $this->postJson(route('admin.solicitudes.cotizacion.store'), $data)->assertOk();
            $this->getJson('/api/mobile/sales/quotations/'.$web->json('id'))->assertOk()->assertJsonPath('folio', $web->json('folio'));
        }
    }

    public function test_mobile_cannot_override_hospital_prices_and_requires_current_review(): void
    {
        $data = $this->payload();
        $forged = $data; $forged['items'][0]['unit_price_override'] = 0;
        $this->postJson('/api/mobile/sales/quotations/preview', $forged)->assertUnprocessable();
        $this->postJson('/api/mobile/sales/quotations/preview', array_replace($data, ['no_commercial_relationship' => true]))->assertUnprocessable();
        $review = $this->postJson('/api/mobile/sales/quotations/preview', $data)->assertOk();
        $data['pricing_token'] = $review->json('pricing_token');
        DB::table('medicine_list_presentation')->where('medicine_presentation_id', 1)->update(['precio' => 300]);
        $this->postJson('/api/mobile/sales/quotations', $data)->assertUnprocessable()->assertJsonValidationErrors('pricing_token');
    }

    public function test_sales_endpoints_reject_inactive_personnel_and_other_users(): void
    {
        $this->getJson('/api/mobile/sales/quotations/5')->assertForbidden();
        $this->seller->personnelProfile->update(['employment_status' => 'inactive']);
        $this->seller->unsetRelation('personnelProfile');
        $this->postJson('/api/mobile/sales/quotations/preview', $this->payload())->assertForbidden();
        $this->getJson('/api/mobile/sales/deliveries')->assertForbidden();
        $this->getJson('/api/mobile/sales/clients')->assertForbidden();
        $this->actingAs(User::findOrFail(2))->postJson('/api/mobile/sales/quotations', $this->payload())->assertForbidden();
    }

    public function test_assigned_hospitals_and_deliveries_are_scoped_to_the_seller(): void
    {
        DB::table('hospitals')->where('id', 2)->update(['is_active' => true]);
        Hospital::findOrFail(1)->salespeople()->syncWithoutDetaching([$this->seller->id]);
        $this->getJson('/api/mobile/sales/clients?assigned=1')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', 1);
        $this->getJson('/api/mobile/sales/clients?assigned=0')->assertOk()->assertJsonCount(1, 'data');
        Schema::table('distribution_delivery_schedules', fn (Blueprint $table) => $table->unsignedBigInteger('distribution_route_id')->nullable());
        Schema::create('distribution_routes', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('distribution_delivery_confirmations', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('distribution_delivery_schedule_id'); $table->dateTime('delivered_at');
        });
        DB::table('distribution_delivery_schedules')->delete();
        foreach ([1, 2] as $hospitalId) {
            DB::table('distribution_delivery_schedules')->insert(['id' => $hospitalId, 'hospital_id' => $hospitalId, 'scheduled_date' => '2026-10-02', 'status' => 'sent']);
        }
        $this->getJson('/api/mobile/sales/deliveries')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'Pendiente');
        DB::table('distribution_delivery_schedules')->where('id', 1)->update(['status' => 'scheduled']);
        $this->getJson('/api/mobile/sales/deliveries')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'Programada');
        DB::table('distribution_delivery_confirmations')->insert(['distribution_delivery_schedule_id' => 1, 'delivered_at' => '2026-10-02 12:00:00']);
        $this->getJson('/api/mobile/sales/deliveries?date=2026-10-02')->assertOk()->assertJsonPath('data.0.status', 'Entregada');
        $this->getJson('/api/mobile/sales/deliveries?date=2026-10-03')->assertOk()->assertJsonCount(0, 'data');
        Schema::table('distribution_delivery_schedules', fn (Blueprint $table) => $table->unsignedBigInteger('warehouse_id')->nullable());
        Schema::create('distribution_route_runs', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('distribution_route_id'); $table->dateTime('started_at'); $table->dateTime('ended_at')->nullable();
        });
        $this->mock(\App\Services\MobileDeliveryTracking::class, function ($mock) {
            $mock->shouldReceive('data')->andReturnUsing(fn ($schedule) => ['id' => $schedule->id, 'hospital_id' => $schedule->hospital_id]);
            $mock->shouldReceive('mixtures')->andReturn([]);
        });
        $this->getJson('/api/mobile/sales/deliveries/tracking')->assertOk()->assertJsonCount(1, 'deliveries.data')->assertJsonPath('deliveries.data.0.id', 1);
        $this->getJson('/api/mobile/sales/deliveries/2/tracking')->assertForbidden();
        Hospital::findOrFail(1)->salespeople()->detach($this->seller->id);
        $this->getJson('/api/mobile/sales/deliveries/tracking')->assertOk()->assertJsonCount(0, 'deliveries.data');
        $client = User::findOrFail(2);
        $client->forceFill(['hospital_id' => 1, 'is_active' => true])->save();
        $client->syncRoles(Role::findOrCreate('Cliente', 'web'));
        $this->actingAs($client)->getJson('/api/mobile/hospital/deliveries')->assertOk()->assertJsonCount(1, 'deliveries.data')->assertJsonPath('deliveries.data.0.id', 1);
        $this->getJson('/api/mobile/hospital/deliveries/2')->assertForbidden();
        DB::table('distribution_delivery_confirmations')->delete();
        $this->getJson('/api/mobile/hospital/deliveries')->assertOk()->assertJsonPath('counts.all', 1)->assertJsonPath('counts.in_route', 0);
    }

    public function test_ventas_role_and_administrator_hospital_assignments(): void
    {
        $this->seller->syncRoles(Role::findOrCreate('Ventas', 'web'));
        $this->seller = $this->seller->fresh();
        $this->actingAs($this->seller)->getJson('/api/mobile/sales/clients')->assertOk();
        $this->getJson('/api/mobile/sales/quotations/2')->assertOk()->assertJsonPath('pricing_snapshot.lines', []);
        $manager = User::findOrFail(2);
        $manager->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $this->actingAs($manager)->put(route('admin.sales.hospitals.update', 1), ['seller_ids' => [$this->seller->id]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(Hospital::findOrFail(1)->salespeople->contains($this->seller));
        $this->putJson(route('admin.sales.hospitals.update', 1), ['seller_ids' => [999]])->assertUnprocessable();
        $this->put(route('admin.sales.hospitals.update', 1), [])->assertRedirect();
        $this->assertSame(0, Hospital::findOrFail(1)->salespeople()->count());
    }

    public function test_hospital_requests_are_idempotent_scoped_and_linked_to_the_shared_quotation(): void
    {
        (require database_path('migrations/2026_10_02_000004_create_hospital_quotation_requests_table.php'))->up();
        Hospital::findOrFail(1)->salespeople()->syncWithoutDetaching([$this->seller->id]);
        $client = User::findOrFail(2);
        $client->forceFill(['hospital_id' => 1, 'is_active' => true])->save();
        $client->syncRoles(Role::findOrCreate('Cliente', 'web'));
        $request = ['submission_key' => (string) Str::uuid(), 'category' => 'oncologicos',
            'items' => [['presentation_id' => 1, 'quantity' => 50]], 'observations' => 'Entrega matutina'];
        $this->actingAs($client);
        $this->getJson('/api/mobile/hospital/catalog?category=oncologicos')->assertOk()->assertJsonCount(1, 'products');
        $created = $this->postJson('/api/mobile/hospital/quotation-requests', $request)->assertCreated();
        $id = $created->json('id');
        $this->postJson('/api/mobile/hospital/quotation-requests', $request)->assertOk()->assertJsonPath('id', $id);
        $this->assertSame(1, \App\Models\HospitalQuotationRequest::count());
        $bad = $request; $bad['submission_key'] = (string) Str::uuid(); $bad['items'][0]['presentation_id'] = 2;
        $this->postJson('/api/mobile/hospital/quotation-requests', $bad)->assertUnprocessable();
        $this->actingAs($this->seller)->getJson('/api/mobile/sales/hospital-requests')->assertOk()->assertJsonPath('data.0.id', $id);
        $data = $this->payload(); $data['hospital_request_id'] = $id;
        $review = $this->postJson('/api/mobile/sales/quotations/preview', $data)->assertOk();
        $data['pricing_token'] = $review->json('pricing_token');
        $saved = $this->postJson('/api/mobile/sales/quotations', $data)->assertOk();
        $quotationId = $saved->json('id');
        $this->assertSame($quotationId, \App\Models\HospitalQuotationRequest::findOrFail($id)->quotation_id);
        $this->actingAs($client)->getJson('/api/mobile/hospital/quotations/'.$quotationId)->assertForbidden();
        $data['action'] = 'send';
        $this->actingAs($this->seller)->putJson('/api/mobile/sales/quotations/'.$quotationId, $data)->assertOk();
        $this->actingAs($client)->getJson('/api/mobile/hospital/quotations/'.$quotationId)->assertOk()->assertJsonPath('folio', $saved->json('folio'));
        $client->forceFill(['hospital_id' => 2])->save();
        $this->getJson('/api/mobile/hospital/quotation-requests')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/mobile/hospital/quotations/'.$quotationId)->assertForbidden();
    }

    public function test_delivery_tracking_filters_access_and_uses_only_the_correct_route_date(): void
    {
        Schema::table('distribution_delivery_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('distribution_route_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->dateTime('sent_at')->nullable();
        });
        Schema::create('distribution_routes', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('code'); });
        (require database_path('migrations/2026_09_02_000001_create_distribution_mobile_tracking_tables.php'))->up();
        Hospital::findOrFail(1)->salespeople()->syncWithoutDetaching([$this->seller->id]);
        DB::table('distribution_routes')->insert(['id' => 1, 'name' => 'Ruta de prueba', 'code' => 'R-01']);
        DB::table('distribution_delivery_schedules')->delete();
        foreach ([1 => [1, '2026-10-02'], 2 => [2, '2026-10-02'], 3 => [1, '2026-10-01']] as $id => [$hospital, $date]) {
            DB::table('distribution_delivery_schedules')->insert(['id' => $id, 'hospital_id' => $hospital, 'scheduled_date' => $date, 'status' => 'sent', 'distribution_route_id' => 1]);
        }
        DB::table('distribution_route_runs')->insert(['id' => 1, 'distribution_route_id' => 1, 'messenger_id' => $this->seller->id, 'started_at' => '2026-10-02 09:00:00']);
        DB::table('distribution_location_updates')->insert(['distribution_route_run_id' => 1, 'latitude' => 19.43, 'longitude' => -99.13, 'recorded_at' => '2026-10-02 09:05:00']);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-02 09:06:00'));
        $this->getJson('/api/mobile/sales/deliveries/tracking?state=in_route')->assertOk()->assertJsonPath('counts.all', 2)->assertJsonPath('counts.in_route', 1)->assertJsonCount(1, 'deliveries.data');
        $this->getJson('/api/mobile/sales/deliveries/1/tracking')->assertOk()->assertJsonPath('state', 'in_route')->assertJsonPath('location.stale', false)->assertJsonCount(1, 'path');
        $this->getJson('/api/mobile/sales/deliveries/2/tracking')->assertForbidden();
        $this->getJson('/api/mobile/sales/deliveries/3/tracking')->assertOk()->assertJsonPath('location', null)->assertJsonPath('state', 'pending');
        $this->travelTo(\Carbon\Carbon::parse('2026-10-02 09:20:00'));
        $this->getJson('/api/mobile/sales/deliveries/1/tracking')->assertOk()->assertJsonPath('location.stale', true);
        DB::table('distribution_delivery_confirmations')->insert(['distribution_delivery_schedule_id' => 1, 'distribution_route_run_id' => 1, 'messenger_id' => $this->seller->id, 'delivered_at' => '2026-10-02 09:20:00', 'notes' => 'Recibida']);
        $this->getJson('/api/mobile/sales/deliveries/tracking?state=delivered&date=2026-10-02')->assertOk()->assertJsonPath('counts.delivered', 1)->assertJsonPath('counts.in_route', 0)->assertJsonCount(1, 'deliveries.data');
        $this->getJson('/api/mobile/sales/deliveries/1/tracking')->assertOk()->assertJsonPath('state', 'delivered')->assertJsonPath('notes', 'Recibida');
        $client = User::findOrFail(2); $client->forceFill(['hospital_id' => 1, 'is_active' => true])->save(); $client->syncRoles(Role::findOrCreate('Cliente', 'web'));
        $this->actingAs($client)->getJson('/api/mobile/hospital/deliveries/1')->assertOk();
        $this->getJson('/api/mobile/hospital/deliveries/2')->assertForbidden();
        $this->getJson('/api/mobile/sales/deliveries/tracking')->assertForbidden();
        $this->travelBack();
    }

    public function test_hospital_login_uses_existing_credentials_and_respects_disabled_access(): void
    {
        Schema::table('personnel_profiles', function (Blueprint $table) {
            $table->string('phone')->nullable(); $table->string('personal_email')->nullable();
            $table->string('department')->nullable(); $table->date('hire_date')->nullable();
        });
        (require database_path('migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        $client = User::findOrFail(2);
        $client->forceFill(['hospital_id' => 1, 'username' => 'hospital-test', 'password' => \Illuminate\Support\Facades\Hash::make('testing-only'), 'is_active' => true])->save();
        $client->syncRoles(Role::findOrCreate('Cliente', 'web'));
        $this->postJson('/api/mobile/auth/login', ['username' => 'hospital-test', 'password' => 'testing-only'])->assertOk()->assertJsonPath('user.module', 'hospital')->assertJsonPath('user.hospital.id', 1);
        $this->postJson('/api/mobile/auth/login', ['username' => 'hospital-test', 'password' => 'incorrect'])->assertUnprocessable();
        Hospital::findOrFail(1)->update(['access_is_active' => false]);
        $this->postJson('/api/mobile/auth/login', ['username' => 'hospital-test', 'password' => 'testing-only'])->assertUnprocessable();
        $this->actingAs($client->fresh())->getJson('/api/mobile/hospital/orders')->assertForbidden();
        $this->getJson('/api/mobile/hospital/deliveries')->assertForbidden();
    }

    public function test_prescription_attachment_is_private_and_request_retries_do_not_duplicate_files(): void
    {
        (require database_path('migrations/2026_10_02_000004_create_hospital_quotation_requests_table.php'))->up();
        \Illuminate\Support\Facades\Storage::fake('local');
        Hospital::findOrFail(1)->salespeople()->syncWithoutDetaching([$this->seller->id]);
        $client = User::findOrFail(2); $client->forceFill(['hospital_id' => 1, 'is_active' => true])->save(); $client->syncRoles(Role::findOrCreate('Cliente', 'web'));
        $data = ['submission_key' => (string) Str::uuid(), 'category' => 'oncologicos', 'items' => '[]', 'attachment' => \Illuminate\Http\UploadedFile::fake()->image('receta.jpg')];
        $created = $this->actingAs($client)->post('/api/mobile/hospital/quotation-requests', $data, ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('has_attachment', true);
        $this->post('/api/mobile/hospital/quotation-requests', $data, ['Accept' => 'application/json'])->assertOk();
        $this->assertCount(1, \Illuminate\Support\Facades\Storage::disk('local')->allFiles('hospital-quotation-requests'));
        $this->get('/api/mobile/sales/hospital-requests/'.$created->json('id').'/attachment')->assertForbidden();
        $this->actingAs($this->seller)->get('/api/mobile/sales/hospital-requests/'.$created->json('id').'/attachment')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        Hospital::findOrFail(1)->salespeople()->detach($this->seller->id);
        // Explicitly assigned requests remain owned by their seller; other salespeople cannot retrieve them.
        $other = User::forceCreate(['name' => 'Otro vendedor', 'username' => 'otro-test', 'is_active' => true]);
        $other->assignRole(Role::findOrCreate('Ventas', 'web'));
        PersonnelProfile::create(['user_id' => $other->id, 'positions' => [PersonnelProfile::POSITION_MOBILE], 'employment_status' => 'hired']);
        $this->actingAs($other)->get('/api/mobile/sales/hospital-requests/'.$created->json('id').'/attachment')->assertForbidden();
    }

    public function test_sales_cannot_create_or_send_quotes_after_hospital_assignment_is_removed(): void
    {
        $data = $this->payload();
        $review = $this->postJson('/api/mobile/sales/quotations/preview', $data)->assertOk();
        $data['pricing_token'] = $review->json('pricing_token');
        $saved = $this->postJson('/api/mobile/sales/quotations', $data)->assertOk();
        Hospital::findOrFail(1)->salespeople()->detach($this->seller->id);
        $this->getJson('/api/mobile/sales/clients?assigned=0')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/mobile/sales/catalog?flow=commercial&category=oncologicos&hospital_id=1')->assertForbidden();
        $this->postJson('/api/mobile/sales/quotations/preview', $data)->assertForbidden();
        $data['submission_key'] = (string) Str::uuid();
        $this->postJson('/api/mobile/sales/quotations', $data)->assertForbidden();
        $data['action'] = 'send';
        $this->putJson('/api/mobile/sales/quotations/'.$saved->json('id'), $data)->assertForbidden();
        $this->postJson('/api/mobile/sales/quotations/'.$saved->json('id').'/email', ['email' => 'test@example.com'])->assertForbidden();
        $this->postJson(route('admin.solicitudes.cotizacion.store'), $data)->assertForbidden();
        $this->assertSame('borrador', RequestQuotation::findOrFail($saved->json('id'))->status);
    }

    public function test_web_and_mobile_workflow_routes_only_to_related_hospital_seller_and_central(): void
    {
        $client = User::findOrFail(2); $client->forceFill(['hospital_id' => 1, 'is_active' => true])->save(); $client->syncRoles(Role::findOrCreate('Cliente', 'web'));
        $central = User::forceCreate(['name' => 'Central A', 'username' => 'central-a', 'is_active' => true]);
        $other = User::forceCreate(['name' => 'Central B', 'username' => 'central-b', 'is_active' => true]);
        foreach ([[$central, 1], [$other, 2]] as [$user, $laboratory]) {
            PersonnelProfile::create(['user_id' => $user->id, 'laboratory_id' => $laboratory, 'employment_status' => 'hired']);
            foreach (['oncologicos_solicitudes_index', 'oncologicos_solicitudes_update'] as $permission) {
                $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($permission, 'web'));
            }
        }
        $data = $this->payload(); $data['action'] = 'send'; $data['patient_name'] = 'Paciente conectado';
        $review = $this->postJson('/api/mobile/sales/quotations/preview', $data)->assertOk();
        $data['pricing_token'] = $review->json('pricing_token');
        $saved = $this->postJson('/api/mobile/sales/quotations', $data)->assertOk(); $id = $saved->json('id');
        foreach ([$client, $central] as $recipient) $this->assertSame(1, $recipient->notifications()->where('data->quotation_id', $id)->count());
        $this->assertSame(0, $this->seller->notifications()->where('data->quotation_id', $id)->count());
        $this->assertSame(0, $other->notifications()->where('data->quotation_id', $id)->count());
        $this->actingAs($client)->getJson('/api/mobile/quotations/'.$id.'/workflow')->assertOk()->assertJsonPath('status', 'enviada')->assertJsonPath('can_authorize', false);
        $this->postJson('/api/mobile/quotations/'.$id.'/authorize')->assertForbidden();
        $this->actingAs($other)->getJson('/api/mobile/quotations/'.$id.'/workflow')->assertForbidden();
        $this->postJson('/api/mobile/quotations/'.$id.'/authorize')->assertForbidden();
        $this->actingAs($central)->postJson(route('admin.solicitudes.cotizacion.authorize', $id))->assertOk()->assertJsonPath('status', 'autorizada');
        $this->postJson('/api/mobile/quotations/'.$id.'/authorize')->assertOk();
        foreach ([$client, $central] as $recipient) $this->assertSame(2, $recipient->notifications()->where('data->quotation_id', $id)->count());
        $this->assertSame(1, $this->seller->notifications()->where('data->quotation_id', $id)->count());
        $this->actingAs($client)->getJson('/api/mobile/hospital/quotations/'.$id)->assertOk()->assertJsonPath('status', 'Autorizada');
        $events = $this->getJson('/api/mobile/quotations/'.$id.'/workflow')->assertOk()->assertJsonCount(2, 'events');
        $this->assertSame('enviada', $events->json('events.1.previous_status'));
        RequestQuotation::findOrFail($id)->forceFill(['status' => 'preparacion', 'request_id' => 999])->save();
        $this->getJson('/api/mobile/quotations/'.$id.'/workflow')->assertOk()->assertJsonPath('status', 'preparacion')->assertJsonPath('request_id', 999);
        $this->actingAs($this->seller)->getJson('/api/mobile/sales/quotations?search='.$saved->json('folio').'&tab=history')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.patient', 'Paciente conectado');
        $this->getJson('/api/mobile/sales/quotations?search=Paciente%20conectado&hospital_id=1&institution_id=1&status=preparacion')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/mobile/sales/quotations?search=Paciente%20conectado&tab=new')->assertOk()->assertJsonCount(0, 'data');
    }
}
