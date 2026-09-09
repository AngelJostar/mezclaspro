<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Tests\Fixtures\DiluentCreation::seed();
Illuminate\Support\Facades\URL::forceRootUrl('http://diluent.test/mezclaspro/public');
view()->share('errors', new Illuminate\Support\ViewErrorBag());

// The browser exercises the actual controller with a persistent, disposable SQLite database.
while (($line = fgets(STDIN)) !== false) {
    try {
        $command = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        $controller = app(App\Http\Controllers\Admin\CatalogProductController::class);
        if ($command['type'] === 'form') {
            $request = Illuminate\Http\Request::create('/create', 'GET', ['modal' => 1]);
            $result = ['html' => $controller->create($request, 'insumos')->render()];
        } elseif ($command['type'] === 'store') {
            $request = Illuminate\Http\Request::create('/store', 'POST', $command['fields'], [], [], ['HTTP_ACCEPT' => 'application/json']);
            $request->setLaravelSession(session()->driver());
            $response = $controller->store($request, 'insumos');
            $result = ['status' => $response->getStatusCode(), 'json' => $response->getData(true)];
        } elseif ($command['type'] === 'records') {
            $result = ['rows' => App\Models\Oncologicos\DiluentPresentation::orderBy('id')->get()->toArray()];
        } else {
            $result = ['html' => Tests\Fixtures\SupplyCatalog::render(Tests\Fixtures\SupplyCatalog::viewData())];
        }
    } catch (Illuminate\Validation\ValidationException $exception) {
        $result = ['status' => 422, 'json' => ['errors' => $exception->errors()]];
    } catch (Throwable $exception) {
        $result = ['fixture_error' => $exception->getMessage()];
    }
    echo json_encode($result, JSON_THROW_ON_ERROR).PHP_EOL;
    flush();
}
