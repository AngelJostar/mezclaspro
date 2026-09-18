<?php

namespace Tests\Feature;

use App\Models\Nutricionales\Category;
use App\Models\Nutricionales\Input;
use App\Models\Nutricionales\NutritionMedicineCatalog;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NutritionMedicineRequestFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_nutrition_product_creates_its_request_field_automatically(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(
            route('admin.catalogo-listas.products.store', ['category' => 'nutricionales']),
            [
                'generic_description' => 'Selenio',
                'commercial_name' => 'Selenio Demo',
                'concentration' => 10,
                'presentation' => 'Ampolleta 10 mL',
                'stability_hours' => 24,
                'osmolaridad' => 785.5,
                'calorias' => 0.4,
                'densidad' => 1.075,
                'request_field' => [
                    'unidad' => 'mg',
                    'tipo_input' => 'adulto',
                    'orden_enum' => 35,
                    'is_active' => 1,
                    'mult' => 1,
                    'div' => 1,
                ],
            ]
        );

        $response->assertRedirect(route('admin.catalogo-listas.catalog', ['category' => 'nutricionales']));
        $this->assertDatabaseHas('inputs', [
            'description' => 'Selenio',
            'unidad' => 'mg',
            'tipo_input' => 'adulto',
            'orden_enum' => 35,
            'is_active' => 1,
        ]);
        $inputId = Input::query()->where('description', 'Selenio')->value('id');
        $this->assertDatabaseHas('nutrition_medicines_catalog', [
            'denominacion_generica' => 'Selenio',
            'input_id' => $inputId,
            'osmolaridad' => 785.5,
            'calorias' => 0.4,
            'densidad' => 1.075,
        ]);
    }

    public function test_editing_a_nutrition_medicine_synchronizes_its_request_field(): void
    {
        $admin = $this->superAdmin();
        $category = Category::query()->create(['name' => 'Vitaminas']);
        $input = Input::query()->create([
            'description' => 'Nombre incorrecto',
            'unidad' => 'mL',
            'is_active' => false,
            'tipo_input' => 'ambos',
            'orden_enum' => 10,
            'category_id' => $category->id,
            'mult' => 1,
            'div' => 1,
        ]);
        $medicine = NutritionMedicineCatalog::query()->create([
            'denominacion_generica' => 'OLIGOMETALES',
            'category_id' => $category->id,
            'input_id' => $input->id,
            'is_active' => true,
        ]);
        $presentation = NutritionMedicinePresentation::query()->create([
            'nutrition_medicine_catalog_id' => $medicine->id,
            'denominacion_comercial' => 'Nulanza',
            'presentacion' => 'Ampolleta 10ml',
            'presentacion_ml' => 10,
            'stability_hours' => 24,
            'is_available' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.nutricionales.medicines.edit', $medicine))
            ->assertOk()
            ->assertSee('Configuración en la solicitud nutricional')
            ->assertDontSee('Seleccione un input');

        $response = $this->actingAs($admin)->put(route('admin.nutricionales.medicines.update', $medicine), [
            'denominacion_generica' => 'Oligometales',
            'category_id' => $category->id,
            'osmolaridad' => 5,
            'densidad' => 1.025,
            'is_active' => 1,
            'request_field' => [
                'unidad' => 'mL',
                'tipo_input' => 'ambos',
                'orden_enum' => 30,
                'is_active' => 1,
                'mult' => 1,
                'div' => 1,
            ],
            'presentations' => [[
                'id' => $presentation->id,
                'denominacion_comercial' => 'Nulanza',
                'presentacion' => 'Ampolleta 10ml',
                'presentacion_ml' => 10,
                'stability_hours' => 24,
                'is_available' => 1,
            ]],
        ]);

        $response->assertRedirect(route('admin.catalogo-listas.catalog', ['category' => 'nutricionales']));
        $this->assertDatabaseHas('inputs', [
            'id' => $input->id,
            'description' => 'Oligometales',
            'is_active' => 1,
            'orden_enum' => 30,
        ]);
        $this->assertDatabaseHas('nutrition_medicine_presentations', [
            'id' => $presentation->id,
            'nutrition_medicine_catalog_id' => $medicine->id,
            'denominacion_comercial' => 'Nulanza',
        ]);
    }

    public function test_legacy_nutrition_catalog_pages_redirect_to_the_unified_catalog(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.nutricionales.medicines.index'))
            ->assertRedirect(route('admin.catalogo-listas.catalog', ['category' => 'nutricionales']));

        $this->actingAs($admin)
            ->get(route('admin.nutricionales.medicines.create'))
            ->assertRedirect(route('admin.catalogo-listas.products.create', ['category' => 'nutricionales']));
    }

    private function superAdmin(): User
    {
        $permission = Permission::query()->create(['name' => 'medicamentos_nutricionales', 'guard_name' => 'web']);
        $role = Role::query()->create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $user = User::query()->create([
            'name' => 'Administrador',
            'lastname' => 'Local',
            'username' => 'nutrition.admin',
            'password' => Hash::make('secret'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
