<?php

return [
    // Explicit list IDs: never infer a generic tariff from another hospital's list.
    'base_lists' => [
        'nutricionales' => env('QUOTATION_BASE_NUTRITION_LIST_ID'),
        'oncologicos' => env('QUOTATION_BASE_ONCOLOGY_LIST_ID'),
        'antibioticos' => env('QUOTATION_BASE_ANTIBIOTIC_LIST_ID'),
    ],
];
