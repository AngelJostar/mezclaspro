<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class NutritionEvidencePdfController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(
            $request->user()->can('nutricionales_solicitudes_store')
                || $request->user()->can('nutricionales_solicitudes_update'),
            403
        );

        $data = $request->validate([
            'fields' => ['required', 'array', 'max:300'],
            'fields.*.section' => ['required', 'string', 'max:100'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.value' => ['required', 'string', 'max:2000'],
            'findings' => ['present', 'array', 'max:100'],
            'findings.*.severity' => ['nullable', 'string', 'max:40'],
            'findings.*.title' => ['required', 'string', 'max:100'],
            'findings.*.rule_code' => ['nullable', 'string', 'max:150'],
            'findings.*.composition_code' => ['nullable', 'string', 'max:100'],
            'findings.*.message' => ['required', 'string', 'max:3000'],
            'findings.*.calculation' => ['nullable', 'string', 'max:3000'],
            'findings.*.suggestion' => ['nullable', 'string', 'max:3000'],
        ]);

        $generatedAt = now();
        $pdf = Pdf::loadView('pdfs.nutricionales.evidencia-validacion', [
            'sections' => collect($data['fields'])->groupBy('section'),
            'findings' => collect($data['findings']),
            'generatedAt' => $generatedAt,
            'generatedBy' => $request->user()->name ?? $request->user()->username ?? 'Usuario del sistema',
        ])->setPaper('letter', 'portrait');

        return $pdf->download('evidencia-validacion-nutricional-'.$generatedAt->format('Ymd-His').'.pdf');
    }
}
