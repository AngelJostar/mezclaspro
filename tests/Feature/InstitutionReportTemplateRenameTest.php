<?php

namespace Tests\Feature;

use App\Services\InstitutionReportTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionReportTemplateRenameTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_a_custom_display_name_without_changing_the_export_title(): void
    {
        $templates = new InstitutionReportTemplateService;
        $before = $templates->get(InstitutionReportTemplateService::DAILY_PATIENT);

        $renamed = $templates->rename(
            InstitutionReportTemplateService::DAILY_PATIENT,
            'Censo diario de mezclas'
        );

        $this->assertSame('Censo diario de mezclas', $renamed['name']);
        $this->assertSame($before['title'], $renamed['title']);
        $this->assertDatabaseHas('institution_report_templates', [
            'report_key' => InstitutionReportTemplateService::DAILY_PATIENT,
            'name' => 'Censo diario de mezclas',
            'title' => $before['title'],
        ]);

        $this->assertSame(
            'Censo diario de mezclas',
            (new InstitutionReportTemplateService)
                ->get(InstitutionReportTemplateService::DAILY_PATIENT)['name']
        );
    }
}
