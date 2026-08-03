<?php


use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;

/*Definimos todas las rutas del uruario en comun */
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/aviso-de-privacidad', function () {
    return view('aviso-de-privacidad');
});


// Route::middleware([
//     'auth:sanctum',
//     config('jetstream.auth_session'),
//     'verified',
// ])->group(function () {
//     Route::get('/dashboard', function () {
//         return view('dashboard');
//     })->name('dashboard');
// });

Route::get('/pdf', function () {
    $pdf = App::make('dompdf.wrapper');
    $pdf->loadHTML('<h1>Hola PDF</h1>');

    return $pdf->stream();
});
