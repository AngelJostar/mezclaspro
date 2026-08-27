<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('hospital_id')
                ->constrained('warehouses')
                ->nullOnDelete();
        });

        $primaryWarehouses = DB::table('warehouses')
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->get(['id', 'laboratory_id'])
            ->groupBy('laboratory_id')
            ->map(fn ($warehouses) => $warehouses->first()->id);

        if ($primaryWarehouses->isEmpty()) {
            return;
        }

        DB::table('users')
            ->join('hospitals', 'hospitals.id', '=', 'users.hospital_id')
            ->whereNull('users.warehouse_id')
            ->whereNotExists(function ($query) {
                $query
                    ->selectRaw('1')
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->whereIn('roles.name', ['Cliente', 'Institucion']);
            })
            ->select(['users.id', 'hospitals.laboratory_id'])
            ->chunkById(200, function ($users) use ($primaryWarehouses) {
                foreach ($users as $user) {
                    $warehouseId = $primaryWarehouses->get($user->laboratory_id);

                    if ($warehouseId) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['warehouse_id' => $warehouseId]);
                    }
                }
            }, 'users.id', 'id');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });
    }
};
