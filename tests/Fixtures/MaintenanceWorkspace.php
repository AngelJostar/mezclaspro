<?php

namespace Tests\Fixtures;

use App\Models\{MaintenanceAppointment, MaintenanceProvider, MaintenanceService};
use App\Models\Oncologicos\Laboratory;

class MaintenanceWorkspace
{
    public static function seed(): array
    {
        $manager = HospitalCredentialList::seed()->fresh();
        auth()->login($manager);
        foreach (['2025_11_06_183220_create_laboratories_table.php', '2026_08_30_000002_create_maintenance_services_table.php', '2026_08_31_000001_create_maintenance_service_quotes_table.php', '2026_10_21_000003_create_maintenance_workspace.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $central = Laboratory::create(['nombre'=>'Central de prueba', 'estado'=>'CDMX', 'activo'=>true]);
        $otherCentral = Laboratory::create(['nombre'=>'Otra central', 'activo'=>true]);
        $service = MaintenanceService::create(['service'=>'Mantenimiento de refrigerador', 'type'=>'Preventivo', 'frequency'=>'Trimestral', 'description'=>'Inspección y servicio del equipo de refrigeración.', 'applicable_assets'=>'Refrigeradores', 'deliverables'=>'Reporte del servicio y evidencia adjunta', 'report_required'=>true, 'is_active'=>true]);
        $otherService = MaintenanceService::create(['service'=>'Calibración de balanza', 'type'=>'Calibración', 'frequency'=>'Semestral', 'laboratory_id'=>$otherCentral->id, 'report_required'=>true, 'is_active'=>true]);
        $provider = MaintenanceProvider::create(['name'=>'Proveedor de prueba', 'specialty'=>'Refrigeración', 'contact_name'=>'Contacto de prueba', 'email'=>'prueba@example.test', 'coverage'=>'CDMX', 'is_active'=>true]);
        $provider->services()->attach($service);
        $appointment = MaintenanceAppointment::create(['service_id'=>$service->id, 'provider_id'=>$provider->id, 'laboratory_id'=>$central->id, 'asset'=>'Refrigerador RF-01', 'location'=>'Almacén', 'responsible_name'=>'Responsable de prueba', 'frequency'=>'Trimestral', 'starts_at'=>'2026-10-19 09:00:00', 'ends_at'=>'2026-10-19 11:00:00', 'next_service_at'=>'2027-01-19', 'status'=>'completed', 'report_status'=>'pending', 'report_notes'=>'Actividades realizadas y observaciones.']);
        $second = MaintenanceAppointment::create(['service_id'=>$otherService->id, 'laboratory_id'=>$otherCentral->id, 'asset'=>'Balanza BAL-01', 'frequency'=>'Semestral', 'starts_at'=>'2026-10-21 10:00:00', 'status'=>'scheduled']);
        return compact('manager','central','otherCentral','service','otherService','provider','appointment','second');
    }
}
