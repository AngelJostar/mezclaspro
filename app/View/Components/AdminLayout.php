<?php

namespace App\View\Components;

use App\Services\InstitutionBillingPendingSummaryService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdminLayout extends Component
{
    /**
     * @var array{yellow: int, red: int}
     */
    public array $billingDueCounts;

    /**
     * Create a new component instance.
     */
    public function __construct(InstitutionBillingPendingSummaryService $pendingBilling)
    {
        $this->billingDueCounts = auth()->user()?->hasAnyRole(['Super Admin', 'Administracion y facturacion'])
            ? $pendingBilling->counts()
            : ['yellow' => 0, 'red' => 0];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('layouts.admin');
    }
}
