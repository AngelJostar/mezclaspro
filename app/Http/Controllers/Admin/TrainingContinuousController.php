<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\TrainingAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrainingContinuousController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $programs = array_map(fn ($program) => [
            'id' => $program['id'], 'title' => $program['title'], 'description' => $program['description'],
            'modules' => array_map(fn ($module) => [
                'id' => $module['id'], 'stage' => $module['stage'], 'title' => $module['title'],
                'description' => $module['description'] ?? '',
                'chapters' => array_map(fn ($chapter) => [
                    'id' => $chapter['id'], 'title' => $chapter['title'], 'objective' => $chapter['objective'] ?? '',
                    'duration' => $chapter['duration'] ?? '',
                    'video_url' => $this->externalVideo($chapter['video'] ?? null),
                    'file_url' => empty($chapter['video_path']) ? null : route('admin.capacitaciones.continua.video', [
                        'program' => $program['id'], 'module' => $module['id'], 'chapter' => $chapter['id'],
                    ]),
                ], $module['chapter_definitions'] ?? []),
            ], $program['module_definitions'] ?? []),
        ], TrainingAccess::continuousCatalog($request->user()));

        return response()->json(['programs' => array_values($programs)])->header('Cache-Control', 'no-store, private');
    }

    public function video(Request $request, string $program, string $module, string $chapter)
    {
        $allowed = collect(TrainingAccess::continuousCatalog($request->user()))->firstWhere('id', $program);
        $selected = collect($allowed['module_definitions'] ?? [])->firstWhere('id', $module);
        abort_unless($selected, 403);
        $entry = collect($selected['chapter_definitions'] ?? [])->firstWhere('id', $chapter);
        $path = $entry['video_path'] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store, private',
        ]);
    }

    private function externalVideo(?string $url): ?string
    {
        return $url && filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true) ? $url : null;
    }
}
