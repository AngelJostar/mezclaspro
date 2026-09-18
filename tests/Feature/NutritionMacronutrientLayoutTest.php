<?php

namespace Tests\Feature;

use App\Models\Nutricionales\Category;
use App\Models\Nutricionales\Input;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NutritionMacronutrientLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_persist_the_nutrition_form_layout(): void
    {
        $aminoCategory = Category::query()->forceCreate(['id' => 1, 'name' => 'Aminoácidos']);
        $carbCategory = Category::query()->forceCreate(['id' => 2, 'name' => 'Carbohidratos']);
        $lipidCategory = Category::query()->forceCreate(['id' => 3, 'name' => 'Lípidos']);
        $electrolyteCategory = Category::query()->forceCreate(['id' => 4, 'name' => 'Electrolitos']);
        $additiveCategory = Category::query()->forceCreate(['id' => 5, 'name' => 'Aditivos']);

        $amino = $this->input('Aminoácidos', $aminoCategory->id, 10);
        $carb = $this->input('Glucosa', $carbCategory->id, 20);
        $lipid = $this->input('Lípidos', $lipidCategory->id, 30);
        $sodium = $this->input('Sodio', $electrolyteCategory->id, 10);
        $potassium = $this->input('Potasio', $electrolyteCategory->id, 20);
        $vitamins = $this->input('Multivitamínico', $additiveCategory->id, 10);
        $zinc = $this->input('Zinc', $additiveCategory->id, 20);

        $sections = [
            'macronutrients' => [
                'left' => [$lipid->id, $amino->id],
                'right' => [$carb->id],
            ],
            'electrolytes' => [
                'left' => [$potassium->id],
                'right' => [$sodium->id],
            ],
            'additives' => [
                'left' => [$zinc->id, $vitamins->id],
                'right' => [],
            ],
        ];

        $adminRole = Role::query()->create(['name' => 'Admin', 'guard_name' => 'web']);
        $regularAdmin = $this->user('regular.admin', $adminRole);
        $this->actingAs($regularAdmin)
            ->postJson(route('admin.nutricionales.inputs.reorder-form-layout'), compact('sections'))
            ->assertForbidden();

        $superRole = Role::query()->create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin = $this->user('super.admin.layout', $superRole);
        $this->actingAs($superAdmin)
            ->postJson(route('admin.nutricionales.inputs.reorder-form-layout'), compact('sections'))
            ->assertOk()
            ->assertJsonPath('message', 'Acomodo del formulario nutricional actualizado.');

        $this->assertSame(10, $lipid->fresh()->orden_enum);
        $this->assertSame(20, $amino->fresh()->orden_enum);
        $this->assertSame(10, $carb->fresh()->orden_enum);
        $this->assertSame(1, $lipid->fresh()->layout_column);
        $this->assertSame(2, $carb->fresh()->layout_column);
        $this->assertSame(10, $potassium->fresh()->orden_enum);
        $this->assertSame(10, $sodium->fresh()->orden_enum);
        $this->assertSame(10, $zinc->fresh()->orden_enum);
        $this->assertSame(20, $vitamins->fresh()->orden_enum);
        $this->assertSame(1, $vitamins->fresh()->layout_column);
    }

    private function input(string $description, int $categoryId, int $order): Input
    {
        return Input::query()->create([
            'description' => $description,
            'unidad' => 'mL',
            'is_active' => true,
            'tipo_input' => 'ambos',
            'orden_enum' => $order,
            'category_id' => $categoryId,
            'mult' => 1,
            'div' => 1,
        ]);
    }

    private function user(string $username, Role $role): User
    {
        $user = User::query()->create([
            'name' => 'Usuario',
            'lastname' => 'Prueba',
            'username' => $username,
            'password' => Hash::make('secret'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
