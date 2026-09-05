<?php

use App\Http\Controllers\Api\Mobile\MobileAuthController;
use App\Http\Controllers\Api\Mobile\MobileRouteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::prefix('mobile')->middleware('throttle:api')->group(function (): void {
    Route::post('/auth/login', [MobileAuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::post('/auth/logout', [MobileAuthController::class, 'logout']);
        Route::get('/routes/assigned', [MobileRouteController::class, 'assigned']);
        Route::get('/routes/{distributionRoute}', [MobileRouteController::class, 'show']);
        Route::post('/routes/{distributionRoute}/start', [MobileRouteController::class, 'start']);
        Route::post('/routes/{distributionRoute}/locations', [MobileRouteController::class, 'location']);
        Route::post('/routes/{distributionRoute}/deliveries/{schedule}/confirm', [MobileRouteController::class, 'confirmDelivery']);
    });
});
