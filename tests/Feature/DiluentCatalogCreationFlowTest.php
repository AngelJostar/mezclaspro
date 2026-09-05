<?php

namespace Tests\Feature;

use App\Models\Oncologicos\Diluent;
use App\Models\Oncologicos\DiluentCatalogPresentation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DiluentCatalogCreationFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_an_existing_generic_can_receive_a_new_presentation(): void
    {
        $this->withoutMiddleware();
        $user = User::factory()->create(['hospital_id' => null]);
        $diluent = Diluent::create(['denominacion_generica' => 'DILUYENTE DE PRUEBA']);

        $this->actingAs($user)->post(route('admin.oncologicos.diluents.store'), [
            'generic_mode' => 'existing',
            'diluent_id' => $diluent->id,
            'catalog_presentation' => [
                'presentation' => 'Bolsa 500 mL',
                'commercial_name' => 'Marca de prueba',
                'manufacturer' => 'CMP',
                'volume_ml' => 500,
            ],
        ])->assertRedirect(route('admin.catalogo-listas.catalog', 'diluyentes'));

        $this->assertSame(1, Diluent::query()->where('denominacion_generica', 'DILUYENTE DE PRUEBA')->count());
        $this->assertDatabaseHas('diluent_catalog_presentations', [
            'diluent_id' => $diluent->id,
            'presentation' => 'Bolsa 500 mL',
        ]);
    }

    public function test_generic_and_presentation_duplicates_ignore_case_and_outer_spaces(): void
    {
        $this->withoutMiddleware();
        $user = User::factory()->create(['hospital_id' => null]);
        $genericName = 'DILUYENTE DUPLICADO '.uniqid();
        $diluent = Diluent::create(['denominacion_generica' => $genericName]);
        DiluentCatalogPresentation::create([
            'diluent_id' => $diluent->id,
            'presentation' => 'Caja 100 piezas',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('admin.oncologicos.diluents.store'), [
            'generic_mode' => 'new',
            'denominacion_generica' => ' '.strtolower($genericName).' ',
            'catalog_presentation' => ['presentation' => 'Nueva presentación'],
        ])->assertSessionHasErrors('denominacion_generica');

        $this->actingAs($user)->post(route('admin.oncologicos.diluents.store'), [
            'generic_mode' => 'existing',
            'diluent_id' => $diluent->id,
            'catalog_presentation' => ['presentation' => ' caja 100 piezas '],
        ])->assertSessionHasErrors('catalog_presentation.presentation');

        $this->assertSame(1, Diluent::query()->whereRaw('LOWER(denominacion_generica) = ?', [strtolower($genericName)])->count());
        $this->assertSame(1, $diluent->catalogPresentations()->count());
    }
}
