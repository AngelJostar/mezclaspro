<?php

namespace Tests\Feature;

use App\Models\InstitutionReportTemplate;
use App\Services\InstitutionReportTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionCustomReportTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_updates_and_deletes_a_custom_grid_template(): void
    {
        $service = new InstitutionReportTemplateService;
        $payload = [
            'name' => 'Control mensual de entregas',
            'description' => 'Plantilla personalizada de validación.',
            'data_source' => 'solicitudes',
            'layout' => [
                'rows' => 4,
                'columns' => 3,
                'cells' => [
                    [
                        'row' => 0,
                        'column' => 0,
                        'type' => 'parameter',
                        'value' => '',
                        'parameter' => 'institution.name',
                        'repeat_direction' => 'vertical',
                        'style' => [
                            'background' => '#2D3D78',
                            'color' => '#FFFFFF',
                            'font_family' => 'Arial',
                            'font_size' => 14,
                            'bold' => true,
                            'italic' => false,
                            'underline' => false,
                            'align' => 'center',
                        ],
                    ],
                ],
            ],
        ];

        $created = $service->createCustom($payload, null);

        $this->assertSame('Control mensual de entregas', $created['name']);
        $this->assertFalse($created['is_published']);
        $this->assertSame(4, $created['layout']['rows']);
        $this->assertSame('institution.name', $created['layout']['cells'][0]['parameter']);
        $this->assertSame('vertical', $created['layout']['cells'][0]['repeat_direction']);
        $this->assertDatabaseHas('institution_report_templates', [
            'id' => $created['id'],
            'is_custom' => true,
            'data_source' => 'solicitudes',
        ]);

        $template = InstitutionReportTemplate::query()->findOrFail($created['id']);
        $updated = $service->updateCustom($template, array_replace($payload, [
            'name' => 'Control semanal de entregas',
        ]));

        $this->assertSame('Control semanal de entregas', $updated['name']);

        $published = $service->publishCustom($template->refresh(), true);

        $this->assertTrue($published['is_published']);
        $this->assertNotNull($published['published_at']);
        $this->assertSame([$created['id']], collect($service->publishedCustomTemplates())->pluck('id')->all());

        $unpublished = $service->publishCustom($template->refresh(), false);

        $this->assertFalse($unpublished['is_published']);
        $this->assertNull($unpublished['published_at']);

        $service->deleteCustom($template->refresh());
        $this->assertDatabaseMissing('institution_report_templates', ['id' => $created['id']]);
    }
}
