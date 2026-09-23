<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Tests\Fixtures\CatalogAvailabilityData::seed();
Illuminate\Support\Facades\Auth::setUser(Tests\Fixtures\CatalogAvailabilityData::user());
Illuminate\Support\Facades\URL::forceRootUrl('http://catalog.test');
$category = $argv[1] ?? 'oncologicos';
$nutrition = $category === 'nutricionales';
$id = $category === 'antibioticos' ? 2 : 1;
Illuminate\Support\Facades\DB::table($nutrition ? 'nutri_medicine_list_items' : 'medicine_list_presentation')
    ->update(['is_active' => (bool) ($argv[2] ?? 1)]);
Illuminate\Support\Facades\DB::table($nutrition ? 'nutrition_medicine_presentations' : 'medicine_presentations')
    ->update(['is_available' => (bool) ($argv[3] ?? 1)]);
echo Tests\Fixtures\CatalogAvailabilityData::renderList($category, $id);
