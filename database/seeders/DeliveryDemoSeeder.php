<?php

namespace Database\Seeders;

use App\Models\DistributionDeliverySchedule;
use App\Models\DistributionRoute;
use App\Models\Hospital;
use App\Models\Nutricionales\Solicitud;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeliveryDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $hospital = Hospital::where('external_code', 'DRSAM-DEMO')->firstOrFail();
            $mixture = Solicitud::where('hospital_id', $hospital->id)
                ->whereIn('estado', ['aprobada', 'preparada', 'revisada'])
                ->with('solicitud_detail')->orderByDesc('id')->firstOrFail();
            $warehouse = Warehouse::where('laboratory_id', $hospital->laboratory_id)
                ->where('is_active', true)->firstOrFail();
            $date = now('America/Mexico_City')->toDateString();
            $mixture->solicitud_detail->update(['fecha_hora_entrega' => $date.' 16:00:00']);

            $route = DistributionRoute::firstOrCreate(['code' => 'R-DEMO-ENTREGA'], [
                'name' => 'Ruta de entrega Demo',
                'schedule_start' => '15:00:00',
                'schedule_end' => '18:00:00',
                'status' => DistributionRoute::STATUS_PENDING,
            ]);
            $route->hospitals()->syncWithoutDetaching([$hospital->id => ['stop_order' => 1]]);
            $schedule = DistributionDeliverySchedule::firstOrCreate([
                'hospital_id' => $hospital->id,
                'warehouse_id' => $warehouse->id,
                'scheduled_date' => $date,
            ], [
                'distribution_route_id' => $route->id,
                'status' => 'scheduled',
            ]);
            $this->command?->info('Entrega demo: '.$hospital->name.' | '.$date.' 16:00 | '.$route->code.' | '.$schedule->status);
        });
    }
}
