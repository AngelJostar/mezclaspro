<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspeccion_nutricionales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('solicitud_id')
                ->constrained('solicituds')
                ->cascadeOnDelete()
                ->unique();

            $table->boolean('es_limpia')->default(false);
            $table->boolean('es_libre')->default(false);

            $table->date('fecha_inspeccion');
            $table->time('hora_inspeccion');

            $table->enum('tipo_contenedor', ['Frasco', 'Bolsa', 'Jeringa', 'Infusor'])->nullable();
            $table->string('tipo_contenedor_otro')->nullable();

            $table->boolean('esta_rotulado')->default(false);
            $table->boolean('numero_lote')->default(false);
            $table->boolean('medicamento')->default(false);
            $table->boolean('dosis_volumen_total')->default(false);
            $table->boolean('volumen_medicamento')->default(false);
            $table->boolean('rubrica_preparador')->default(false);
            $table->boolean('sello_seguridad')->default(false);
            $table->boolean('presenta_grietas')->default(false);
            $table->boolean('presenta_fugas')->default(false);
            $table->boolean('esta_roto')->default(false);
            $table->boolean('coloracion_apropiada')->default(false);
            $table->boolean('contenido_homogeneo')->default(false);
            $table->boolean('presenta_particulas')->default(false);
            $table->boolean('presenta_turbidez')->default(false);
            $table->boolean('volumen_correcto')->default(false);
            $table->boolean('aprueba_contenido')->default(false);
            $table->boolean('aprueba_contenedor')->default(false);

            $table->decimal('dosis_volumen', 8, 2)->default(0);
            $table->decimal('peso_mezcla', 8, 2)->default(0);

            $table->boolean('mezcla_aprobada')->default(false);
            $table->text('observaciones')->nullable();

            $table->string('reviso_nombre')->nullable();
            $table->string('aprobo_nombre')->nullable();
            $table->string('preparo_nombre')->nullable();
            $table->string('libero_nombre')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspeccion_nutricionales');
    }
};
