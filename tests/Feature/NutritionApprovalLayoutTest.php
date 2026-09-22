<?php

namespace Tests\Feature;

use Tests\TestCase;

class NutritionApprovalLayoutTest extends TestCase
{
    public function test_component_units_and_calculated_volumes_have_independent_layout_columns(): void
    {
        $template = file_get_contents(resource_path('views/admin/nutricionales/solicitudes/edit.blade.php'));

        $this->assertStringContainsString('flex-wrap items-end gap-3', $template);
        $this->assertStringContainsString('flex: 2 1 320px', $template);
        $this->assertStringContainsString('shrink-0 items-center whitespace-nowrap', $template);
        $this->assertStringContainsString('flex: 0 1 105px', $template);
        $this->assertStringContainsString('flex: 0 1 135px', $template);
        $this->assertStringContainsString('whitespace-normal break-words font-bold leading-tight', $template);
        $this->assertStringContainsString('min-height: 2.5rem', $template);
    }
}
