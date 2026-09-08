<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\InstitucionController;
use App\Models\DistributionRoute;
use App\Models\Hospital;
use App\Models\Institucion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstitutionHospitalMapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            '2024_01_10_035338_create_hospitals_table.php',
            '2026_02_06_201859_create_clientes_table.php',
            '2026_02_06_214504_create_cliente_hospital_table.php',
            '2026_08_10_000001_add_profile_fields_to_hospitals_table.php',
            '2026_08_26_000008_add_coordinates_to_hospitals_table.php',
            '2024_04_11_013606_create_permission_tables.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->nullable();
            $table->string('username')->nullable();
            $table->text('credential_password')->nullable();
            $table->text('training_credential_password')->nullable();
            $table->boolean('is_active')->default(true);
        });

        (require database_path('migrations/2026_08_20_000010_create_distribution_routes_tables.php'))->up();
    }

    public function test_map_includes_all_linked_hospitals_regardless_of_filters_or_pagination(): void
    {
        $institution = Institucion::create(['nombre' => 'Institucion de prueba']);
        $hospitals = Hospital::factory()->count(27)->create([
            'adress' => 'Direccion de prueba', 'is_active' => true,
            'latitude' => 19.42, 'longitude' => -99.16,
        ]);
        $hospitals->last()->update(['is_active' => false]);
        $institution->hospitals()->attach($hospitals->modelKeys());
        $otherInstitution = Institucion::create(['nombre' => 'Otra institucion']);
        $unrelated = Hospital::factory()->create(['adress' => 'Direccion ajena']);
        $otherInstitution->hospitals()->attach($unrelated);

        $data = $this->pageData($institution);
        $this->assertCount(25, $data['hospitals']);
        $this->assertCount(27, $data['mapHospitals']);
        $this->assertSame(27, $data['totalHospitals']);
        $this->assertEqualsCanonicalizing($hospitals->modelKeys(), $data['mapHospitals']->pluck('id')->all());
        $this->assertFalse($data['mapHospitals']->contains('id', $unrelated->id));
        $this->assertCount(1, $data['mapHospitals']->where('is_active', false));

        $filtered = $this->pageData($institution, ['search' => 'Ninguna coincidencia', 'status' => 'active']);
        $this->assertCount(0, $filtered['hospitals']);
        $this->assertSame($data['mapHospitals']->all(), $filtered['mapHospitals']->all());
        $this->assertSame([
            'id', 'name', 'address', 'is_active', 'has_assigned_route', 'latitude', 'longitude', 'estimated',
        ], array_keys($data['mapHospitals']->first()));
    }

    public function test_map_route_assignment_is_independent_of_hospital_status_and_tracks_catalog_changes(): void
    {
        $institution = Institucion::create(['nombre' => 'Institucion con rutas']);
        $hospitals = Hospital::factory()->count(4)->create([
            'adress' => 'Direccion', 'latitude' => 19.42, 'longitude' => -99.16,
            'is_active' => true,
        ]);
        $hospitals[2]->update(['is_active' => false]);
        $hospitals[3]->update(['is_active' => false]);
        $institution->hospitals()->attach($hospitals->modelKeys());
        $route = DistributionRoute::create([
            'name' => 'Ruta de prueba', 'code' => 'MAP-001',
            'schedule_start' => '08:00', 'schedule_end' => '17:00',
            'status' => DistributionRoute::STATUS_COMPLETED,
        ]);
        $route->hospitals()->attach([$hospitals[0]->id, $hospitals[2]->id]);

        $data = $this->pageData($institution, ['status' => 'active']);
        $mapped = $data['mapHospitals']->keyBy('id');
        foreach ($hospitals as $index => $hospital) {
            $this->assertSame($index < 2, $mapped[$hospital->id]['is_active']);
            $this->assertSame($index % 2 === 0, $mapped[$hospital->id]['has_assigned_route']);
        }
        $this->assertCount(2, $data['hospitals']);
        $this->assertCount(4, $mapped);
        $html = view('admin.instituciones.partials.hospital-map', $data)->render();
        $this->assertStringContainsString('data-map-color-mode', $html);
        $this->assertStringContainsString('value="status">Activos e inactivos', $html);
        $this->assertStringContainsString('value="route">Ruta asignada', $html);
        $this->assertStringContainsString('data-map-legend="route" hidden', $html);

        $route->hospitals()->detach($hospitals[0]->id);
        $mapped = $this->pageData($institution)['mapHospitals']->keyBy('id');
        $this->assertFalse($mapped[$hospitals[0]->id]['has_assigned_route']);
        $this->assertTrue($mapped[$hospitals[2]->id]['has_assigned_route']);
    }

    public function test_map_distinguishes_exact_estimated_and_unknown_locations(): void
    {
        $institution = Institucion::create(['nombre' => 'Institucion de prueba']);
        $exact = Hospital::factory()->create([
            'name' => 'Hospital exacto', 'adress' => 'Direccion exacta',
            'latitude' => 19.42, 'longitude' => -99.16,
        ]);
        $approximate = Hospital::factory()->create(['name' => 'Hospital local', 'adress' => 'Cuernavaca']);
        $unknown = Hospital::factory()->create(['name' => 'Hospital sin ubicacion', 'adress' => 'Por registrar']);
        $institution->hospitals()->attach([$exact->id, $approximate->id, $unknown->id]);

        $data = $this->pageData($institution);
        $mapped = $data['mapHospitals']->keyBy('id');
        $this->assertSame(19.42, $mapped[$exact->id]['latitude']);
        $this->assertFalse($mapped[$exact->id]['estimated']);
        $this->assertTrue($mapped[$approximate->id]['estimated']);
        $this->assertNotNull($mapped[$approximate->id]['latitude']);
        $this->assertNull($mapped[$unknown->id]['latitude']);
        $this->assertNull($mapped[$unknown->id]['longitude']);

        $html = view('admin.instituciones.partials.hospital-map', $data)->render();
        $this->assertStringContainsString('2 de 3 hospitales ubicados', $html);
        $this->assertStringContainsString('Coordenadas pendientes', $html);
    }

    public function test_empty_institution_and_hostile_names_render_safely(): void
    {
        $institution = Institucion::create(['nombre' => 'Institucion vacia']);
        $data = $this->pageData($institution);
        $this->assertCount(0, $data['mapHospitals']);
        $html = view('admin.instituciones.partials.hospital-map', $data)->render();
        $this->assertStringContainsString('no tiene hospitales registrados', $html);

        $hospital = Hospital::factory()->create([
            'name' => '</script><script>alert(1)</script>', 'adress' => 'Direccion',
            'latitude' => 19.42, 'longitude' => -99.16,
        ]);
        $institution->hospitals()->attach($hospital);
        $html = view('admin.instituciones.partials.hospital-map', $this->pageData($institution))->render();
        $this->assertStringNotContainsString('</script><script>alert(1)</script>', $html);
        $this->assertStringContainsString('data-map-hospitals', $html);
    }

    private function pageData(Institucion $institution, array $query = []): array
    {
        return app(InstitucionController::class)
            ->hospitales(Request::create('/instituciones/'.$institution->id.'/hospitals', 'GET', $query), $institution)
            ->getData();
    }
}
