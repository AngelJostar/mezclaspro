<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Tests\Fixtures\RequestQuotationData::seed();
(require database_path('migrations/2026_10_28_120000_add_institution_account_to_users.php'))->up();
$user = App\Models\User::create(['name' => 'Institución de prueba', 'username' => 'institucion.demo', 'institution_id' => 1, 'is_active' => true]);
$user->assignRole('Institucion');
App\Models\Institucion::find(1)->hospitals()->attach(2);
App\Models\Hospital::whereKey(2)->update(['name' => 'Hospital Norte']);
auth()->login($user);
view()->share('errors', new Illuminate\Support\ViewErrorBag());
$pages = [];
foreach (['home' => null, 'filtered' => 2, 'quotations' => null] as $key => $hospital) {
    $request = Illuminate\Http\Request::create('http://institution-portal.test/admin/solicitudes', 'GET');
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession(session()->driver());
    $request->attributes->set('institution_hospital', $hospital);
    $app->instance('request', $request);
    $controller = $key === 'quotations' ? App\Http\Controllers\Admin\RequestQuotationController::class : App\Http\Controllers\Admin\UnifiedSolicitudController::class;
    $pages[$key] = app($controller)->index($request)->render();
}
echo json_encode($pages, JSON_THROW_ON_ERROR);
