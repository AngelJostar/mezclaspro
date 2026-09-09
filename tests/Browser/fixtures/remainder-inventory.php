<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
Tests\Fixtures\RemainderInventory::seed();
$data = Tests\Fixtures\RemainderInventory::viewData($argv[1] ?? 'oncologicos');
$data['errors'] = new Illuminate\Support\ViewErrorBag();
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/oncologicos/inventory/index.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source.' @stack("js")', $data);
