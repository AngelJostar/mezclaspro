<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAgentRun;
use App\Services\Agents\PatientNutritionComparison;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AgentNutritionReportController extends Controller
{
    public function __invoke(Request $request, AiAgentRun $run)
    {
        $report = PatientNutritionComparison::authorizeReport($request->user(), $run);
        return Pdf::loadView('pdfs.nutricionales.patient-comparison', compact('report', 'run'))
            ->setPaper('letter', 'landscape')->stream('comparativo-nutricional-'.$run->id.'.pdf', ['Attachment' => $request->boolean('download')])
            ->header('Cache-Control', 'private, no-store')->header('X-Content-Type-Options', 'nosniff');
    }
}
