<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institucion;
use App\Models\InstitutionReportTemplate;
use App\Services\InstitutionCustomReportExportService;
use App\Services\InstitutionReportTemplateService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class InstitutionReportTemplateController extends Controller
{
    public function storeCustom(
        Request $request,
        InstitutionReportTemplateService $templates
    ): JsonResponse {
        $validated = $request->validate($this->customTemplateRules($templates));

        return response()->json([
            'message' => 'La plantilla de reporte se creó correctamente.',
            'template' => $templates->createCustom($validated, $request->user()?->id),
        ], 201);
    }

    public function updateCustom(
        Request $request,
        InstitutionReportTemplate $customTemplate,
        InstitutionReportTemplateService $templates
    ): JsonResponse {
        abort_unless($customTemplate->is_custom, 404);
        $validated = $request->validate($this->customTemplateRules($templates));

        return response()->json([
            'message' => 'La plantilla de reporte se actualizó correctamente.',
            'template' => $templates->updateCustom($customTemplate, $validated),
        ]);
    }

    public function destroyCustom(
        InstitutionReportTemplate $customTemplate,
        InstitutionReportTemplateService $templates
    ): JsonResponse {
        $templates->deleteCustom($customTemplate);

        return response()->json([
            'message' => 'La plantilla personalizada se eliminó correctamente.',
        ]);
    }

    public function publishCustom(
        Request $request,
        InstitutionReportTemplate $customTemplate,
        InstitutionReportTemplateService $templates
    ): JsonResponse {
        abort_unless($customTemplate->is_custom, 404);
        $validated = $request->validate([
            'is_published' => ['required', 'boolean'],
        ]);
        $isPublished = (bool) $validated['is_published'];

        return response()->json([
            'message' => $isPublished
                ? 'La plantilla se agregó a los reportes correctamente.'
                : 'La plantilla se quitó de los reportes correctamente.',
            'template' => $templates->publishCustom($customTemplate, $isPublished),
        ]);
    }

    public function downloadCustom(
        Request $request,
        InstitutionReportTemplate $customTemplate,
        Institucion $institucion,
        InstitutionCustomReportExportService $exporter
    ): Response {
        abort_unless($customTemplate->is_custom && $customTemplate->is_published, 404);
        $validated = $request->validate([
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $periodFrom = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $periodTo = Carbon::parse($validated['to'] ?? now()->endOfMonth()->toDateString())->endOfDay();
        $contents = $exporter->make(
            $customTemplate,
            $institucion,
            $request->user(),
            $periodFrom,
            $periodTo
        );
        $templateName = Str::slug($customTemplate->name ?: $customTemplate->title, '_') ?: 'reporte_personalizado';
        $downloadName = sprintf('%s_institucion_%d.xlsx', $templateName, $institucion->id);

        return response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $downloadName
            ),
            'Content-Length' => (string) strlen($contents),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

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

    private function customTemplateRules(InstitutionReportTemplateService $templates): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'data_source' => ['required', 'string', Rule::in(array_keys($templates->customDataSources()))],
            'layout' => ['required', 'array'],
            'layout.rows' => ['required', 'integer', 'min:1', 'max:40'],
            'layout.columns' => ['required', 'integer', 'min:1', 'max:20'],
            'layout.cells' => ['required', 'array', 'max:800'],
            'layout.cells.*.row' => ['required', 'integer', 'min:0', 'max:39'],
            'layout.cells.*.column' => ['required', 'integer', 'min:0', 'max:19'],
            'layout.cells.*.type' => ['required', 'string', Rule::in(['text', 'free', 'parameter'])],
            'layout.cells.*.value' => ['nullable', 'string', 'max:500'],
            'layout.cells.*.parameter' => [
                'nullable',
                'string',
                Rule::in($templates->allowedCustomParameterKeys()),
            ],
            'layout.cells.*.repeat_direction' => [
                'nullable',
                'string',
                Rule::in(['none', 'vertical', 'horizontal']),
            ],
            'layout.cells.*.style' => ['required', 'array'],
            'layout.cells.*.style.background' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'layout.cells.*.style.color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'layout.cells.*.style.font_family' => [
                'required',
                'string',
                Rule::in(['Arial', 'Calibri', 'Figtree', 'Georgia', 'Tahoma', 'Times New Roman', 'Verdana']),
            ],
            'layout.cells.*.style.font_size' => ['required', 'integer', 'min:8', 'max:36'],
            'layout.cells.*.style.bold' => ['required', 'boolean'],
            'layout.cells.*.style.italic' => ['required', 'boolean'],
            'layout.cells.*.style.underline' => ['required', 'boolean'],
            'layout.cells.*.style.align' => ['required', 'string', Rule::in(['left', 'center', 'right'])],
        ];
    }
}
