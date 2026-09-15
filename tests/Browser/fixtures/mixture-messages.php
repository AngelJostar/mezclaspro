<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
echo Tests\Fixtures\HospitalRequestTable::render('todas', $argv[1] ?? 'Super Admin');
echo view('admin.solicitudes._messages-dialog')->render();
