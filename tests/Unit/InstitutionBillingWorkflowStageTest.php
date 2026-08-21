<?php

namespace Tests\Unit;

use App\Models\InstitutionBilling;
use PHPUnit\Framework\TestCase;

class InstitutionBillingWorkflowStageTest extends TestCase
{
    public function test_it_classifies_a_billing_without_invoice_data_as_pending(): void
    {
        $billing = new InstitutionBilling();

        $this->assertSame(InstitutionBilling::STAGE_PENDING, $billing->workflowStage());
    }

    public function test_it_classifies_a_billing_with_complete_invoice_data_as_receivable(): void
    {
        $billing = new InstitutionBilling([
            'folio_interno' => 'F-123',
            'fecha_facturacion' => '2026-08-19',
            'numero_carta_factura' => 'CF-123',
            'fecha_carta_factura' => '2026-08-19',
            'estatus_facturacion' => 'Pendiente',
        ]);

        $this->assertSame(InstitutionBilling::STAGE_RECEIVABLE, $billing->workflowStage());
    }

    public function test_completed_status_always_classifies_a_billing_as_history(): void
    {
        $billing = new InstitutionBilling([
            'estatus_facturacion' => 'Completado',
        ]);

        $this->assertSame(InstitutionBilling::STAGE_HISTORY, $billing->workflowStage());
    }
}
