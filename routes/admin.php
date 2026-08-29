<?php

use App\Http\Controllers\Admin\HospitalController;
use App\Http\Controllers\Admin\InputController;
use App\Http\Controllers\Admin\CatalogoListasController;
use App\Http\Controllers\Admin\CatalogProductController;
use App\Http\Controllers\Admin\Nutricionales\MedicineController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\Nutricionales\SolicitudController;
use App\Http\Controllers\Admin\UnifiedSolicitudController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\TrainingPersonnelController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\DistributionController;
use App\Http\Controllers\Admin\DistributionDeliveryController;
use App\Http\Controllers\Admin\DistributionRouteController;
use App\Http\Controllers\Admin\SuperAdministratorController;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Route; //Importamos para generar nuestras rutas.
use App\Exports\SolicitudesExport;
use App\Http\Controllers\Admin\InstitucionController;
use App\Http\Controllers\Admin\InstitucionBillingController;
use App\Http\Controllers\Admin\InstitutionReportTemplateController;
use App\Http\Controllers\Admin\Nutricionales\NutriMedicineListController;
use App\Http\Controllers\Admin\Nutricionales\NutritionStockController;
use App\Http\Controllers\Admin\Oncologicos\DiluentController;
use App\Http\Controllers\Admin\Oncologicos\DiluentPresentationController;
use App\Http\Controllers\Admin\Oncologicos\InfusorController;
use App\Http\Controllers\Admin\Oncologicos\InventoryController;
use App\Http\Controllers\Admin\Oncologicos\LaboratoryController;
use App\Http\Controllers\Admin\Oncologicos\LaboratoryPurchaseOrderController;
use App\Http\Controllers\Admin\Oncologicos\MedicineCatalogController;
use App\Http\Controllers\Admin\Oncologicos\MedicineController as OncologicosMedicineController;
use App\Http\Controllers\Admin\Oncologicos\MedicinePresentationController;
use App\Http\Controllers\Admin\Oncologicos\MezclaController;
use App\Http\Controllers\Admin\Oncologicos\SolicitudController as OncologicosSolicitudController;
use Maatwebsite\Excel\Facades\Excel;

//Debemos avisarle a laravel que hemos creado un nuevo archivo de rutas en providers
Route::get('/dashboard', function () {
    //     session()->flash('swal', [
    //         'icon'=>"error",
    //         'title'=>"Oops...",
    //         'text'=>"Something went wrong :(!",
    //         'footer'=>'<a href="#">Why do I have this issue?</a>'
    // ]);
    return view('admin.dashboard');
})->name('dashboard');

Route::get('solicitudes', [UnifiedSolicitudController::class, 'index'])
    ->name('solicitudes.index');


Route::get('nutricionales/solicitudes/exportar', [SolicitudController::class, 'exportarExcel'])
    ->name('nutricionales.solicitudes.exportar')
    ->middleware(['can:nutricionales_solicitudes_index']);


Route::patch('/users/{user}/username', [UserController::class, 'updateUsername'])
    ->name('users.username.update')
    ->middleware(['can:usuarios']);

Route::patch('/users/{user}/password', [UserController::class, 'updatePassword'])
    ->name('users.password.update')
    ->middleware(['can:usuarios']);

Route::patch('/users/{user}/training-username', [UserController::class, 'updateTrainingUsername'])
    ->name('users.training-username.update')
    ->middleware(['can:usuarios']);

Route::patch('/users/{user}/training-password', [UserController::class, 'updateTrainingPassword'])
    ->name('users.training-password.update')
    ->middleware(['can:usuarios']);

Route::patch('/users/{user}/role-access', [UserController::class, 'updateRoleAccess'])
    ->name('users.role-access.update')
    ->middleware(['role:Super Admin']);

Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])
    ->name('users.status.update')
    ->middleware(['can:usuarios']);

Route::patch('/users/hospitals/{hospital}/status', [UserController::class, 'updateHospitalStatus'])
    ->name('users.hospitals.status.update')
    ->middleware(['can:usuarios']);

Route::patch('/users/institutions/{institucion}/status', [UserController::class, 'updateInstitutionStatus'])
    ->name('users.institutions.status.update')
    ->middleware(['can:usuarios']);

Route::resource('/users', UserController::class)
    ->except(['show'])
    ->middleware(['can:usuarios']);

Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])
    ->name('users.deactivate')
    ->middleware(['can:usuarios']);

Route::prefix('distribucion')
    ->name('distribution.')
    ->middleware(['can:menu.distribucion'])
    ->group(function () {
        Route::get('/catalogo-rutas', [DistributionRouteController::class, 'index'])->name('routes.index');
        Route::get('/catalogo-rutas/crear', [DistributionRouteController::class, 'create'])->name('routes.create');
        Route::post('/catalogo-rutas', [DistributionRouteController::class, 'store'])->name('routes.store');
        Route::get('/catalogo-rutas/{distributionRoute}/editar', [DistributionRouteController::class, 'edit'])->name('routes.edit');
        Route::patch('/catalogo-rutas/{distributionRoute}', [DistributionRouteController::class, 'update'])->name('routes.update');
        Route::delete('/catalogo-rutas/{distributionRoute}', [DistributionRouteController::class, 'destroy'])
            ->name('routes.destroy')
            ->middleware(['role:Super Admin']);
        Route::get('/catalogo-rutas/{distributionRoute}/qr', [DistributionRouteController::class, 'qr'])->name('routes.qr');
        Route::get('/catalogo-mensajeros', [DistributionRouteController::class, 'messengers'])->name('messengers.index');
        Route::get('/programacion-entregas', [DistributionDeliveryController::class, 'index'])->name('deliveries.index');
        Route::post('/programacion-entregas', [DistributionDeliveryController::class, 'store'])->name('deliveries.store');
        Route::patch('/programacion-entregas/mandar-a-ruta', [DistributionDeliveryController::class, 'send'])->name('deliveries.send');
        Route::redirect('/rutas', '/admin/distribucion/catalogo-rutas')->name('routes.legacy');
    });

Route::prefix('superadministrador')
    ->name('superadministrator.')
    ->middleware(['role:Super Admin'])
    ->group(function () {
        Route::get('/', [SuperAdministratorController::class, 'index'])->name('index');
        Route::patch('/administradores/{administrator}/destituir', [SuperAdministratorController::class, 'dismiss'])
            ->name('administrators.dismiss');
        Route::patch('/personal/{personnel}/nombrar', [SuperAdministratorController::class, 'appoint'])
            ->name('personnel.appoint');
    });

Route::resource('/roles', RoleController::class)
    ->except('show')
    ->middleware(['can:roles']);

Route::resource('/permissions', PermissionController::class)
    ->except('show')
    ->middleware(['can:permisos']);

Route::resource('/suppliers', SupplierController::class)
    ->except(['destroy'])
    ->middleware(['role_or_permission:Super Admin|menu.proveedores|oncologicos_laboratory_index']);

Route::prefix('/distribucion')
    ->name('distribution.')
    ->middleware(['role_or_permission:Super Admin|menu.distribucion|laboratorios'])
    ->group(function () {
        Route::get('/', [DistributionController::class, 'index'])->name('index');
        Route::get('/mensajeros', [DistributionController::class, 'couriers'])->name('couriers');
        Route::get('/nueva', [DistributionController::class, 'create'])->name('create');
        Route::post('/', [DistributionController::class, 'store'])->name('store');
        Route::get('/{distributionRoute}', [DistributionController::class, 'show'])->name('show');
        Route::get('/{distributionRoute}/editar', [DistributionController::class, 'edit'])->name('edit');
        Route::put('/{distributionRoute}', [DistributionController::class, 'update'])->name('update');
        Route::patch('/{distributionRoute}/estatus', [DistributionController::class, 'updateStatus'])->name('status.update');
        Route::get('/{distributionRoute}/qr', [DistributionController::class, 'qr'])->name('qr');
    });


Route::resource('/hospitals', HospitalController::class)
    ->only(['index', 'edit', 'update'])
    ->middleware(['can:hospitales']);

Route::patch('/hospitals/{hospital}/status', [HospitalController::class, 'toggleStatus'])
    ->name('hospitals.toggle-status')
    ->middleware(['can:hospitales']);

Route::resource('nutricionales/medicines', MedicineController::class)
    ->except(['show', 'destroy'])
    ->middleware(['can:medicamentos_nutricionales'])
    ->names('nutricionales.medicines');

Route::resource('nutricionales/inputs', InputController::class)
    ->except(['show'])
    ->middleware(['can:medicamentos_nutricionales'])
    ->names('nutricionales.inputs');

Route::prefix('catalogo-listas')
    ->name('catalogo-listas.')
    ->group(function () {
        Route::get('/', [CatalogoListasController::class, 'index'])->name('index');
        Route::get('{category}/productos/nuevo', [CatalogProductController::class, 'create'])->name('products.create');
        Route::post('{category}/productos', [CatalogProductController::class, 'store'])->name('products.store');
        Route::get('{category}/catalogo', [CatalogoListasController::class, 'catalog'])->name('catalog');
        Route::get('{category}/listas', [CatalogoListasController::class, 'lists'])->name('lists');
        Route::get('{category}/listas/nueva', [CatalogoListasController::class, 'createList'])->name('lists.create');
        Route::post('listas/unificadas', [CatalogoListasController::class, 'storeUnifiedList'])->name('lists.store-unified');
        Route::get('{category}/listas/respaldo/nueva', [CatalogoListasController::class, 'createBackupList'])->name('backup-lists.create');
        Route::get('{category}/listas/{list}/editar', [CatalogoListasController::class, 'editList'])->name('lists.edit');
        Route::post('{category}/listas/{list}/cargos', [CatalogoListasController::class, 'storeAdditionalCharge'])
            ->name('lists.additional-charges.store');
        Route::put('{category}/listas/{list}/cargos/{charge}', [CatalogoListasController::class, 'updateAdditionalCharge'])->name('lists.additional-charges.update');
        Route::delete('{category}/listas/{list}/cargos/{charge}', [CatalogoListasController::class, 'destroyAdditionalCharge'])->name('lists.additional-charges.destroy');
        Route::get('{category}/listas/{list}', [CatalogoListasController::class, 'showList'])->name('lists.show');
    });

Route::get('hospitals/{hospital}/reporte-mezclas-onco', [HospitalController::class, 'exportarMezclasOnco'])
    ->name('hospitals.exportarMezclasOnco')
    ->middleware(['can:hospitales']);

// Route::resource('solicitudes', SolicitudController::class)->parameter('solicitudes', 'solicitud')->except(['destroy'])
//     ->middleware(['can:solicitudes']);

// Ruta para mostrar todas las solicitudes
Route::get('nutricionales/solicitudes', [SolicitudController::class, 'index'])->name('nutricionales.solicitudes.index')
    ->middleware(['can:nutricionales_solicitudes_index']);
// Ruta para mostrar el formulario de creación de solicitud
Route::get('nutricionales/solicitudes/create', [SolicitudController::class, 'create'])->name('nutricionales.solicitudes.create')
    ->middleware(['can:nutricionales_solicitudes_create']);

// Ruta para almacenar una nueva solicitud
Route::post('nutricionales/solicitudes', [SolicitudController::class, 'store'])->name('nutricionales.solicitudes.store')
    ->middleware(['can:nutricionales_solicitudes_store']);

// Ruta para mostrar una solicitud específica
Route::get('nutricionales/solicitudes/{solicitud}', [SolicitudController::class, 'show'])->name('nutricionales.solicitudes.show')
    ->middleware(['can:nutricionales_solicitudes_show']);

// Ruta para mostrar el formulario de edición de una solicitud
Route::get('nutricionales/solicitudes/{solicitud}/edit', [SolicitudController::class, 'edit'])->name('nutricionales.solicitudes.edit')
    ->middleware(['can:nutricionales_solicitudes_edit']);

// Ruta para actualizar una solicitud específica
Route::put('nutricionales/solicitudes/{solicitud}', [SolicitudController::class, 'update'])->name('nutricionales.solicitudes.update')
    ->middleware(['can:nutricionales_solicitudes_update']);

// // Ruta para eliminar una solicitud específica
// Route::delete('nutricionales/solicitudes/{solicitud}', [SolicitudController::class, 'destroy'])->name('nutricionales.solicitudes.destroy')
// ->middleware(['can:solicitudes_destroy']);


// También puedes excluir la ruta de eliminación
// Route::resource('solicitudes', SolicitudController::class)->parameter('solicitudes', 'solicitud')->except(['destroy']);

Route::get('nutricionales/solicitudes/solicitud/{solicitud}', [SolicitudController::class, 'solicitud'])->name('nutricionales.solicitudes.solicitud')
    ->middleware(['can:nutricionales_solicitudes_index']);



Route::get('nutricionales/solicitudes/orden-de-preparacion/{solicitud}', [SolicitudController::class, 'ordenPreparacion'])->name('nutricionales.solicitudes.ordenPreparacion')
    ->middleware(['can:nutricionales_solicitudes_index']);

Route::get('nutricionales/solicitudes/remision/{solicitud}', [SolicitudController::class, 'remision'])->name('nutricionales.solicitudes.remision')
    ->middleware(['can:nutricionales_solicitudes_index']);

Route::get('nutricionales/solicitudes/envio/{solicitud}', [SolicitudController::class, 'envio'])->name('nutricionales.solicitudes.envio')
    ->middleware(['can:nutricionales_solicitudes_index']);

Route::get('nutricionales/solicitudes/etiqueta/{solicitud}', [SolicitudController::class, 'etiqueta'])->name('nutricionales.solicitudes.etiqueta')
    ->middleware(['can:nutricionales_solicitudes_index']);

Route::get('nutricionales/solicitudes/inspeccion/{solicitud}', [SolicitudController::class, 'inspeccion'])->name('nutricionales.solicitudes.inspeccion')
    ->middleware(['can:nutricionales_solicitudes_index']);

Route::resource('nutricionales/nutri-medicine-lists', NutriMedicineListController::class)
    ->names('nutricionales.nutri-medicine-lists');

Route::get('nutricionales/stocks/select-laboratory', [NutritionStockController::class, 'selectLaboratory'])
    ->name('nutricionales.stocks.selectLaboratory');

Route::get('nutricionales/stocks', [NutritionStockController::class, 'index'])
    ->name('nutricionales.stocks.index');

Route::get('nutricionales/stocks/exportar', [NutritionStockController::class, 'exportarExcel'])
    ->name('nutricionales.stocks.exportar');

Route::post('nutricionales/stocks/bulk-update', [NutritionStockController::class, 'bulkUpdate'])
    ->name('nutricionales.stocks.bulkUpdate');

Route::get('nutricionales/stocks/ingreso', [NutritionStockController::class, 'ingresoForm'])
    ->name('nutricionales.stocks.ingreso');

Route::post('nutricionales/stocks/ingreso', [NutritionStockController::class, 'registrarIngreso'])
    ->name('nutricionales.stocks.registrarIngreso');

Route::get('nutricionales/stocks/{stock}/edit', [NutritionStockController::class, 'edit'])
    ->name('nutricionales.stocks.edit');

Route::put('nutricionales/stocks/{stock}', [NutritionStockController::class, 'update'])
    ->name('nutricionales.stocks.update');

Route::post('nutricionales/stocks/{stock}/merge-duplicate', [NutritionStockController::class, 'mergeDuplicate'])
    ->name('nutricionales.stocks.mergeDuplicate');

Route::post('nutricionales/stocks/{stock}/deplete', [NutritionStockController::class, 'deplete'])
    ->name('nutricionales.stocks.deplete');


Route::get('nutricionales/stocks/{stock}/merma', [NutritionStockController::class, 'mermaForm'])
    ->name('nutricionales.stocks.merma');

Route::post('nutricionales/stocks/{stock}/merma', [NutritionStockController::class, 'registrarMerma'])
    ->name('nutricionales.stocks.registrarMerma');

Route::get('nutricionales/stocks/{stock}/movimientos', [NutritionStockController::class, 'movimientos'])
    ->name('nutricionales.stocks.movimientos');

Route::post('nutricionales/solicitudes/{solicitud}/preparar', [SolicitudController::class, 'preparar'])
    ->name('nutricionales.solicitudes.preparar');

Route::post('nutricionales/solicitudes/{solicitud}/revisar', [SolicitudController::class, 'revisar'])
    ->name('nutricionales.solicitudes.revisar');

Route::post('nutricionales/solicitudes/{solicitud}/entregar', [SolicitudController::class, 'entregar'])
    ->name('nutricionales.solicitudes.entregar');


Route::post(
    'nutricionales/stocks/save-active-presentations',
    [NutritionStockController::class, 'saveActivePresentations']
)->name('nutricionales.stocks.saveActivePresentations');


Route::post('nutricionales/solicitudes/{solicitud}/cancelar', [SolicitudController::class, 'cancelar'])
    ->name('nutricionales.solicitudes.cancelar');



// RUTAS PARA ONCOLOGICOS

Route::get('oncologicos/solicitudes/exportar', [OncologicosSolicitudController::class, 'exportarExcel'])
    ->name('oncologicos.solicitudes.exportar')
    ->middleware(['can:oncologicos_solicitudes_index']);

Route::get('oncologicos/solicitudes', [OncologicosSolicitudController::class, 'index'])->name('oncologicos.solicitudes.index')
    ->middleware(['can:oncologicos_solicitudes_index']);

Route::get('oncologicos/solicitudes/create', [OncologicosSolicitudController::class, 'create'])->name('oncologicos.solicitudes.create')
    ->middleware(['can:oncologicos_solicitudes_create']);

Route::post('oncologicos/solicitudes', [OncologicosSolicitudController::class, 'store'])->name('oncologicos.solicitudes.store')
    ->middleware(['can:oncologicos_solicitudes_store']);

Route::get('oncologicos/solicitudes/{id}', [OncologicosSolicitudController::class, 'show'])->name('oncologicos.solicitudes.show')
    ->middleware(['can:oncologicos_solicitudes_show']);

Route::get('oncologicos/solicitudes/{id}/edit', [OncologicosSolicitudController::class, 'edit'])->name('oncologicos.solicitudes.edit')
    ->middleware(['can:oncologicos_solicitudes_edit']);

Route::put('oncologicos/solicitudes/{id}', [OncologicosSolicitudController::class, 'update'])->name('oncologicos.solicitudes.update')
    ->middleware(['can:oncologicos_solicitudes_update']);

Route::post('/solicitudes/{solicitud}/cancelar', [OncologicosSolicitudController::class, 'cancelar'])->name('oncologicos.solicitudes.cancelar');

Route::get('antibioticos/solicitudes', [OncologicosSolicitudController::class, 'index'])
    ->defaults('request_type', 'antibioticos')
    ->name('antibioticos.solicitudes.index')
    ->middleware(['can:oncologicos_solicitudes_index']);

//RUTAS PARA MEZCLAS ONCOLOGICAS

Route::get('oncologicos/solicitudes/mezclas/{mezcla}', [MezclaController::class, 'index'])->name('oncologicos.mezclas.index')
    ->middleware(['can:oncologicos_mezclas_index']);
Route::get('oncologicos/mezclas/{mezcla}', [MezclaController::class, 'show'])->name('oncologicos.mezclas.show')
    ->middleware(['can:oncologicos_mezclas_show']);
Route::get('oncologicos/mezclas/{mezcla}/edit', [MezclaController::class, 'edit'])->name('oncologicos.mezclas.edit')
    ->middleware(['can:oncologicos_mezclas_edit']);
Route::put('oncologicos/mezclas/{mezcla}', [MezclaController::class, 'update'])->name('oncologicos.mezclas.update')
    ->middleware(['can:oncologicos_mezclas_update']);

// PDF para solicitud de mezcla oncologicas

Route::get('oncologicos/mezclas/orden-de-preparacion/{mezcla}', [MezclaController::class, 'ordenPreparacion'])->name('oncologicos.mezclas.ordenPreparacion')
    ->middleware(['can:oncologicos_mezclas_index']);

Route::get('oncologicos/mezclas/inspeccion/{mezcla}', [MezclaController::class, 'inspeccion'])->name('oncologicos.mezclas.inspeccion')
    ->middleware(['can:oncologicos_mezclas_index']);

Route::get('oncologicos/mezclas/etiqueta/{mezcla}', [MezclaController::class, 'etiqueta'])->name('oncologicos.mezclas.etiqueta')
    ->middleware(['can:oncologicos_mezclas_index']);

Route::get('oncologicos/mezclas/solicitud-completa/{solicitud}', [OncologicosSolicitudController::class, 'solicitud'])->name('oncologicos.mezclas.solicitudCompleta')
    ->middleware(['can:oncologicos_mezclas_index']);

Route::get('oncologicos/mezclas/envio/{solicitud}', [OncologicosSolicitudController::class, 'envio'])->name('oncologicos.mezclas.envio')
    ->middleware(['can:oncologicos_mezclas_index']);

Route::get('oncologicos/mezclas/remision/{solicitud}', [OncologicosSolicitudController::class, 'remision'])->name('oncologicos.mezclas.remision')
    ->middleware(['can:oncologicos_mezclas_index']);


//DILUENTS
// Listado
Route::get('oncologicos/diluents', [DiluentController::class, 'index'])
    ->name('oncologicos.diluents.index')
    ->middleware(['can:oncologicos_diluents_index']);

// Crear
Route::get('oncologicos/diluents/crear', [DiluentController::class, 'create'])
    ->name('oncologicos.diluents.create')
    ->middleware(['can:oncologicos_diluents_create']);

Route::post('oncologicos/diluents', [DiluentController::class, 'store'])
    ->name('oncologicos.diluents.store')
    ->middleware(['can:oncologicos_diluents_store']);
// Editar
Route::get('oncologicos/diluents/{diluent}/editar', [DiluentController::class, 'edit'])
    ->name('oncologicos.diluents.edit')
    ->middleware(['can:oncologicos_diluents_edit']);

Route::put('oncologicos/diluents/{diluent}', [DiluentController::class, 'update'])
    ->name('oncologicos.diluents.update')
    ->middleware(['can:oncologicos_diluents_update']);

// Eliminar
Route::delete('oncologicos/diluents/{diluent}', [DiluentController::class, 'destroy'])
    ->name('oncologicos.diluents.destroy')
    ->middleware(['can:oncologicos_diluents_destroy']);

//END DILUENTS

//SUBCRUD DE DILUYENTES
// LISTAR presentaciones de un diluyente
Route::get('oncologicos/diluents/{diluent}/presentaciones', [DiluentPresentationController::class, 'index'])
    ->name('oncologicos.diluent_presentations.index')
    ->middleware(['can:oncologicos_diluents_index']);

// CREAR
Route::get('oncologicos/diluents/{diluent}/presentaciones/crear', [DiluentPresentationController::class, 'create'])
    ->name('oncologicos.diluent_presentations.create')
    ->middleware(['can:oncologicos_diluents_create']);

Route::post('oncologicos/diluents/{diluent}/presentaciones', [DiluentPresentationController::class, 'store'])
    ->name('oncologicos.diluent_presentations.store')
    ->middleware(['can:oncologicos_diluents_store']);

// EDITAR
Route::get('oncologicos/diluents/{diluent}/presentaciones/{presentation}/editar', [DiluentPresentationController::class, 'edit'])
    ->name('oncologicos.diluent_presentations.edit')
    ->middleware(['can:oncologicos_diluents_edit']);

Route::put('oncologicos/diluents/{diluent}/presentaciones/{presentation}', [DiluentPresentationController::class, 'update'])
    ->name('oncologicos.diluent_presentations.update')
    ->middleware(['can:oncologicos_diluents_update']);

// ELIMINAR
Route::delete('oncologicos/diluents/{diluent}/presentaciones/{presentation}', [DiluentPresentationController::class, 'destroy'])
    ->name('oncologicos.diluent_presentations.destroy')
    ->middleware(['can:oncologicos_diluents_destroy']);

//END SUBCRUDDILUYENTES

Route::resource('oncologicos/medicines/catalog', MedicineCatalogController::class)
    ->middleware(['can:medicamentos_oncologicos'])
    ->names('oncologicos.medicines.catalog');


Route::resource('oncologicos/medicines', OncologicosMedicineController::class)
    ->except(['show'])
    ->middleware(['can:medicamentos_oncologicos'])
    ->names('oncologicos.medicines');

Route::get('oncologicos/medicines/{medicineList}/exportar', [OncologicosMedicineController::class, 'exportarExcel'])
    ->name('oncologicos.medicines.exportar')
    ->middleware(['can:medicamentos_oncologicos']);


// RUTAS PARA ONCOLÓGICOS / INFUSORES
Route::prefix('oncologicos')->name('oncologicos.')->group(function () {
    Route::resource('infusores', InfusorController::class)
        ->except(['show'])
        ->parameters(['infusores' => 'infusor']) // <-- fuerza {infusor}
        ->names('infusores'); // genera index, create, store, show, edit, update, destroy
});

//RUTAS PARA PRESENTACIONES

Route::patch(
    'oncologicos/medicines/catalog/{catalog}/presentations/{presentation}/habilitar',
    [MedicinePresentationController::class, 'restore']
)
    ->name('oncologicos.medicines.catalog.presentations.restore')
    ->middleware(['can:medicamentos_oncologicos']);

Route::resource('oncologicos/medicines/catalog.presentations', MedicinePresentationController::class)
    ->except(['show'])
    ->middleware(['can:medicamentos_oncologicos'])
    ->names('oncologicos.medicines.catalog.presentations');


Route::redirect('/clientes', '/admin/instituciones')
    ->name('legacy.clientes.index')
    ->middleware(['role:Super Admin']);

Route::redirect('/clientes/create', '/admin/instituciones/create')
    ->name('legacy.clientes.create')
    ->middleware(['role:Super Admin']);

Route::resource('/instituciones', InstitucionController::class)
    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
    ->parameters(['instituciones' => 'institucion'])
    ->middleware(['role_or_permission:Super Admin|menu.instituciones.list']);

$administrationReportsMiddleware = ['role_or_permission:Super Admin|Administracion y facturacion|menu.administracion.reports'];
$billingPendingMiddleware = ['role_or_permission:Super Admin|Administracion y facturacion|menu.facturacion.pending'];
$billingReceivableMiddleware = ['role_or_permission:Super Admin|Administracion y facturacion|menu.facturacion.receivable'];
$billingHistoryMiddleware = ['role_or_permission:Super Admin|Administracion y facturacion|menu.facturacion.history'];
$billingMovementsMiddleware = ['role_or_permission:Super Admin|Administracion y facturacion|menu.facturacion.movements'];

Route::get('instituciones-reportes', [InstitucionController::class, 'reportes'])
    ->name('instituciones.reportes')
    ->middleware($administrationReportsMiddleware);

Route::put('instituciones-reportes/formatos/{reportTemplate}', [InstitutionReportTemplateController::class, 'update'])
    ->name('instituciones.reportes.formatos.update')
    ->middleware($administrationReportsMiddleware);

Route::patch('instituciones-reportes/formatos/{reportTemplate}/nombre', [InstitutionReportTemplateController::class, 'rename'])
    ->name('instituciones.reportes.formatos.rename')
    ->middleware($administrationReportsMiddleware);

Route::get('instituciones-facturacion', [InstitucionBillingController::class, 'index'])
    ->name('instituciones.billing.index')
    ->middleware($billingPendingMiddleware);

Route::get('instituciones-facturacion/por-cobrar', [InstitucionBillingController::class, 'receivable'])
    ->name('instituciones.billing.receivable')
    ->middleware($billingReceivableMiddleware);

Route::get('instituciones-facturacion/historial', [InstitucionBillingController::class, 'history'])
    ->name('instituciones.billing.history')
    ->middleware($billingHistoryMiddleware);

Route::get('instituciones-facturacion/bitacora', [InstitucionBillingController::class, 'movementLog'])
    ->name('instituciones.billing.movements')
    ->middleware($billingMovementsMiddleware);

Route::get('instituciones-facturacion/exportar', [InstitucionBillingController::class, 'exportarExcel'])
    ->name('instituciones.billing.export')
    ->middleware($billingPendingMiddleware);

Route::post('instituciones-facturacion/exportar-ampliado', [InstitucionBillingController::class, 'exportarExcelAmpliado'])
    ->name('instituciones.billing.expanded-export')
    ->middleware($billingPendingMiddleware);

Route::post('instituciones-facturacion', [InstitucionBillingController::class, 'store'])
    ->name('instituciones.billing.store')
    ->middleware($billingPendingMiddleware);

Route::post('instituciones-facturacion/{billing}/mover', [InstitucionBillingController::class, 'moveFromHistory'])
    ->name('instituciones.billing.move')
    ->middleware($billingHistoryMiddleware);

Route::view('capacitaciones', 'admin.capacitaciones.index')
    ->name('capacitaciones.index');

Route::view('capacitaciones/programas', 'admin.capacitaciones.index')
    ->name('capacitaciones.programas');

Route::view('capacitaciones/alumnos', 'admin.capacitaciones.index')
    ->name('capacitaciones.alumnos');

Route::get('capacitaciones/personal', [TrainingPersonnelController::class, 'index'])
    ->name('capacitaciones.personal');

Route::post('capacitaciones/personal', [TrainingPersonnelController::class, 'store'])
    ->name('capacitaciones.personal.store');

Route::get('instituciones/{institucion}/hospitals', [InstitucionController::class, 'hospitales'])
    ->name('instituciones.hospitals')
    ->middleware(['role_or_permission:Super Admin|menu.instituciones.hospitals']);

Route::get('instituciones/{institucion}/hospitals/create', [HospitalController::class, 'createForInstitution'])
    ->name('instituciones.hospitals.create')
    ->middleware(['role_or_permission:Super Admin|menu.instituciones.hospitals']);

Route::post('instituciones/{institucion}/hospitals', [HospitalController::class, 'storeForInstitution'])
    ->name('instituciones.hospitals.store')
    ->middleware(['role_or_permission:Super Admin|menu.instituciones.hospitals']);

Route::put('instituciones/{institucion}/hospitals', [InstitucionController::class, 'actualizarHospitales'])
    ->name('instituciones.hospitals.update')
    ->middleware(['role_or_permission:Super Admin|menu.instituciones.hospitals']);

Route::get('clientes/{cliente}/edit', function ($cliente) {
    return redirect()->route('admin.instituciones.edit', ['institucion' => $cliente]);
})->name('legacy.clientes.edit')->middleware(['role:Super Admin']);

Route::get('instituciones/{institucion}/exportar-mezclas-onco', [InstitucionController::class, 'exportarMezclasOnco'])
    ->name('instituciones.exportarMezclasOnco')
    ->middleware($administrationReportsMiddleware);

Route::get('instituciones/{institucion}/exportar-general', [InstitucionController::class, 'exportarReporteGeneral'])
    ->name('instituciones.exportarGeneral')
    ->middleware($administrationReportsMiddleware);

Route::get('instituciones/{institucion}/exportar-hospital', [InstitucionController::class, 'exportarReporteHospital'])
    ->name('instituciones.exportarHospital')
    ->middleware($administrationReportsMiddleware);

Route::get('instituciones/{institucion}/exportar-hospital-detalle', [InstitucionController::class, 'exportarReporteHospitalDetalle'])
    ->name('instituciones.exportarHospitalDetalle')
    ->middleware($administrationReportsMiddleware);

Route::get('instituciones/{institucion}/exportar-reporte-diario-paciente', [InstitucionController::class, 'exportarReporteDiarioPaciente'])
    ->name('instituciones.exportarReporteDiarioPaciente')
    ->middleware($administrationReportsMiddleware);

Route::get('instituciones/{institucion}/exportar-reporte-mensual-insumos', [InstitucionController::class, 'exportarReporteMensualInsumos'])
    ->name('instituciones.exportarReporteMensualInsumos')
    ->middleware($administrationReportsMiddleware);

Route::get('clientes/{cliente}/exportar-mezclas-onco', function ($cliente) {
    return redirect()->route('admin.instituciones.exportarMezclasOnco', ['institucion' => $cliente]);
})->name('legacy.clientes.exportar-mezclas-onco')->middleware(['role:Super Admin']);


// ===============================
// INVENTARIO GLOBAL ONCOLÓGICO
// ===============================

Route::prefix('oncologicos/inventory')
    ->name('oncologicos.inventory.')
    ->middleware(['can:medicamentos_oncologicos'])
    ->group(function () {

        Route::get('/', [InventoryController::class, 'index'])
            ->name('index');

        Route::get('/ingreso', [InventoryController::class, 'ingresoForm'])
            ->name('ingresoForm');

        Route::post('/ingreso', [InventoryController::class, 'registrarIngreso'])
            ->name('registrarIngreso');

        Route::get('/exportar', [InventoryController::class, 'exportarExcel'])
            ->name('exportar');

        Route::get('/lotes/{batch}/movimientos', [InventoryController::class, 'movimientos'])
            ->name('movimientos');

        Route::get('/lotes/{batch}/editar', [InventoryController::class, 'editBatch'])
            ->name('editBatch');

        Route::put('/lotes/{batch}', [InventoryController::class, 'updateBatch'])
            ->name('updateBatch');

        Route::get('/lotes/{batch}/merma', [InventoryController::class, 'mermaForm'])
            ->name('merma');

        Route::post('/lotes/{batch}/merma', [InventoryController::class, 'registrarMerma'])
            ->name('registrarMerma');

        Route::post('/lotes/{batch}/remanente/merma', [InventoryController::class, 'descartarRemanente'])
            ->name('descartarRemanente');

        Route::get('/select-laboratory', [InventoryController::class, 'selectLaboratory'])
            ->name('selectLaboratory');

        Route::post('/select-laboratory', [InventoryController::class, 'setLaboratory'])
            ->name('setLaboratory');
    });

// ===============================
// LABORATORIOS (SUCURSALES)
// ===============================

// Listado
Route::get('/oncologicos/laboratory', [LaboratoryController::class, 'index'])
    ->name('oncologicos.laboratory.index')
    ->middleware(['can:oncologicos_laboratory_index']);

Route::get('/almacenes', [WarehouseController::class, 'index'])
    ->name('warehouses.index')
    ->middleware(['can:oncologicos_laboratory_index']);

Route::get('/almacenes/crear', [WarehouseController::class, 'create'])
    ->name('warehouses.create')
    ->middleware(['can:oncologicos_laboratory_create']);

Route::post('/almacenes', [WarehouseController::class, 'store'])
    ->name('warehouses.store')
    ->middleware(['can:oncologicos_laboratory_create']);

Route::get('/almacenes/{warehouse}/editar', [WarehouseController::class, 'edit'])
    ->name('warehouses.edit')
    ->middleware(['can:oncologicos_laboratory_edit']);

Route::put('/almacenes/{warehouse}', [WarehouseController::class, 'update'])
    ->name('warehouses.update')
    ->middleware(['can:oncologicos_laboratory_update']);

Route::delete('/almacenes/{warehouse}', [WarehouseController::class, 'destroy'])
    ->name('warehouses.destroy')
    ->middleware(['can:oncologicos_laboratory_destroy']);

Route::get('/almacenes/ordenes-de-compra', [WarehouseController::class, 'purchaseOrders'])
    ->name('warehouses.purchase-orders.index')
    ->middleware(['can:oncologicos_laboratory_index']);

Route::get('/compras/nueva', [WarehouseController::class, 'newPurchaseOrder'])
    ->name('purchases.create')
    ->middleware(['can:oncologicos_laboratory_index']);

Route::get('/almacenes/{warehouse}/insumos', [WarehouseController::class, 'suppliesInventory'])
    ->name('warehouses.supplies.index')
    ->middleware(['can:oncologicos_laboratory_index']);

// Crear
Route::get('/oncologicos/laboratory/crear', [LaboratoryController::class, 'create'])
    ->name('oncologicos.laboratory.create')
    ->middleware(['can:oncologicos_laboratory_create']);

Route::post('/oncologicos/laboratory', [LaboratoryController::class, 'store'])
    ->name('oncologicos.laboratory.store')
    ->middleware(['can:oncologicos_laboratory_store']);

Route::get('/oncologicos/laboratory/{laboratory}/ordenes-de-compra/nueva', [LaboratoryPurchaseOrderController::class, 'create'])
    ->name('oncologicos.laboratory.purchase-orders.create')
    ->middleware(['can:oncologicos_laboratory_index']);

Route::post('/oncologicos/laboratory/{laboratory}/ordenes-de-compra', [LaboratoryPurchaseOrderController::class, 'store'])
    ->name('oncologicos.laboratory.purchase-orders.store')
    ->middleware(['can:oncologicos_laboratory_index']);

Route::get('/oncologicos/laboratory/{laboratory}/ordenes-de-compra/{purchaseOrder}/descargar', [LaboratoryPurchaseOrderController::class, 'download'])
    ->name('oncologicos.laboratory.purchase-orders.download')
    ->middleware(['can:oncologicos_laboratory_index']);

// Editar
Route::get('/oncologicos/laboratory/{laboratory}/editar', [LaboratoryController::class, 'edit'])
    ->name('oncologicos.laboratory.edit')
    ->middleware(['can:oncologicos_laboratory_edit']);

Route::put('/oncologicos/laboratory/{laboratory}', [LaboratoryController::class, 'update'])
    ->name('oncologicos.laboratory.update')
    ->middleware(['can:oncologicos_laboratory_update']);

// Eliminar
Route::delete('/oncologicos/laboratory/{laboratory}', [LaboratoryController::class, 'destroy'])
    ->name('oncologicos.laboratory.destroy')
    ->middleware(['can:oncologicos_laboratory_destroy']);
