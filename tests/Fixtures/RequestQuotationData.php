<?php

namespace Tests\Fixtures;

use App\Models\RequestQuotation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class RequestQuotationData
{
    public static function seed(): User
    {
        $user = UnifiedRequestExportData::seed();
        $user->syncRoles(Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']));
        (require database_path('migrations/2026_09_22_000001_create_request_quotations_table.php'))->up();
        (require database_path('migrations/2026_09_22_000002_add_capture_to_request_quotations.php'))->up();
        (require database_path('migrations/2026_09_22_000004_add_seller_to_request_quotations.php'))->up();
        (require database_path('migrations/2024_05_21_124030_create_notifications_table.php'))->up();
        foreach (['users' => 'is_active', 'hospitals' => 'access_is_active', 'clientes' => 'is_active'] as $table => $column) {
            Schema::table($table, fn (Blueprint $schema) => $schema->boolean($column)->default(true));
        }
        DB::table('clientes')->insert([
            ['id' => 1, 'nombre' => 'Institucion de prueba'],
            ['id' => 2, 'nombre' => 'Otra institucion'],
        ]);
        DB::table('cliente_hospital')->insert([
            ['hospital_id' => 1, 'cliente_id' => 1], ['hospital_id' => 2, 'cliente_id' => 2],
        ]);
        foreach ([
            [1, 'oncologicos', 'borrador', 100.50, '2026-09-20 09:00:00'],
            [2, 'oncologicos', 'enviada', 210, '2026-09-21 10:00:00'],
            [3, 'nutricionales', 'autorizada', 95.25, '2026-09-21 23:59:59'],
            [4, 'antibioticos', 'preparacion', 500, '2026-09-22 11:00:00'],
        ] as [$id, $category, $status, $total, $date]) {
            RequestQuotation::forceCreate([
                'id' => $id, 'hospital_id' => 1, 'institution_id' => 1, 'created_by' => $user->id,
                'category' => $category, 'patient_name' => 'Paciente de prueba '.$id,
                'price_list_id' => 1, 'price_list_name' => 'Lista del hospital', 'total' => $total,
                'status' => $status, 'created_at' => $date, 'updated_at' => $date,
                'authorized_by' => in_array($status, ['autorizada', 'preparacion']) ? $user->id : null,
                'authorized_at' => in_array($status, ['autorizada', 'preparacion']) ? $date : null,
                'request_id' => $status === 'preparacion' ? 2 : null,
            ]);
        }
        RequestQuotation::forceCreate([
            'id' => 5, 'hospital_id' => 2, 'institution_id' => 2, 'created_by' => 2,
            'category' => 'oncologicos', 'patient_name' => 'Paciente ajeno',
            'price_list_id' => 2, 'price_list_name' => 'Lista ajena', 'total' => 123,
            'status' => 'enviada', 'created_at' => '2026-09-21 12:00:00',
        ]);

        return $user->refresh();
    }
}
