<?php

use App\Http\Controllers\Admin\HospitalController;
use App\Http\Controllers\Admin\InputController;
use App\Http\Controllers\Admin\Nutricionales\MedicineController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\Nutricionales\SolicitudController;
use App\Http\Controllers\Admin\UserController;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Route; //Importamos para generar nuestras rutas.
use App\Exports\SolicitudesExport;
use App\Http\Controllers\Admin\InstitucionController;
use App\Http\Controllers\Admin\InstitucionBillingController;
use App\Http\Controllers\Admin\Nutricionales\NutriMedicineListController;
use App\Http\Controllers\Admin\Nutricionales\NutritionStockController;
use App\Http\Controllers\Admin\Oncologicos\DiluentController;
use App\Http\Controllers\Admin\Oncologicos\DiluentPresentationController;
use App\Http\Controllers\Admin\Oncologicos\InfusorController;
use App\Http\Controllers\Admin\Oncologicos\InventoryController;
use App\Http\Controllers\Admin\Oncologicos\LaboratoryController;
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


Route::get('nutricionales/solicitudes/exportar', [SolicitudController::class, 'exportarExcel'])
    ->name('nutricionales.solicitudes.exportar')
    ->middleware(['can:nutricionales_solicitudes_index']);


Route::resource('/users', UserController::class)
    ->middleware(['can:usuarios']);

Route::resource('/roles', RoleController::class)
    ->except('show')
    ->middleware(['can:roles']);

Route::resource('/permissions', PermissionController::class)
    ->except('show')
    ->middleware(['can:permisos']);


Route::resource('/hospitals', HospitalController::class)
    ->except(['show', 'destroy'])
    ->middleware(['can:hospitales']);

Route::resource('nutricionales/medicines', MedicineController::class)
    ->except(['destroy'])
    ->middleware(['can:medicamentos_nutricionales'])
    ->names('nutricionales.medicines');

Route::resource('nutricionales/inputs', InputController::class)
    ->except(['show'])
    ->middleware(['can:medicamentos_nutricionales'])
    ->names('nutricionales.inputs');

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

//RUTAS PARA MEZCLAS ONCOLOGICAS

Route::get('oncologicos/solicitudes/mezclas/{mezcla}', [MezclaController::class, 'index'])->name('oncologicos.mezclas.index')
    ->middleware(['can:oncologicos_mezclas_index']);
Route::get('oncologicos/mezclas/create', [MezclaController::class, 'create'])->name('oncologicos.mezclas.create')
    ->middleware(['can:oncologicos_mezclas_create']);
Route::get('oncologicos/mezclas/{mezcla}', [MezclaController::class, 'show'])->name('oncologicos.mezclas.show')
    ->middleware(['can:oncologicos_mezclas_show']);
Route::post('oncologicos/mezclas', [MezclaController::class, 'store'])->name('oncologicos.mezclas.store')
    ->middleware(['can:oncologicos_mezclas_store']);
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
    ->middleware(['can:medicamentos_oncologicos'])
    ->names('oncologicos.medicines.catalog.presentations');


Route::redirect('/clientes', '/admin/instituciones')
    ->middleware(['role:Super Admin']);

Route::redirect('/clientes/create', '/admin/instituciones/create')
    ->middleware(['role:Super Admin']);

Route::resource('/instituciones', InstitucionController::class)
    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
    ->parameters(['instituciones' => 'institucion'])
    ->middleware(['role:Super Admin']);

Route::get('instituciones-reportes', [InstitucionController::class, 'reportes'])
    ->name('instituciones.reportes')
    ->middleware(['role:Super Admin']);

Route::get('instituciones-facturacion', [InstitucionBillingController::class, 'index'])
    ->name('instituciones.billing.index')
    ->middleware(['role:Super Admin']);

Route::get('instituciones-facturacion/exportar', [InstitucionBillingController::class, 'exportarExcel'])
    ->name('instituciones.billing.export')
    ->middleware(['role:Super Admin']);

Route::post('instituciones-facturacion', [InstitucionBillingController::class, 'store'])
    ->name('instituciones.billing.store')
    ->middleware(['role:Super Admin']);

Route::get('instituciones/{institucion}/hospitals', [InstitucionController::class, 'hospitales'])
    ->name('instituciones.hospitals')
    ->middleware(['role:Super Admin']);

Route::post('instituciones/{institucion}/hospitals', [InstitucionController::class, 'storeHospital'])
    ->name('instituciones.hospitals.store')
    ->middleware(['role:Super Admin']);

Route::put('instituciones/{institucion}/hospitals', [InstitucionController::class, 'actualizarHospitales'])
    ->name('instituciones.hospitals.update')
    ->middleware(['role:Super Admin']);

Route::get('clientes/{cliente}/edit', function ($cliente) {
    return redirect()->route('admin.instituciones.edit', ['institucion' => $cliente]);
})->middleware(['role:Super Admin']);

Route::get('instituciones/{institucion}/exportar-mezclas-onco', [InstitucionController::class, 'exportarMezclasOnco'])
    ->name('instituciones.exportarMezclasOnco')
    ->middleware(['role:Super Admin']);

Route::get('instituciones/{institucion}/exportar-general', [InstitucionController::class, 'exportarReporteGeneral'])
    ->name('instituciones.exportarGeneral')
    ->middleware(['role:Super Admin']);

Route::get('instituciones/{institucion}/exportar-hospital', [InstitucionController::class, 'exportarReporteHospital'])
    ->name('instituciones.exportarHospital')
    ->middleware(['role:Super Admin']);

Route::get('instituciones/{institucion}/exportar-hospital-detalle', [InstitucionController::class, 'exportarReporteHospitalDetalle'])
    ->name('instituciones.exportarHospitalDetalle')
    ->middleware(['role:Super Admin']);

Route::get('clientes/{cliente}/exportar-mezclas-onco', function ($cliente) {
    return redirect()->route('admin.instituciones.exportarMezclasOnco', ['institucion' => $cliente]);
})->middleware(['role:Super Admin']);


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

// Crear
Route::get('/oncologicos/laboratory/crear', [LaboratoryController::class, 'create'])
    ->name('oncologicos.laboratory.create')
    ->middleware(['can:oncologicos_laboratory_create']);

Route::post('/oncologicos/laboratory', [LaboratoryController::class, 'store'])
    ->name('oncologicos.laboratory.store')
    ->middleware(['can:oncologicos_laboratory_store']);

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
