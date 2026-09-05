<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HospitalInstitutionChangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            '2024_01_10_035338_create_hospitals_table.php',
            '2026_02_06_201859_create_clientes_table.php',
            '2026_02_06_214504_create_cliente_hospital_table.php',
            '2024_04_11_013606_create_permission_tables.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username');
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('hospital_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_change_replaces_existing_links_without_affecting_other_hospitals(): void
    {
        $original = Institucion::create(['nombre' => 'Institucion original']);
        $additional = Institucion::create(['nombre' => 'Institucion adicional']);
        $destination = Institucion::create(['nombre' => 'Institucion destino']);
        $hospital = $this->createHospital();
        $otherHospital = $this->createHospital();
        $hospital->instituciones()->attach([$original->id, $additional->id]);
        $otherHospital->instituciones()->attach($original);

        $this->actingAs($this->createManager())
            ->patch(route('admin.hospitals.change-institution', $hospital), [
                'new_institution_id' => $destination->id,
                'institution_filter' => $original->id,
            ])
            ->assertRedirect(route('admin.hospitals.index', ['institution_id' => $original->id]))
            ->assertSessionHas('swal.icon', 'success');

        $this->assertSame([$destination->id], $hospital->instituciones()->get()->modelKeys());
        $this->assertSame([$original->id], $otherHospital->instituciones()->get()->modelKeys());
    }

    public function test_hospital_without_institution_can_be_assigned_and_repeated_submission_does_not_duplicate_it(): void
    {
        $destination = Institucion::create(['nombre' => 'Institucion destino']);
        $hospital = $this->createHospital();
        $this->actingAs($this->createManager());

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->patch(route('admin.hospitals.change-institution', $hospital), [
                'new_institution_id' => $destination->id,
            ])->assertRedirect(route('admin.hospitals.index', ['institution_id' => 'all']));
        }

        $this->assertSame([$destination->id], $hospital->instituciones()->get()->modelKeys());
    }

    public function test_invalid_or_missing_catalog_selection_preserves_current_institution(): void
    {
        $original = Institucion::create(['nombre' => 'Institucion original']);
        $hospital = $this->createHospital();
        $hospital->instituciones()->attach($original);
        $this->actingAs($this->createManager());

        foreach ([999999, '', 'Institucion inexistente', [$original->id]] as $invalidSelection) {
            $this->from(route('admin.hospitals.index'))
                ->patch(route('admin.hospitals.change-institution', $hospital), [
                    'new_institution_id' => $invalidSelection,
                    'institution_change_hospital_id' => $hospital->id,
                ])
                ->assertRedirect(route('admin.hospitals.index'))
                ->assertSessionHasErrorsIn('changeInstitution', 'new_institution_id');

            $this->assertSame([$original->id], $hospital->instituciones()->get()->modelKeys());
        }
    }

    public function test_user_without_hospital_permission_cannot_change_institution(): void
    {
        $hospital = $this->createHospital();
        $destination = Institucion::create(['nombre' => 'Institucion destino']);
        $user = $this->createUser();

        $this->actingAs($user)
            ->patch(route('admin.hospitals.change-institution', $hospital), [
                'new_institution_id' => $destination->id,
            ])->assertForbidden();

        $this->assertSame([], $hospital->instituciones()->get()->modelKeys());
    }

    private function createHospital(): Hospital
    {
        return Hospital::factory()->create(['adress' => 'Direccion de prueba', 'is_active' => true]);
    }

    private function createManager(): User
    {
        $permission = Permission::firstOrCreate(['name' => 'hospitales', 'guard_name' => 'web']);
        $manager = $this->createUser();
        $manager->givePermissionTo($permission);

        return $manager;
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Gestor de prueba',
            'username' => 'gestor-prueba',
            'hospital_id' => null,
            'is_active' => true,
        ]);
    }
}
