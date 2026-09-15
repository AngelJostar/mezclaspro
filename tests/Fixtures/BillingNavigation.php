<?php

namespace Tests\Fixtures;

use Illuminate\Pagination\LengthAwarePaginator;

final class BillingNavigation
{
    public static function view(string $section): \Illuminate\Contracts\View\View
    {
        $data = [
            'search' => '', 'dateFrom' => null, 'dateTo' => null,
            'billingDueCounts' => ['yellow' => 3, 'red' => 2],
        ];

        if ($section === 'movements') {
            return view('admin.instituciones.billing.movements', $data + [
                'fromStage' => '', 'toStage' => '',
                'movements' => new LengthAwarePaginator([], 0, 50),
            ]);
        }

        return view('admin.instituciones.billing.index', $data + [
            'billingSection' => $section,
            'institucionId' => null, 'hospitalId' => null,
            'billingStatus' => '', 'facturacionStatus' => '', 'conciliableFilter' => '',
            'instituciones' => collect(), 'hospitals' => collect(), 'summary' => [],
            'records' => new LengthAwarePaginator([], 0, 200),
        ]);
    }
}
