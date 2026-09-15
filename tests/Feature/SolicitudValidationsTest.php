<?php

namespace Tests\Feature;

use App\Exports\SolicitudValidationsExport;
use App\Http\Controllers\Admin\SolicitudValidationController;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Fixtures\SolicitudValidations;
use Tests\Fixtures\RejectedSolicitudData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SolicitudValidationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(SolicitudValidations::boot());
        RejectedSolicitudData::boot();
    }

    public function test_all_categories_keep_navigation_inside_validations_and_show_empty_filterable_table(): void
    {
        foreach (['todas', 'nutricionales', 'oncologicos', 'antibioticos'] as $type) {
            $response = $this->get(route('admin.solicitudes.validaciones.index', ['tipo' => $type, 'estado' => 'pendientes']));
            $response->assertOk()->assertViewHas('selectedType', $type)
                ->assertViewHas('validations', fn ($rows) => $rows->isEmpty())
                ->assertSee('No hay solicitudes rechazadas para estos filtros.')
                ->assertSee('Exportar a Excel')->assertSee('Observaciones')->assertSee('Ajustes');
            $html = $response->getContent();
            $this->assertSame(17, substr_count($html, 'data-force-column-filter'));
            $this->assertStringContainsString('role="status"', $html);
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            $links = (new \DOMXPath($dom))->query('//nav[@aria-label="Tipo de solicitudes"]//a');
            $this->assertCount(4, $links);
            foreach ($links as $link) {
                $this->assertStringContainsString('/solicitudes/validaciones', $link->getAttribute('href'));
                $this->assertStringContainsString('estado=pendientes', $link->getAttribute('href'));
            }
            $selected = (new \DOMXPath($dom))->query('//nav[@aria-label="Tipo de solicitudes"]//a[@aria-current="page"]');
            $this->assertCount(1, $selected);
            $this->assertStringContainsString('Seleccionada', $selected[0]->textContent);
            $this->assertStringContainsString('bg-cyan-50', $selected[0]->getAttribute('class'));
            $this->assertSame(route('admin.solicitudes.validaciones.index', array_filter([
                'estado' => 'pendientes', 'tipo' => $type === 'todas' ? null : $type,
            ])), $selected[0]->getAttribute('href'));
        }
    }

    public function test_list_category_cards_preserve_routes_and_permission_visibility(): void
    {
        $this->app->instance('request', Request::create('/admin/solicitudes', 'GET', ['estado' => 'preparacion']));
        $routes = [
            'admin.solicitudes.index', 'admin.nutricionales.solicitudes.index',
            'admin.oncologicos.solicitudes.index', 'admin.antibioticos.solicitudes.index',
        ];
        foreach ([[true, true, [0, 1, 2, 3]], [true, false, [0, 1]], [false, true, [0, 2, 3]], [false, false, []]] as [$nutrition, $oncology, $visible]) {
            $html = view('admin.solicitudes._type-selector', [
                'selectedType' => 'todas', 'canViewNutrition' => $nutrition, 'canViewOncology' => $oncology,
            ])->render();
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            $links = (new \DOMXPath($dom))->query('//nav[@aria-label="Tipo de solicitudes"]//a');
            $this->assertCount(count($visible), $links);
            foreach ($visible as $position => $index) {
                $this->assertSame(route($routes[$index], ['estado' => 'preparacion']), $links[$position]->getAttribute('href'));
            }
        }
    }

    public function test_sidebar_keeps_only_listado_under_an_expanded_requests_parent(): void
    {
        $request = Request::create(route('admin.solicitudes.index'));
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));
        $this->app->instance('request', $request);
        $html = view('layouts.includes.admin.aside', [
            'pendingSolicitudesCount' => 8,
            'myPurchaseOrdersCount' => 0,
            'billingDueCounts' => ['yellow' => 0, 'red' => 0],
        ])->render();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        $links = $xpath->query('//ul[@id="solicitudes-submenu"]//a');
        $this->assertCount(1, $links);
        $this->assertSame('Listado', trim($links[0]->textContent));
        $this->assertSame(route('admin.solicitudes.index'), $links[0]->getAttribute('href'));
        $this->assertStringNotContainsString('Validaciones', $html);
        $this->assertStringNotContainsString(route('admin.solicitudes.validaciones.index'), $html);
        $this->assertSame('page', $links[0]->getAttribute('aria-current'));
        $this->assertStringContainsString('solicitudes', $xpath->query('//aside')->item(0)->getAttribute('x-data'));
        $this->assertStringContainsString('8 solicitudes pendientes', $html);
    }

    public function test_export_is_an_empty_validation_workbook_not_the_request_list(): void
    {
        Excel::fake();
        $this->get(route('admin.solicitudes.validaciones.exportar', ['tipo' => 'oncologicos']))->assertOk();
        Excel::assertDownloaded('validaciones.xlsx', function (SolicitudValidationsExport $export) {
            $this->assertSame([], $export->array());
            $this->assertCount(12, $export->headings());
            $this->assertSame('Observaciones', $export->headings()[11]);

            return true;
        });
    }

    public function test_demo_shows_only_existing_rejections_and_preserves_their_state(): void
    {
        RejectedSolicitudData::seed();
        foreach (['todas' => 6, 'nutricionales' => 2, 'oncologicos' => 3, 'antibioticos' => 1] as $type => $count) {
            foreach (['todas', 'historial'] as $status) {
                $response = $this->get(route('admin.solicitudes.validaciones.index', ['tipo' => $type, 'estado' => $status]))
                    ->assertOk()->assertViewHas('validations', fn ($rows) => $rows->count() === $count);
                $this->assertSame($count, substr_count($response->getContent(), 'data-validation-row'));
            }
        }
        $response = $this->get(route('admin.solicitudes.validaciones.index'))->assertOk()
            ->assertSee('Demo: solicitudes rechazadas')->assertSee('Cancelada')->assertSee('No aprobada')
            ->assertSee('&lt;script&gt;ejemplo&lt;/script&gt;', false)->assertDontSee('<script>ejemplo</script>', false);
        $rows = $response->viewData('validations');
        $this->assertSame([101, 103, 301, 303, null, null], $rows->pluck('id')->all());
        $this->assertNotNull($rows->first()['inspection_url']);
        $this->assertNull($rows->firstWhere('id', 301)['inspection_url']);
        $this->assertSame('aprobada', DB::table('mezclas')->where('id', 303)->value('estado'));
        $this->assertSame('no-aprobada', DB::table('solicitud_oncos')->where('id', 202)->value('estado'));
        foreach (['pendientes', 'preparacion', 'ruta', 'entregadas'] as $status) {
            $this->get(route('admin.solicitudes.validaciones.index', ['estado' => $status]))
                ->assertOk()->assertViewHas('validations', fn ($rows) => $rows->isEmpty());
        }
    }

    public function test_export_contains_the_same_rejections_and_filters_as_the_screen(): void
    {
        RejectedSolicitudData::seed();
        Excel::fake();
        $this->get(route('admin.solicitudes.validaciones.exportar', ['tipo' => 'nutricionales']))->assertOk();
        Excel::assertDownloaded('validaciones.xlsx', function (SolicitudValidationsExport $export) {
            $rows = $export->array();
            $this->assertCount(2, $rows);
            $this->assertSame([101, 103], array_column($rows, 1));
            $this->assertSame('Rechazada', $rows[0][9]);
            $sheet = (new \PhpOffice\PhpSpreadsheet\Spreadsheet)->getActiveSheet();
            $export->bindValue($sheet->getCell('A1'), '=1+1');
            $this->assertSame('s', $sheet->getCell('A1')->getDataType());

            return true;
        });
    }

    public function test_hospital_users_only_see_their_requests_and_allowed_columns_in_screen_and_export(): void
    {
        RejectedSolicitudData::seed();
        foreach (['Cliente', 'Institucion'] as $role) {
            $this->actingAs($this->hospitalUser($role, 1));
            $response = $this->get(route('admin.solicitudes.validaciones.index'))->assertOk()
                ->assertViewHas('validations', fn ($rows) => $rows->pluck('request_id')->all() === [101, 201, 203]);
            $this->assertSame(11, substr_count($response->getContent(), 'data-force-column-filter'));
            $response->assertDontSee('Hospital Dos')->assertDontSee('Solicitud completa')->assertDontSee('Observaciones');
            Excel::fake();
            $this->get(route('admin.solicitudes.validaciones.exportar'))->assertOk();
            Excel::assertDownloaded('validaciones.xlsx', function (SolicitudValidationsExport $export) {
                $this->assertCount(10, $export->headings());
                $this->assertSame([101, 201, 203], array_column($export->array(), 2));
                $this->assertCount(10, $export->array()[0]);

                return true;
            });
        }
        $this->actingAs($this->hospitalUser('Cliente', null, 3));
        $this->get(route('admin.solicitudes.validaciones.index'))->assertOk()
            ->assertViewHas('validations', fn ($rows) => $rows->isEmpty());
    }

    private function hospitalUser(string $role, ?int $hospitalId, int $id = 1): User
    {
        $user = (new User)->forceFill(['id' => $id, 'is_active' => true, 'hospital_id' => $hospitalId]);
        $user->setRelation('roles', new Collection([Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])]));
        $user->setRelation('permissions', new Collection(array_map(
            fn ($name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']),
            ['menu.solicitudes', 'nutricionales_solicitudes_index', 'oncologicos_solicitudes_index']
        )));

        return $user;
    }

    public function test_no_permission_and_unavailable_categories_are_forbidden(): void
    {
        foreach ([[], ['nutricionales_solicitudes_index'], ['oncologicos_solicitudes_index']] as $permissions) {
            $user = Mockery::mock(User::class);
            $user->shouldReceive('can')->andReturnUsing(fn ($ability) => in_array($ability, $permissions, true));
            $request = Request::create('/admin/solicitudes/validaciones', 'GET', [
                'tipo' => in_array('nutricionales_solicitudes_index', $permissions, true) ? 'oncologicos' : 'nutricionales',
            ]);
            $request->setUserResolver(fn () => $user);
            try {
                app(SolicitudValidationController::class)->index($request);
                $this->fail('Unauthorized category was accepted.');
            } catch (HttpException $error) {
                $this->assertSame(403, $error->getStatusCode());
            }
        }
    }

    public function test_guest_cannot_open_or_export_validations(): void
    {
        auth()->forgetGuards();
        $this->get(route('admin.solicitudes.validaciones.index'))->assertRedirect(route('login'));
        $this->get(route('admin.solicitudes.validaciones.exportar'))->assertRedirect(route('login'));
    }
}
