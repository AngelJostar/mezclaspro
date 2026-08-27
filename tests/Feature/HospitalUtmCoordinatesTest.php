<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\HospitalController;
use App\Models\Hospital;
use App\Models\Institucion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HospitalUtmCoordinatesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('razon_social')->nullable();
            $table->string('rfc')->nullable();
            $table->string('telefono')->nullable();
            $table->timestamps();
        });

        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('adress')->nullable();
            $table->string('short_name')->nullable();
            $table->string('internal_key')->nullable()->unique();
            $table->string('unit_type')->nullable();
            $table->string('care_level')->nullable();
            $table->string('rfc')->nullable();
            $table->string('clues')->nullable();
            $table->string('state')->nullable();
            $table->string('municipality')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('street_number')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedTinyInteger('utm_zone')->nullable();
            $table->char('utm_hemisphere', 1)->nullable();
            $table->decimal('utm_easting', 12, 3)->nullable();
            $table->decimal('utm_northing', 13, 3)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_position')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('reception_hours')->nullable();
            $table->json('operation_days')->nullable();
            $table->boolean('service_oncology')->default(false);
            $table->boolean('service_antibiotics')->default(false);
            $table->boolean('service_nutrition')->default(false);
            $table->foreignId('laboratory_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('nutri_medicine_list_id')->nullable();
            $table->foreignId('onco_medicine_list_id')->nullable();
            $table->foreignId('antibiotic_medicine_list_id')->nullable();
            $table->timestamps();
        });

        Schema::create('cliente_hospital', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id');
            $table->foreignId('hospital_id');
            $table->timestamps();
            $table->unique(['cliente_id', 'hospital_id']);
        });
    }

    public function test_a_hospital_can_be_created_with_utm_coordinates(): void
    {
        $institution = Institucion::query()->create([
            'nombre' => 'Institucion de prueba',
        ]);

        $request = Request::create('/hospitals', 'POST', [
            'name_hp' => 'Hospital con coordenadas',
            'internal_key' => 'H-UTM-001',
            'unit_type' => 'Hospital general',
            'state' => 'Ciudad de Mexico',
            'municipality' => 'Cuauhtemoc',
            'postal_code' => '06000',
            'street_number' => 'Plaza de la Constitucion 1',
            'utm_zone' => 14,
            'utm_hemisphere' => 'n',
            'utm_easting' => 486017.3309,
            'utm_northing' => 2148700.2198,
            'contact_name' => 'Responsable de prueba',
            'phone' => '5555555555',
            'email' => 'hospital@example.test',
            'submission' => 'create',
            'is_active' => 1,
        ]);
        $request->setLaravelSession($this->app['session.store']);

        $response = $this->app->make(HospitalController::class)
            ->storeForInstitution($request, $institution);

        $this->assertSame(
            route('admin.instituciones.hospitals', $institution),
            $response->getTargetUrl()
        );

        $hospital = Hospital::query()->where('internal_key', 'H-UTM-001')->firstOrFail();

        $this->assertSame(14, $hospital->utm_zone);
        $this->assertSame('N', $hospital->utm_hemisphere);
        $this->assertEqualsWithDelta(486017.3309, $hospital->utm_easting, 0.001);
        $this->assertEqualsWithDelta(2148700.2198, $hospital->utm_northing, 0.001);
        $this->assertEqualsWithDelta(19.4326, $hospital->latitude, 0.000001);
        $this->assertEqualsWithDelta(-99.1332, $hospital->longitude, 0.000001);

        $this->assertDatabaseHas('cliente_hospital', [
            'cliente_id' => $institution->id,
        ]);
    }

    public function test_partial_utm_coordinates_are_rejected(): void
    {
        $institution = Institucion::query()->create([
            'nombre' => 'Institucion de prueba',
        ]);

        $request = Request::create('/hospitals', 'POST', [
            'name_hp' => 'Hospital incompleto',
            'internal_key' => 'H-UTM-002',
            'unit_type' => 'Hospital general',
            'state' => 'Ciudad de Mexico',
            'municipality' => 'Cuauhtemoc',
            'postal_code' => '06000',
            'street_number' => 'Plaza de la Constitucion 1',
            'utm_zone' => 14,
            'contact_name' => 'Responsable de prueba',
            'phone' => '5555555555',
            'email' => 'hospital@example.test',
            'submission' => 'create',
            'is_active' => 1,
        ]);

        try {
            $this->app->make(HospitalController::class)
                ->storeForInstitution($request, $institution);
            $this->fail('Expected incomplete UTM coordinates to fail validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('utm_hemisphere', $exception->errors());
            $this->assertArrayHasKey('utm_easting', $exception->errors());
            $this->assertArrayHasKey('utm_northing', $exception->errors());
        }

        $this->assertDatabaseMissing('hospitals', [
            'internal_key' => 'H-UTM-002',
        ]);
    }
}
