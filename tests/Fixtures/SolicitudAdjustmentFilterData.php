<?php

namespace Tests\Fixtures;

use App\Models\MixtureAdjustment;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SolicitudAdjustmentFilterData
{
    public static function seed(): User
    {
        $user = UnifiedRequestExportData::seed();
        Schema::create('solicitud_inputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitud_id');
        });
        foreach ([1, 2] as $hospital) {
            foreach (['nutricionales', 'oncologicos', 'antibioticos'] as $kind) {
                foreach ([
                    'requested' => ['requested', 'pendiente'],
                    'authorized' => ['authorized', 'pendiente'],
                    'declined' => ['declined', 'pendiente'],
                    'empty' => ['requested', ''],
                    'null' => ['requested', null],
                    'approved' => ['approved', 'aprobada'],
                    'cancelled' => ['cancelled', 'pendiente'],
                    'rejected' => ['rejected', 'no_aprobada'],
                    'already_approved' => ['requested', 'aprobada'],
                    'request_cancelled' => ['requested', 'cancelada'],
                    'none' => [null, 'pendiente'],
                ] as $case => [$adjustmentStatus, $state]) {
                    $patient = $kind.' '.$case.' hospital '.$hospital;
                    if ($kind === 'nutricionales') {
                        $patientId = DB::table('solicitud_patients')->insertGetId(['nombre_paciente' => $patient]);
                        $id = DB::table('solicituds')->insertGetId([
                            'user_id' => $hospital, 'solicitud_patient_id' => $patientId,
                            'solicitud_detail_id' => $hospital, 'estado' => $state, 'created_at' => now(),
                        ]);
                        $table = 'solicituds';
                    } else {
                        $requestId = DB::table('solicitud_oncos')->insertGetId([
                            'user_id' => $hospital, 'hospital_id' => $hospital, 'tipo_solicitud' => $kind,
                            'nombre_paciente' => $patient, 'estado' => $state, 'created_at' => now(),
                        ]);
                        $id = DB::table('mezclas')->insertGetId([
                            'solicitud_id' => $requestId, 'estado' => $case === 'request_cancelled' ? 'pendiente' : $state,
                            'created_at' => now(), 'production_attempt' => 1,
                        ]);
                        $table = 'mezclas';
                    }
                    if ($adjustmentStatus === null) continue;
                    $data = [
                        'kind' => $kind, 'target_id' => $id, 'hospital_id' => $hospital,
                        'status' => $adjustmentStatus, 'description' => 'Version de prueba',
                        'proposal' => [], 'review' => [], 'baseline_hash' => str_repeat('0', 64), 'requested_by' => 1,
                    ];
                    // Older pending versions must not override the current cancelled version.
                    if ($case === 'cancelled') MixtureAdjustment::create(array_replace($data, ['status' => 'requested']));
                    $adjustment = MixtureAdjustment::create($data);
                    DB::table($table)->where('id', $id)->update(['adjustment_id' => $adjustment->id]);
                }
            }
        }

        return $user;
    }
}
