<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Tests\Fixtures\SupplyCatalog::seed();
Illuminate\Support\Facades\URL::forceRootUrl('http://consumable.test/mezclaspro/public');
view()->share('errors', new Illuminate\Support\ViewErrorBag());

// Keep controller writes in a disposable database while the browser exercises the form.
while (($line = fgets(STDIN)) !== false) {
    try {
        $command = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        $controller = app(App\Http\Controllers\Admin\ConsumableCatalogController::class);
        if ($command['type'] === 'form') {
            $request = Illuminate\Http\Request::create('/create', 'GET', ['modal' => 1]);
            $result = ['html' => $controller->create($request)->render()];
        } elseif ($command['type'] === 'create') {
            $result = ['redirect' => $controller->create(Illuminate\Http\Request::create('/create'))->getTargetUrl()];
        } elseif ($command['type'] === 'store') {
            parse_str($command['body'], $fields);
            $request = Illuminate\Http\Request::create('/store', 'POST', $fields, [], [], ['HTTP_ACCEPT' => 'application/json']);
            $response = $controller->store($request);
            $result = ['status' => $response->getStatusCode(), 'json' => $response->getData(true)];
        } elseif ($command['type'] === 'records') {
            $result = ['rows' => App\Models\ConsumableItem::with('catalogPresentations')->orderBy('id')->get()->toArray()];
        } else {
            $result = ['html' => Tests\Fixtures\SupplyCatalog::render(Tests\Fixtures\SupplyCatalog::viewData('consumibles'))];
        }
    } catch (Illuminate\Validation\ValidationException $exception) {
        $result = ['status' => 422, 'json' => ['errors' => $exception->errors()]];
    } catch (Throwable $exception) {
        $result = ['fixture_error' => $exception->getMessage()];
    }
    echo json_encode($result, JSON_THROW_ON_ERROR).PHP_EOL;
    flush();
}
