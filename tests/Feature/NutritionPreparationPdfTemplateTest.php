<?php

namespace Tests\Feature;

use Tests\TestCase;

class NutritionPreparationPdfTemplateTest extends TestCase
{
    public function test_it_uses_the_audit_trace_instead_of_the_old_signature_box(): void
    {
        $template = file_get_contents(resource_path('views/pdfs/nutricionales/orden-de-preparacion.blade.php'));

        $this->assertStringNotContainsString('FTO-NPT-025-005', $template);
        $this->assertStringNotContainsString('Nombre y firma', $template);

        foreach (['Captura:', 'Validación:', 'Preparación:', 'Inspección:', 'Aprobación:'] as $label) {
            $this->assertStringContainsString($label, $template);
        }

        $this->assertStringContainsString('$solicitud_detalles->validated_at', $template);
        $this->assertStringContainsString('$inspeccion?->inspection_completed_at', $template);
        $this->assertStringContainsString('@unless ($soloInspeccion ?? false)', $template);
        $this->assertStringContainsString('@if ($soloInspeccion ?? false)', $template);
        $this->assertStringNotContainsString("'salto-pagina'", $template);
    }
}
