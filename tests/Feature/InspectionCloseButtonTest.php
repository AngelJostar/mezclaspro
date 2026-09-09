<?php

namespace Tests\Feature;

use App\Livewire\Oncologicos\InspeccionMezcla;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class InspectionCloseButtonTest extends TestCase
{
    public function test_close_button_has_a_local_icon_and_closes_without_saving(): void
    {
        $this->assertSame('sqlite', DB::getDriverName());
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lastname');
            $table->string('username');
        });
        Schema::create('personnel_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->json('positions')->nullable();
        });

        $inspection = Livewire::test(InspeccionMezcla::class);
        foreach ([1, 2] as $opening) {
            $inspection->set('mostrarModalInspeccion', true)
                ->set('observaciones', 'Observacion sin guardar '.$opening)
                ->assertSee('aria-label="Cerrar inspección"', false)
                ->assertSee('data-inspection-icon="x"', false)
                ->assertSee('&times;', false)
                ->assertDontSee('fa-xmark', false)
                ->assertDontSee('Marcar default')
                ->assertDontSee('marcarDefault', false)
                ->assertSee('wire:click="$set(\'mostrarModalInspeccion\', false)"', false);

            DB::enableQueryLog();
            DB::flushQueryLog();
            $inspection->call('$set', 'mostrarModalInspeccion', false)
                ->assertSet('mostrarModalInspeccion', false)
                ->assertDontSee('Cerrar inspección')
                ->assertSet('observaciones', 'Observacion sin guardar '.$opening);
            $this->assertEmpty(DB::getQueryLog());
            DB::disableQueryLog();
        }
    }
}
