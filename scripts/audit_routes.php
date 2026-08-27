<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$brokenActions = [];
$names = [];
$signatures = [];

foreach (app('router')->getRoutes() as $route) {
    $name = $route->getName();

    if ($name !== null) {
        $names[$name][] = implode('|', $route->methods()).' '.$route->uri();
    }

    foreach (array_diff($route->methods(), ['HEAD']) as $method) {
        $signatures[$method.' '.$route->uri()][] = $name ?: $route->getActionName();
    }

    $action = $route->getActionName();

    if ($action === 'Closure' || ! str_contains($action, '@')) {
        continue;
    }

    [$class, $method] = explode('@', $action, 2);

    if (! class_exists($class) || ! method_exists($class, $method)) {
        $brokenActions[] = ($name ?: $route->uri()).' => '.$action;
    }
}

$duplicateNames = array_filter($names, static fn (array $routes): bool => count($routes) > 1);
$duplicateSignatures = array_filter($signatures, static fn (array $routes): bool => count($routes) > 1);

echo 'BROKEN_ACTIONS='.count($brokenActions).PHP_EOL;
foreach ($brokenActions as $brokenAction) {
    echo '  '.$brokenAction.PHP_EOL;
}

echo 'DUPLICATE_NAMES='.count($duplicateNames).PHP_EOL;
foreach ($duplicateNames as $name => $routes) {
    echo '  '.$name.PHP_EOL;
    foreach ($routes as $route) {
        echo '    '.$route.PHP_EOL;
    }
}

echo 'DUPLICATE_SIGNATURES='.count($duplicateSignatures).PHP_EOL;
foreach ($duplicateSignatures as $signature => $routes) {
    echo '  '.$signature.PHP_EOL;
    foreach ($routes as $route) {
        echo '    '.$route.PHP_EOL;
    }
}

exit(($brokenActions !== [] || $duplicateNames !== [] || $duplicateSignatures !== []) ? 1 : 0);
