<?php

namespace Tests\Fixtures;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PreparationWorkflow
{
    public static function seed(): void
    {
        InspectionWorkflow::seed();
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
        auth()->user()->refresh();
        Schema::table('hospitals', function (Blueprint $table) {
            $table->integer('onco_medicine_list_id')->nullable();
            $table->integer('antibiotic_medicine_list_id')->nullable();
        });
        Schema::table('solicitud_oncos', fn (Blueprint $table) => $table->string('remision')->nullable());
        foreach ([
            'medicine_medicine_lists' => ['medicine_list_id', 'medicine_id'],
            'medicine_oncos' => ['catalog_id'],
            'medicine_list_presentation' => ['medicine_list_id', 'medicine_presentation_id'],
            'medicine_presentations' => ['catalog_id', 'is_available'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) $table->integer($column);
            });
        }
        DB::table('hospitals')->where('id', 1)->update(['onco_medicine_list_id' => 1, 'antibiotic_medicine_list_id' => 1]);
        DB::table('medicine_oncos')->insert(['id' => 1, 'catalog_id' => 1]);
        DB::table('medicine_medicine_lists')->insert(['medicine_list_id' => 1, 'medicine_id' => 1]);
        DB::table('mezclas')->where('id', 1)->update(['estado' => 'dispensada', 'production_attempt' => 2]);
        DB::table('solicitud_oncos')->where('id', 1)->update(['estado' => 'dispensada']);
        DB::table('inspeccion_mezclas')->where('mezcla_id', 1)->delete();
    }
}
