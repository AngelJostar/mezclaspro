<?php

use App\Http\Controllers\Api\Mobile\MobileAuthController;
use App\Http\Controllers\Api\Mobile\MobileHospitalController;
use App\Http\Controllers\Api\Mobile\MobileNotificationController;
use App\Http\Controllers\Api\Mobile\MobileRouteController;
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

Route::prefix('mobile')->middleware('throttle:api')->group(function (): void {
    Route::post('/auth/login', [MobileAuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::get('/quotations/{quotation}/workflow', [\App\Http\Controllers\Api\Mobile\MobileQuotationWorkflowController::class, 'show'])->whereNumber('quotation');
        Route::post('/quotations/{quotation}/authorize', [\App\Http\Controllers\Admin\RequestQuotationController::class, 'authorizeQuotation'])->whereNumber('quotation');
        Route::get('/sales/clients', [\App\Http\Controllers\Api\Mobile\MobileSalesController::class, 'clients']);
        Route::get('/sales/quotations', [\App\Http\Controllers\Api\Mobile\MobileSalesController::class, 'quotations']);
        Route::get('/sales/quotations/{quotation}', [\App\Http\Controllers\Api\Mobile\MobileSalesController::class, 'show']);
        Route::prefix('sales')->middleware(\App\Http\Middleware\EnsureMobileSalesAccess::class)->group(function (): void {
            $controller = \App\Http\Controllers\Admin\RequestQuotationController::class;
            Route::get('/catalog', [$controller, 'options']);
            Route::get('/hospital-requests', [\App\Http\Controllers\Api\Mobile\MobileSalesController::class, 'hospitalRequests']);
            Route::get('/hospital-requests/{hospitalRequest}/attachment', [\App\Http\Controllers\Api\Mobile\MobileSalesController::class, 'requestAttachment']);
            Route::post('/quotations/preview', [$controller, 'preview']);
            Route::post('/quotations', [$controller, 'store']);
            Route::put('/quotations/{quotation}', [$controller, 'update']);
            Route::get('/quotations/{quotation}/pdf', [$controller, 'pdf']);
            Route::post('/quotations/{quotation}/email', [$controller, 'email'])->middleware('throttle:10,1');
            Route::get('/quotations/{quotation}/documents', [\App\Http\Controllers\Admin\RequestQuotationDocumentController::class, 'index']);
            Route::post('/quotations/{quotation}/documents', [\App\Http\Controllers\Admin\RequestQuotationDocumentController::class, 'store']);
            Route::get('/deliveries', [\App\Http\Controllers\Api\Mobile\MobileSalesController::class, 'deliveries']);
            Route::get('/deliveries/tracking', [\App\Http\Controllers\Api\Mobile\MobileDeliveryTrackingController::class, 'index']);
            Route::get('/deliveries/{schedule}/tracking', [\App\Http\Controllers\Api\Mobile\MobileDeliveryTrackingController::class, 'show'])->whereNumber('schedule');
        });
        Route::post('/auth/logout', [MobileAuthController::class, 'logout']);
        Route::get('/hospital/dashboard', [MobileHospitalController::class, 'dashboard']);
        Route::get('/hospital/deliveries', [\App\Http\Controllers\Api\Mobile\MobileDeliveryTrackingController::class, 'index']);
        Route::get('/hospital/deliveries/{schedule}', [\App\Http\Controllers\Api\Mobile\MobileDeliveryTrackingController::class, 'show'])->whereNumber('schedule');
        Route::get('/hospital/catalog', [MobileHospitalController::class, 'catalog']);
        Route::get('/hospital/quotation-wizard/catalog', [MobileHospitalController::class, 'wizardCatalog']);
        Route::post('/hospital/quotation-wizard/preview', [MobileHospitalController::class, 'wizardPreview']);
        Route::post('/hospital/quotation-wizard/requests', [MobileHospitalController::class, 'wizardStore']);
        Route::post('/hospital/quotation-wizard/requests/{hospitalRequest}/attachment', [MobileHospitalController::class, 'wizardAttachment'])->whereNumber('hospitalRequest');
        Route::get('/hospital/quotation-requests', [MobileHospitalController::class, 'quotationRequests']);
        Route::post('/hospital/quotation-requests', [MobileHospitalController::class, 'storeQuotationRequest']);
        Route::get('/hospital/quotations', [MobileHospitalController::class, 'quotations']);
        Route::get('/hospital/quotations/{quotation}', [MobileHospitalController::class, 'quotation'])->whereNumber('quotation');
        Route::get('/hospital/orders', [MobileHospitalController::class, 'orders']);
        Route::get('/hospital/orders/{type}/{id}', [MobileHospitalController::class, 'order'])->whereIn('type', ['oncologicos', 'antibioticos', 'nutricionales'])->whereNumber('id');
        Route::get('/notifications', [MobileNotificationController::class, 'index']);
        Route::post('/notifications/read-all', [MobileNotificationController::class, 'readAll']);
        Route::get('/routes/assigned', [MobileRouteController::class, 'assigned']);
        Route::get('/routes/available', [MobileRouteController::class, 'available']);
        Route::get('/routes/catalog', [MobileRouteController::class, 'catalog']);
        Route::get('/routes/history', [MobileRouteController::class, 'history']);
        Route::get('/routes/{distributionRoute}', [MobileRouteController::class, 'show']);
        Route::post('/routes/{distributionRoute}/accept', [MobileRouteController::class, 'accept']);
        Route::post('/routes/{distributionRoute}/release', [MobileRouteController::class, 'release']);
        Route::post('/routes/{distributionRoute}/start', [MobileRouteController::class, 'start']);
        Route::post('/routes/{distributionRoute}/locations', [MobileRouteController::class, 'location']);
        Route::post('/routes/{distributionRoute}/deliveries/{schedule}/confirm', [MobileRouteController::class, 'confirmDelivery']);
    });
});
