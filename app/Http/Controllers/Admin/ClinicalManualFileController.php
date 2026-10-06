<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClinicalSource;
use Illuminate\Support\Facades\Storage;

class ClinicalManualFileController extends Controller
{
    public function __invoke(ClinicalSource $source)
    {
        abort_unless($source->is_manual && $source->file_path && Storage::disk('local')->exists($source->file_path), 404);
        abort_unless(strtolower(pathinfo($source->file_path, PATHINFO_EXTENSION)) === 'pdf', 404);
        return Storage::disk('local')->response($source->file_path, $source->file_name, ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'], 'inline');
    }
}
