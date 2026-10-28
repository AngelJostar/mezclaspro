<?php

namespace Tests\Feature;

use App\Models\HospitalConciliationSubmission;
use App\Models\Institucion;
use App\Models\User;
use App\Support\InstitutionPortal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\Fixtures\HospitalToolsData;
use Tests\Fixtures\UnifiedRequestExportData;
use Tests\TestCase;

class InstitutionToolsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        UnifiedRequestExportData::seed();
        HospitalToolsData::addBilling();
        Schema::create('solicitud_inputs', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('solicitud_id');
            $table->unsignedBigInteger('input_id')->nullable(); $table->unsignedBigInteger('presentation_id')->nullable();
        });
        foreach (['users' => 'is_active', 'hospitals' => 'access_is_active', 'clientes' => 'is_active'] as $table => $column) {
            Schema::table($table, fn (Blueprint $schema) => $schema->boolean($column)->default(true));
        }
        foreach (['2026_10_28_120000_add_institution_account_to_users.php', '2024_05_21_124030_create_notifications_table.php',
            '2026_09_15_130000_create_hospital_conciliation_submissions.php', '2026_09_15_150000_add_confirmation_to_hospital_conciliation_submissions.php',
            '2026_10_06_180000_add_direction_to_hospital_conciliation_submissions.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $this->mock(\App\Services\InstitutionBillingPricingService::class, function ($mock) {
            $mock->makePartial();
            $mock->shouldReceive('priceOncoMix')->andReturn(['total_iva_included' => 200.25, 'lines' => collect()]);
            $mock->shouldReceive('priceNutritionRequest')->andReturn(['total_iva_included' => 300.50]);
        });
        $account = User::create(['name' => 'Institución', 'institution_id' => 1, 'is_active' => true]);
        $account->assignRole('Institucion');
        foreach (InstitutionPortal::PERMISSIONS as $permission) $account->givePermissionTo(Permission::findOrCreate($permission));
        Institucion::find(1)->hospitals()->attach(2);
        DB::table('hospitals')->insert(['id' => 3, 'name' => 'Hospital externo']);
        foreach ([1, 2, 3] as $hospital) {
            HospitalConciliationSubmission::create(['submission_key' => (string) Str::uuid(), 'hospital_id' => $hospital,
                'hospital_name' => 'Hospital '.$hospital, 'sender_name' => 'Central', 'direction' => 'sent',
                'filters' => [], 'mixture_count' => 0, 'conciliable_count' => 0, 'snapshot' => []]);
        }
        $this->actingAs($account);
    }

    public function test_conciliations_are_aggregated_and_read_only_with_foreign_downloads_blocked(): void
    {
        $this->get(route('admin.herramientas.index', ['seccion' => 'conciliacion']))->assertOk()
            ->assertViewHas('submissions', fn ($rows) => $rows->total() === 2)
            ->assertDontSee('data-client-request-open data-url=', false);
        foreach ([1, 2] as $id) {
            $this->getJson(route('admin.herramientas.conciliaciones.detail', $id))->assertOk()->assertJsonPath('canRespond', false);
            $this->postJson(route('admin.herramientas.conciliaciones.accept', $id), [])->assertForbidden();
        }
        $this->getJson(route('admin.herramientas.conciliaciones.detail', 3))->assertNotFound();
        $this->get(route('admin.herramientas.conciliaciones.download', 3))->assertNotFound();
        $this->get(route('admin.herramientas.index', ['seccion' => 'conciliacion', 'institution_hospital' => 2]))->assertOk()
            ->assertViewHas('submissions', fn ($rows) => $rows->total() === 1 && (int) $rows->first()->hospital_id === 2);
    }

    public function test_billing_includes_both_related_hospitals_and_empty_membership_returns_no_records(): void
    {
        $this->get(route('admin.herramientas.index', ['seccion' => 'facturacion']))->assertOk()
            ->assertViewHas('billingRecords', fn ($rows) => $rows->total() === 6
                && $rows->getCollection()->pluck('hospital.id')->unique()->count() === 2);
        Institucion::find(1)->hospitals()->detach();
        $this->get(route('admin.herramientas.index', ['seccion' => 'facturacion']))->assertOk()
            ->assertViewHas('billingRecords', fn ($rows) => $rows->isEmpty());
    }
}
