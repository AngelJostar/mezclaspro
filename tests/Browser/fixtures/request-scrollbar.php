<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo '<div class="mt-4 overflow-x-auto" data-sticky-x-position="viewport"><table class="w-full text-sm text-gray-500"><thead class="text-xs bg-gray-50 uppercase">';
echo view('admin.solicitudes._table-header')->render();
echo '</thead><tbody><tr><td colspan="19" class="px-4 py-10 text-center">No se encontraron solicitudes.</td></tr></tbody></table></div>';
