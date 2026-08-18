<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_list_presentation', function (Blueprint $table) {
            $table->string('descripcion_remision', 500)
                ->nullable()
                ->after('iva_desglosado');
        });

        Schema::table('nutri_medicine_list_items', function (Blueprint $table) {
            $table->string('descripcion_remision', 500)
                ->nullable()
                ->after('precio_ml');
        });

        DB::table('medicine_list_presentation as mlp')
            ->join('medicine_presentations as mp', 'mp.id', '=', 'mlp.medicine_presentation_id')
            ->join('medicines_catalog as mc', 'mc.id', '=', 'mp.catalog_id')
            ->select([
                'mlp.id',
                'mc.denominacion as producto',
                'mp.presentacion',
            ])
            ->orderBy('mlp.id')
            ->get()
            ->each(function ($row) {
                DB::table('medicine_list_presentation')
                    ->where('id', $row->id)
                    ->update([
                        'descripcion_remision' => $this->defaultDescription(
                            $row->producto,
                            $row->presentacion
                        ),
                    ]);
            });

        DB::table('nutri_medicine_list_items as nli')
            ->join(
                'nutrition_medicine_presentations as nmp',
                'nmp.id',
                '=',
                'nli.nutrition_medicine_presentation_id'
            )
            ->join(
                'nutrition_medicines_catalog as nmc',
                'nmc.id',
                '=',
                'nmp.nutrition_medicine_catalog_id'
            )
            ->select([
                'nli.id',
                'nmc.denominacion_generica as producto',
                'nmp.presentacion',
            ])
            ->orderBy('nli.id')
            ->get()
            ->each(function ($row) {
                DB::table('nutri_medicine_list_items')
                    ->where('id', $row->id)
                    ->update([
                        'descripcion_remision' => $this->defaultDescription(
                            $row->producto,
                            $row->presentacion
                        ),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('medicine_list_presentation', function (Blueprint $table) {
            $table->dropColumn('descripcion_remision');
        });

        Schema::table('nutri_medicine_list_items', function (Blueprint $table) {
            $table->dropColumn('descripcion_remision');
        });
    }

    private function defaultDescription($product, $presentation): string
    {
        return trim(implode(' ', array_filter([
            trim((string) $product),
            trim((string) $presentation),
        ])));
    }
};
