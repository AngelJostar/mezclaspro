<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class OperationalStatusBadgeTest extends TestCase
{
    public function test_inspected_status_is_blue_in_request_lists_and_the_shared_component(): void
    {
        foreach (['revisada', 'Inspeccionada'] as $status) {
            $html = view('admin.solicitudes._status-badge', compact('status'))->render();
            $this->assertStringContainsString('Inspeccionada', $html);
            $this->assertStringContainsString('bg-blue-100 text-blue-700', $html);
            $this->assertStringNotContainsString('purple', $html);
        }

        $html = Blade::render('<x-operational-status-badge status="revisada" />');
        $this->assertStringContainsString('Inspeccionada', $html);
        $this->assertStringContainsString('bg-blue-100 text-blue-700', $html);
        $this->assertStringNotContainsString('purple', $html);
    }

    public function test_dispensed_prepared_and_inspected_states_share_the_same_blue(): void
    {
        foreach (['dispensada', 'preparada', 'enproceso', 'revisada'] as $status) {
            foreach ([
                view('admin.solicitudes._status-badge', compact('status'))->render(),
                Blade::render('<x-operational-status-badge :status="$status" />', compact('status')),
            ] as $html) {
                $this->assertStringContainsString('bg-blue-100 text-blue-700', $html);
                $this->assertStringNotContainsString('sky-', $html);
                $this->assertStringNotContainsString('indigo-', $html);
            }
        }
    }

    public function test_other_operational_status_colors_are_unchanged(): void
    {
        foreach ([
            'pendiente' => 'bg-yellow-100 text-yellow-700',
            'aprobada' => 'bg-green-100 text-green-700',
            'entregada' => 'bg-gray-200 text-gray-700',
            'cancelada' => 'bg-red-100 text-red-700',
            'no_aprobada' => 'bg-red-200 text-red-800',
        ] as $status => $classes) {
            $this->assertStringContainsString($classes, view('admin.solicitudes._status-badge', compact('status'))->render());
            $this->assertStringContainsString($classes, Blade::render('<x-operational-status-badge :status="$status" />', compact('status')));
        }
    }
}
