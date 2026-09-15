<?php

namespace Tests\Feature;

use App\Exports\UnifiedSolicitudesExport;
use App\Http\Controllers\Admin\UnifiedSolicitudController;
use App\Http\Controllers\Admin\Nutricionales\SolicitudController as NutritionController;
use App\Http\Controllers\Admin\Oncologicos\SolicitudController as OncologyController;
use App\Livewire\Nutricionales\SolicitudesTable as NutritionTable;
use App\Livewire\Oncologicos\SolicitudesTable as OncologyTable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\SolicitudAdjustmentFilterData;
use Tests\TestCase;

class SolicitudAdjustmentFilterTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = SolicitudAdjustmentFilterData::seed();
    }

    public static function rolesAndCategories(): array
    {
        $cases = [];
        foreach (['Super Admin', 'Cliente', 'Institucion'] as $role) {
            foreach (['todas', 'nutricionales', 'oncologicos', 'antibioticos'] as $category) {
                $cases[] = [$role, $category];
            }
        }
        return $cases;
    }

    #[DataProvider('rolesAndCategories')]
    public function test_only_current_unapproved_adjustments_are_listed_in_the_allowed_scope(string $role, string $category): void
    {
        $this->user->syncRoles(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
        $this->actingAs($this->user);
        $actual = $this->patients($category)->sort()->values()->all();
        $expected = [];
        foreach ($role === 'Super Admin' ? [1, 2] : [1] as $hospital) {
            foreach ($category === 'todas' ? ['nutricionales', 'oncologicos', 'antibioticos'] : [$category] as $kind) {
                foreach (['requested', 'authorized', 'declined', 'empty', 'null'] as $case) {
                    $expected[] = $kind.' '.$case.' hospital '.$hospital;
                }
            }
        }
        sort($expected);
        $this->assertSame($expected, $actual);
    }

    public function test_final_approval_removes_the_request_from_both_filtered_views_and_export(): void
    {
        $target = DB::table('mixture_adjustments')->where('kind', 'nutricionales')
            ->where('hospital_id', 1)->where('status', 'authorized')->first();
        $this->assertCount(5, $this->patients('nutricionales'));
        $this->assertSame(12, app(UnifiedSolicitudController::class)->index($this->request())->getData()['adjustmentPendingCount']);
        DB::table('mixture_adjustments')->where('id', $target->id)->update(['status' => 'approved']);
        DB::table('solicituds')->where('id', $target->target_id)->update(['estado' => 'aprobada']);
        $this->assertCount(4, $this->patients('nutricionales'));
        $this->assertSame(11, app(UnifiedSolicitudController::class)->index($this->request())->getData()['adjustmentPendingCount']);
        $expected = $this->patients('todas')->all();
        $this->assertCount(14, $expected);
        Excel::fake();
        app(UnifiedSolicitudController::class)->exportarExcel($this->request());
        Excel::assertDownloaded('solicitudes_todas.xlsx', fn (UnifiedSolicitudesExport $export) =>
            $export->collection()->pluck('patient')->all() === $expected);
    }

    #[DataProvider('rolesAndCategories')]
    public function test_adjustment_badge_counts_only_current_requested_and_authorized_mixtures_independently_of_filter(string $role, string $category): void
    {
        $this->user->syncRoles(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
        $this->actingAs($this->user);
        $expected = 4 * ($category === 'todas' ? 3 : 1) * ($role === 'Super Admin' ? 2 : 1);

        foreach (['todas', 'pendientes', 'en_ajuste', 'historial'] as $status) {
            $request = $this->request();
            $request->query->add(['estado' => $status, 'tipo_solicitud' => $category, 'page' => 2]);
            app()->instance('request', $request);
            $view = match ($category) {
                'todas' => app(UnifiedSolicitudController::class)->index($request),
                'nutricionales' => app(NutritionController::class)->index(),
                default => app(OncologyController::class)->index($request),
            };
            $this->assertSame($expected, $view->getData()['adjustmentPendingCount']);
            $html = view('admin.solicitudes._status-selector', $view->getData())->render();
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            $badge = (new \DOMXPath($dom))->query('//nav/a[contains(@href, "estado=en_ajuste")]/span');
            $this->assertCount(1, $badge);
            $this->assertSame((string) $expected, trim($badge[0]->textContent));
            $this->assertStringContainsString('rounded-full', $badge[0]->getAttribute('class'));
        }
    }

    public function test_adjustment_badge_respects_category_permissions_and_displays_zero(): void
    {
        $this->user->revokePermissionTo('oncologicos_solicitudes_index');
        $data = app(UnifiedSolicitudController::class)->index($this->request())->getData();
        $this->assertSame(4, $data['adjustmentPendingCount']);
        DB::table('mixture_adjustments')->where('kind', 'nutricionales')->update(['status' => 'approved']);
        $data = app(UnifiedSolicitudController::class)->index($this->request())->getData();
        $this->assertSame(0, $data['adjustmentPendingCount']);
        $html = view('admin.solicitudes._status-selector', $data)->render();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $badge = (new \DOMXPath($dom))->query('//nav/a[contains(@href, "estado=en_ajuste")]/span');
        $this->assertCount(1, $badge);
        $this->assertSame('0', trim($badge[0]->textContent));
    }

    public function test_selector_position_active_state_and_category_links_keep_the_filter(): void
    {
        $request = $this->request();
        $request->query->add(['page' => 3]);
        app()->instance('request', $request);
        $html = view('admin.solicitudes._status-selector')->render()
            .view('admin.solicitudes._type-selector', ['selectedType' => 'todas'])->render();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        $links = $xpath->query('//nav[@aria-label="Estado de las solicitudes"]/a');
        $this->assertCount(8, $links);
        $this->assertSame('Pendientes', trim($links[1]->textContent));
        $this->assertSame('Mensajería', trim($links[2]->textContent));
        $this->assertSame('En Ajuste', trim($links[3]->textContent));
        $this->assertSame('En preparación', trim($links[4]->textContent));
        $this->assertSame('page', $links[3]->getAttribute('aria-current'));
        $this->assertStringNotContainsString('page=', $links[3]->getAttribute('href'));
        $categories = $xpath->query('//nav[@aria-label="Tipo de solicitudes"]//a');
        $this->assertCount(4, $categories);
        foreach ($categories as $link) $this->assertStringContainsString('estado=en_ajuste', $link->getAttribute('href'));
    }

    private function patients(string $category)
    {
        if ($category === 'todas') {
            return app(UnifiedSolicitudController::class)->index($this->request())->getData()['requests']->pluck('patient');
        }
        if ($category === 'nutricionales') {
            $component = new NutritionTable;
            $component->mount('en_ajuste');
            return $component->render()->getData()['solicitudes']->getCollection()
                ->map(fn ($row) => $row->solicitud_patient->nombre_paciente);
        }
        $component = new OncologyTable;
        $component->mount($category, 'en_ajuste');
        return $component->render()->getData()['mezclas']->getCollection()
            ->map(fn ($row) => $row->solicitud->nombre_paciente);
    }

    private function request(): Request
    {
        $request = Request::create(route('admin.solicitudes.index'), 'GET', ['estado' => 'en_ajuste']);
        $request->setUserResolver(fn () => $this->user);
        return $request;
    }
}
