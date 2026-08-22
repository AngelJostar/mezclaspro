<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InstitutionReportTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstitutionReportTemplateController extends Controller
{
    public function rename(
        Request $request,
        string $reportTemplate,
        InstitutionReportTemplateService $templates
    ): JsonResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        return response()->json([
            'message' => 'El nombre del reporte se guardó correctamente.',
            'template' => $templates->rename($reportTemplate, $validated['name']),
        ]);
    }

    public function update(
        Request $request,
        string $reportTemplate,
        InstitutionReportTemplateService $templates
    ): JsonResponse {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'columns' => ['required', 'array', 'min:1', 'max:60'],
            'columns.*.key' => [
                'required',
                'string',
                Rule::in($templates->allowedColumnKeys($reportTemplate)),
            ],
            'columns.*.label' => ['required', 'string', 'max:80'],
            'columns.*.visible' => ['required', 'boolean'],
            'info_boxes' => ['array', 'max:10'],
            'info_boxes.*.label' => ['nullable', 'string', 'max:80'],
            'info_boxes.*.value' => ['nullable', 'string', 'max:250'],
            'free_fields' => ['array', 'max:12'],
            'free_fields.*.label' => ['nullable', 'string', 'max:80'],
            'free_fields.*.value' => ['nullable', 'string', 'max:500'],
        ]);

        if (! collect($validated['columns'])->contains(fn (array $column) => $column['visible'])) {
            return response()->json([
                'message' => 'Selecciona al menos una columna visible.',
                'errors' => ['columns' => ['Selecciona al menos una columna visible.']],
            ], 422);
        }

        return response()->json([
            'message' => 'El formato se guardó correctamente.',
            'template' => $templates->save($reportTemplate, $validated),
        ]);
    }
}
