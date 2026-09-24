<?php

namespace Tests\Fixtures;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class RejectedSolicitudData
{
    public static function boot(bool $withRecords = false): void
    {
        if (DB::getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Rejection fixtures require an in-memory database.');
        }

        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('access_is_active')->default(true);
        });
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
        });
        Schema::create('cliente_hospital', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('hospital_id');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hospital_id')->nullable();
        });
        Schema::create('solicitud_patients', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_paciente');
            $table->string('apellidos_paciente')->nullable();
        });
        Schema::create('solicitud_details', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha_hora_entrega')->nullable();
            $table->text('observaciones')->nullable();
        });
        Schema::create('solicituds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('request_quotation_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('solicitud_patient_id')->nullable();
            $table->unsignedBigInteger('solicitud_detail_id')->nullable();
            $table->string('estado');
            $table->string('lote')->nullable();
            $table->timestamps();
        });
        Schema::create('solicitud_oncos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('tipo_solicitud');
            $table->string('nombre_paciente')->nullable();
            $table->string('estado');
            $table->text('observaciones')->nullable();
            $table->dateTime('fecha_entrega')->nullable();
            $table->timestamps();
        });
        Schema::create('mezclas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitud_id');
            $table->string('estado');
            $table->string('lote')->nullable();
            $table->dateTime('fecha_entrega')->nullable();
            $table->unsignedInteger('production_attempt')->default(1);
            $table->timestamps();
        });
        foreach (['inspeccion_nutricionales' => 'solicitud_id', 'inspeccion_mezclas' => 'mezcla_id'] as $table => $foreignKey) {
            Schema::create($table, function (Blueprint $table) use ($foreignKey) {
                $table->id();
                $table->unsignedBigInteger($foreignKey);
            });
        }

        if ($withRecords) self::seed();
    }

    public static function seed(): void
    {
        DB::table('hospitals')->insert([['id' => 1, 'name' => 'Hospital Uno'], ['id' => 2, 'name' => 'Hospital Dos']]);
        DB::table('users')->insert([['id' => 1, 'hospital_id' => 1], ['id' => 2, 'hospital_id' => 2]]);
        DB::table('solicitud_patients')->insert(['id' => 1, 'nombre_paciente' => 'Paciente de prueba', 'apellidos_paciente' => 'Nutricional']);
        DB::table('solicitud_details')->insert(['id' => 1, 'observaciones' => '<script>ejemplo</script>', 'fecha_hora_entrega' => '2026-09-15 10:00:00']);
        foreach ([101 => [1, 'cancelada'], 102 => [1, 'pendiente'], 103 => [2, 'no_aprobada']] as $id => [$user, $status]) {
            DB::table('solicituds')->insert(['id' => $id, 'user_id' => $user, 'estado' => $status,
                'solicitud_patient_id' => 1, 'solicitud_detail_id' => 1, 'created_at' => '2026-09-14 09:00:00']);
        }
        foreach ([201 => [1, 'oncologicos', 'pendiente'], 202 => [2, 'antibioticos', 'no-aprobada'],
            203 => [1, 'oncologicos', 'no_aprobada'], 204 => [2, 'oncologicos', 'aprobada'],
            205 => [null, 'oncologicos', 'no_aprobada']] as $id => [$hospital, $type, $status]) {
            DB::table('solicitud_oncos')->insert(['id' => $id, 'hospital_id' => $hospital, 'tipo_solicitud' => $type,
                'estado' => $status, 'nombre_paciente' => 'Paciente de prueba '.$id, 'created_at' => '2026-09-14 08:00:00']);
        }
        foreach ([301 => [201, 'cancelada'], 302 => [201, 'aprobada'], 303 => [202, 'aprobada'], 304 => [204, 'aprobada']] as $id => [$request, $status]) {
            DB::table('mezclas')->insert(['id' => $id, 'solicitud_id' => $request, 'estado' => $status,
                'production_attempt' => $id === 304 ? 2 : 1]);
        }
        DB::table('inspeccion_nutricionales')->insert(['solicitud_id' => 101]);
    }
}
