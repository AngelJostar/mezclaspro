<?php

namespace Tests\Fixtures;

use App\Models\DistributionRoute;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class InstitutionHospitalFields
{
    public static function seed(): array
    {
        $manager = HospitalCredentialList::seed();
        $manager->refresh();
        Schema::table('users', fn (Blueprint $table) => $table->string('lastname')->nullable());
        foreach ([
            '2026_08_10_000001_add_profile_fields_to_hospitals_table.php',
            '2026_08_26_000008_add_coordinates_to_hospitals_table.php',
            '2026_08_20_000010_create_distribution_routes_tables.php',
            '2026_10_02_000002_create_hospital_salesperson_table.php',
            '2026_10_21_000001_add_service_lines_configured_to_hospitals.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::table('hospitals', function (Blueprint $table) {
            $table->unsignedBigInteger('onco_medicine_list_id')->nullable();
            $table->unsignedBigInteger('nutri_medicine_list_id')->nullable();
            $table->boolean('access_is_active')->default(true);
        });
        Role::create(['name' => 'Vendedor', 'guard_name' => 'web']);
        $institution = Institucion::create(['nombre' => 'Institución de prueba']);
        $hospital = HospitalCredentialList::hospital('Hospital de prueba');
        $hospital->update(['onco_medicine_list_id' => 14, 'nutri_medicine_list_id' => 25]);
        $otherHospital = HospitalCredentialList::hospital('Otro hospital');
        $institution->hospitals()->attach($hospital);
        $route = DistributionRoute::create([
            'name' => 'Ruta actual', 'code' => 'R-001', 'schedule_start' => '08:00', 'schedule_end' => '17:00',
        ]);
        $nextRoute = DistributionRoute::create([
            'name' => 'Ruta nueva', 'code' => 'R-002', 'schedule_start' => '08:00', 'schedule_end' => '17:00',
        ]);
        $route->hospitals()->attach($hospital, ['stop_order' => 4, 'completed_at' => '2026-10-20 12:00:00']);
        $nextRoute->hospitals()->attach($otherHospital, ['stop_order' => 7]);
        $seller = User::create(['name' => 'Vendedor actual', 'username' => 'seller.old', 'is_active' => false]);
        $seller->assignRole('Vendedor');
        $nextSeller = User::create(['name' => 'Vendedor nuevo', 'username' => 'seller.new', 'is_active' => true]);
        $nextSeller->assignRole('Vendedor');
        $client = User::create(['name' => 'Cliente de prueba', 'username' => 'client', 'is_active' => true]);
        $client->assignRole('Institucion');
        $hospital->salespeople()->attach($seller);

        return compact('manager', 'institution', 'hospital', 'otherHospital', 'route', 'nextRoute', 'seller', 'nextSeller', 'client');
    }
}
