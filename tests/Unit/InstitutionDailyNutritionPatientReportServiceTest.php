<?php

namespace Tests\Unit;

use App\Models\InstitutionBilling;
use App\Models\Nutricionales\Solicitud;
use App\Models\Nutricionales\SolicitudDetail;
use App\Models\Nutricionales\SolicitudPatient;
use App\Services\InstitutionBillingPricingService;
use App\Services\InstitutionDailyNutritionPatientReportService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class InstitutionDailyNutritionPatientReportServiceTest extends TestCase
{
    public function test_it_uses_the_current_calendar_month_when_no_range_is_provided(): void
    {
        Carbon::setTestNow('2025-07-15 10:00:00');

        try {
            $pricing = new class extends InstitutionBillingPricingService
            {
                public function priceNutritionRequest(Solicitud $solicitud): array
                {
                    return ['total_iva_included' => 100];
                }
            };

            $service = new InstitutionDailyNutritionPatientReportService($pricing);
            $result = $service->build(collect([
                $this->request(1, 'entregada', 'Si', '2025-06-30 23:59:59', 'L0630', 'Fuera', 'Antes'),
                $this->request(2, 'entregada', 'Si', '2025-07-01 00:00:00', 'L0701', 'Dentro', 'Inicio'),
                $this->request(3, 'entregada', 'Si', '2025-07-31 23:59:59', 'L0731', 'Dentro', 'Fin'),
                $this->request(4, 'entregada', 'Si', '2025-08-01 00:00:00', 'L0801', 'Fuera', 'Despues'),
            ]));
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame(2, $result['detail_count']);
        $this->assertContains('Dentro Inicio', array_column($result['rows'], 3));
        $this->assertContains('Dentro Fin', array_column($result['rows'], 3));
        $this->assertNotContains('Fuera Antes', array_column($result['rows'], 3));
        $this->assertNotContains('Fuera Despues', array_column($result['rows'], 3));
    }

    public function test_it_groups_delivered_requests_by_day_and_excludes_non_reconcilable_requests(): void
    {
        $pricing = new class extends InstitutionBillingPricingService
        {
            public array $totals = [];

            public function priceNutritionRequest(Solicitud $solicitud): array
            {
                return ['total_iva_included' => $this->totals[$solicitud->id] ?? 0];
            }
        };
        $pricing->totals = [1 => 100.25, 2 => 200.50, 3 => 999.99, 4 => 50.00, 5 => 800.00];

        $service = new InstitutionDailyNutritionPatientReportService($pricing);
        $result = $service->build(collect([
            $this->request(1, 'entregada', 'Si', '2025-07-01 09:00:00', 'L0101', 'Ana', 'Perez'),
            $this->request(2, 'entregada', null, '2025-07-01 12:00:00', 'L0102', 'Beatriz', 'Lopez'),
            $this->request(3, 'entregada', 'No conciliable', '2025-07-01 14:00:00', 'L0103', 'Carmen', 'Ruiz'),
            $this->request(4, 'finalizada', 'Si', '2025-07-02 10:00:00', 'L0201', 'Diana', 'Soto'),
            $this->request(5, 'entregada', 'Si', '2025-06-30 23:59:59', 'L0630', 'Elena', 'Diaz'),
        ]), Carbon::parse('2025-07-01'), Carbon::parse('2025-07-31'));

        $this->assertSame(3, $result['detail_count']);
        $this->assertSame([2, 5], $result['summary_rows']);
        $this->assertSame(['Total del dia: 2 mezclas', '01-07-2025', null, null, null, null, 300.75], $result['rows'][0]);
        $this->assertSame(1, $result['rows'][1][0]);
        $this->assertSame(2, $result['rows'][2][0]);
        $this->assertSame('L0101', $result['rows'][1][2]);
        $this->assertSame('Ana Perez', $result['rows'][1][3]);
        $this->assertSame(['Total del dia: 1 mezcla', '02-07-2025', null, null, null, null, 50.0], $result['rows'][3]);
        $this->assertSame(1, $result['rows'][4][0]);
        $this->assertNotContains('Carmen Ruiz', array_column($result['rows'], 3));
        $this->assertNotContains('Elena Diaz', array_column($result['rows'], 3));
    }

    private function request(
        int $id,
        string $status,
        ?string $reconcilable,
        string $deliveredAt,
        string $lot,
        string $firstName,
        string $lastName
    ): Solicitud {
        $request = new Solicitud([
            'estado' => $status,
            'lote' => $lot,
        ]);
        $request->id = $id;
        $request->setRelation('solicitud_detail', new SolicitudDetail([
            'fecha_hora_entrega' => $deliveredAt,
            'nombre_medico' => 'Dra. Demo',
        ]));
        $request->setRelation('solicitud_patient', new SolicitudPatient([
            'nombre_paciente' => $firstName,
            'apellidos_paciente' => $lastName,
            'fecha_nacimiento' => '2020-05-16',
        ]));
        $request->setRelation('billing', $reconcilable === null
            ? null
            : new InstitutionBilling(['conciliable' => $reconcilable]));

        return $request;
    }
}
