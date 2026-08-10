<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Internal\MedicalUnitCatalogController;
use App\Http\Controllers\Api\Internal\MixturePrevalidationController;
use App\Http\Controllers\Api\Internal\ExternalMixtureRequestController;
use App\Http\Controllers\Api\Internal\ExternalMixtureDocumentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('internal/v1')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('/medical-units/{externalCode}/catalogs/npt', [MedicalUnitCatalogController::class, 'npt']);
        Route::get('/medical-units/{externalCode}/catalogs/oncology', [MedicalUnitCatalogController::class, 'oncology']);
        Route::post('/mixture-requests/prevalidate', MixturePrevalidationController::class);
        Route::post('/mixture-requests', [ExternalMixtureRequestController::class, 'store']);
        Route::get('/mixture-requests/{remoteRequestId}', [ExternalMixtureRequestController::class, 'show']);
        Route::get('/mixture-requests/{remoteRequestId}/remission', [ExternalMixtureRequestController::class, 'remission']);
        Route::post('/mixture-requests/{remoteRequestId}/documents', [ExternalMixtureDocumentController::class, 'store']);
        Route::get('/mixture-requests/{remoteRequestId}/documents/{document}', [ExternalMixtureDocumentController::class, 'show']);
    });
