<?php

namespace Tests\Feature;

use App\Exports\UnifiedSolicitudesExport;
use App\Http\Controllers\Admin\UnifiedSolicitudController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\HospitalRequestTable;
use Tests\Fixtures\UnifiedRequestExportData;
use Tests\TestCase;

class UnifiedSolicitudesExportTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = UnifiedRequestExportData::seed();
    }

    public function test_download_combines_the_allowed_categories_and_matches_each_status_view(): void
    {
        foreach (['todas' => 5, 'pendientes' => 1, 'preparacion' => 2, 'ruta' => 1, 'entregadas' => 1, 'historial' => 1] as $status => $count) {
            $request = Request::create(route('admin.solicitudes.index'), 'GET', ['estado' => $status]);
            $request->setUserResolver(fn () => $this->user);
            $rows = app(UnifiedSolicitudController::class)->index($request)->getData()['requests'];
            $this->assertCount($count, $rows);
            $book = $this->download(['estado' => $status]);
            $sheet = $book->getActiveSheet();
            $this->assertSame($count + 1, $sheet->getHighestRow());
            $this->assertSame('J', $sheet->getHighestColumn());
            $this->assertSame($rows->pluck('type_label')->all(), array_column($sheet->rangeToArray('A2:A'.($count + 1)), 0));
            $this->assertSame($rows->pluck('patient')->all(), array_column($sheet->rangeToArray('E2:E'.($count + 1)), 0));
            $this->assertSame('A2', $sheet->getFreezePane());
            $this->assertSame('A1:J'.($count + 1), $sheet->getAutoFilter()->getRange());
            $this->assertNotContains('Hospital ajeno', array_column($sheet->rangeToArray('D2:D'.($count + 1)), 0));
            $this->assertSame((new UnifiedSolicitudesExport(collect()))->headings(), $sheet->rangeToArray('A1:J1')[0]);
            if ($status === 'pendientes') $this->assertNull($sheet->getCell('B2')->getValue());
            if ($status === 'ruta') $this->assertSame('Inspeccionada', $sheet->getCell('H2')->getValue());
            $book->disconnectWorksheets();
        }
    }

    public function test_permissions_restrict_categories_and_administrators_keep_their_scope(): void
    {
        foreach (['nutricionales_solicitudes_index' => 1, 'oncologicos_solicitudes_index' => 4] as $permission => $count) {
            $this->user->syncPermissions([$permission]);
            $book = $this->download();
            $this->assertSame($count + 1, $book->getActiveSheet()->getHighestRow());
            $book->disconnectWorksheets();
        }
        $this->user->syncPermissions(['nutricionales_solicitudes_index', 'oncologicos_solicitudes_index']);
        $admin = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $this->user->syncRoles($admin);
        $book = $this->download();
        $this->assertSame(8, $book->getActiveSheet()->getHighestRow());
        $book->disconnectWorksheets();

        $this->user->assignRole('Institucion');
        $book = $this->download(['hospital_id' => 2, 'user_id' => 2]);
        $this->assertSame(6, $book->getActiveSheet()->getHighestRow());
        $book->disconnectWorksheets();
    }

    public function test_dates_identifiers_and_patient_text_are_exported_safely(): void
    {
        $this->user->syncPermissions(['nutricionales_solicitudes_index']);
        DB::table('solicitud_patients')->where('id', 1)->update(['nombre_paciente' => '=1+1', 'apellidos_paciente' => null]);
        $book = $this->download();
        $sheet = $book->getActiveSheet();
        $this->assertSame('00042', $sheet->getCell('I2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('I2')->getDataType());
        $this->assertSame('=1+1', $sheet->getCell('E2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('E2')->getDataType());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('F2')->getDataType());
        $this->assertSame('2026-09-08 09:30', $sheet->getCell('F2')->getFormattedValue());
        $this->assertSame('2026-09-09 15:00', $sheet->getCell('G2')->getFormattedValue());
        $book->disconnectWorksheets();
    }

    public function test_empty_results_download_a_workbook_with_headers(): void
    {
        $this->user->forceFill(['hospital_id' => 99])->save();
        $this->user->syncPermissions(['oncologicos_solicitudes_index']);
        $book = $this->download();
        $this->assertSame(1, $book->getActiveSheet()->getHighestRow());
        $this->assertSame('Tipo', $book->getActiveSheet()->getCell('A1')->getValue());
        $book->disconnectWorksheets();
    }

    public function test_download_requires_login_and_request_list_permission(): void
    {
        $routes = Route::getRoutes();
        $this->assertSame($routes->getByName('admin.solicitudes.index')->gatherMiddleware(),
            $routes->getByName('admin.solicitudes.exportar')->gatherMiddleware());
        auth()->logout();
        $this->get(route('admin.solicitudes.exportar'))->assertRedirect(route('login'));
        $this->actingAs($this->user);
        $this->user->syncPermissions([]);
        $this->withoutMiddleware()->get(route('admin.solicitudes.exportar'))->assertForbidden();
    }

    public function test_the_all_requests_view_has_the_matching_green_export_button(): void
    {
        $html = HospitalRequestTable::render('todas', 'Institucion');
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $links = (new \DOMXPath($document))->query('//a[contains(@href, "/solicitudes/exportar")]');
        $this->assertSame(1, $links->length);
        $link = $links->item(0);
        $this->assertSame(route('admin.solicitudes.exportar', ['estado' => 'todas']), $link->getAttribute('href'));
        $this->assertSame('Exportar a Excel', trim($link->textContent));
        $this->assertStringContainsString('bg-green-600', $link->getAttribute('class'));
    }

    private function download(array $query = [])
    {
        $response = $this->withoutMiddleware()->get(route('admin.solicitudes.exportar', $query));
        $response->assertOk();
        $this->assertStringContainsString('attachment; filename=solicitudes_todas.xlsx', $response->headers->get('Content-Disposition'));
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $this->assertSame('PK', file_get_contents($path, false, null, 0, 2));
            Cell::setValueBinder(new DefaultValueBinder());

            return (new Xlsx())->load($path);
        } finally {
            unlink($path);
        }
    }
}
