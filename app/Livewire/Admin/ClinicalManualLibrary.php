<?php

namespace App\Livewire\Admin;

use App\Models\ClinicalSource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Locked;

class ClinicalManualLibrary extends Component
{
    use WithFileUploads;

    public const TYPES = [
        'npt_adulto' => 'Nutrición Parenteral Adulto',
        'npt_pediatrico' => 'Nutrición Parenteral Pediátrico',
        'oncologicos' => 'Oncológicos',
        'antibioticos' => 'Antibióticos',
    ];

    public string $type = 'npt_adulto';
    public string $version = '';
    public string $reference = '';
    public string $content = '';
    public $file;
    public string $notice = '';
    #[Locked]
    public string $modal = '';
    #[Locked]
    public ?int $viewingId = null;
    public string $clinicalReviewer = '';
    public bool $reviewConfirmed = false;

    public function openForm(string $type): void
    {
        $this->authorizeAccess();
        abort_unless(array_key_exists($type, self::TYPES), 404);
        $this->reset('file', 'version', 'reference', 'content', 'viewingId');
        $this->resetValidation();
        $this->type = $type;
        $this->modal = 'form';
    }

    public function viewManual(int $id): void
    {
        $this->authorizeAccess();
        $source = ClinicalSource::where('is_manual', true)->findOrFail($id);
        $this->viewingId = $id;
        $this->clinicalReviewer = (string) $source->clinical_reviewer;
        $this->reviewConfirmed = false;
        $this->modal = 'view';
        $this->resetValidation();
    }

    public function showHistory(string $type): void
    {
        $this->authorizeAccess();
        abort_unless(array_key_exists($type, self::TYPES), 404);
        $this->type = $type;
        $this->modal = 'history';
        $this->resetValidation();
    }

    public function closeModal(): void
    {
        $this->authorizeAccess();
        $this->reset('modal', 'viewingId', 'file', 'version', 'reference', 'content',
            'clinicalReviewer', 'reviewConfirmed');
        $this->resetValidation();
    }

    public function approveManual(int $id): void
    {
        $this->authorizeAccess();
        abort_unless($this->viewingId === $id, 403);
        $source = ClinicalSource::where('is_manual', true)->findOrFail($id);
        if ($source->superseded_at) {
            $this->addError('manualReview', 'No se puede aprobar una versión histórica. Abre la versión actual.');
            return;
        }
        $data = $this->validate([
            'clinicalReviewer' => 'required|string|min:3|max:255',
            'reviewConfirmed' => 'accepted',
        ], [
            'reviewConfirmed.accepted' => 'Confirma que el responsable sanitario revisó esta versión del manual.',
        ]);
        $source->update([
            'clinical_reviewer' => $data['clinicalReviewer'],
            'valid_until' => null,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        $this->reviewConfirmed = false;
        $this->notice = 'Manual aprobado para la validación clínica. Las solicitudes deben validarse nuevamente.';
        $this->resetValidation();
    }

    public function revokeManualApproval(int $id): void
    {
        $this->authorizeAccess();
        abort_unless($this->viewingId === $id, 403);
        $source = ClinicalSource::where('is_manual', true)->findOrFail($id);
        $source->update([
            'clinical_reviewer' => null,
            'valid_until' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $this->reset('clinicalReviewer', 'reviewConfirmed');
        $this->notice = 'Aprobación retirada. El manual dejó de formar parte de la evidencia vigente.';
        $this->resetValidation();
    }

    private function authorizeAccess(): void
    {
        abort_unless(Auth::user()?->hasRole('Super Admin'), 403);
    }

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function updatedFile(): void
    {
        $this->authorizeAccess();
        $this->validate(['file' => 'required|file|mimes:pdf,docx,txt|max:10240']);
        $this->content = '';
        $extension = strtolower($this->file->getClientOriginalExtension());
        if ($extension === 'txt') {
            $this->content = file_get_contents($this->file->getRealPath());
        } elseif ($extension === 'docx') {
            $zip = new \ZipArchive();
            if ($zip->open($this->file->getRealPath()) !== true) {
                $this->addError('file', 'El documento Word no se pudo leer.'); return;
            }
            $stat = $zip->statName('word/document.xml');
            if (!$stat || $stat['size'] > 2000000) {
                $zip->close(); $this->addError('file', 'El documento no tiene un texto compatible o excede el tamaño permitido.'); return;
            }
            $xml = $zip->getFromName('word/document.xml'); $zip->close();
            $this->content = trim(html_entity_decode(strip_tags(str_replace(['</w:p>', '</w:tr>', '</w:tc>'], ["\n", "\n", "\t"], $xml)), ENT_QUOTES | ENT_XML1, 'UTF-8'));
        }
    }

    public function save(): void
    {
        $this->authorizeAccess();
        $this->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'version' => 'required|string|max:20', 'reference' => 'required|string|max:1000',
            'content' => 'required|string|min:100|max:30000',
            'file' => 'nullable|file|mimes:pdf,docx,txt|max:10240',
        ]);
        $lock = Cache::lock('clinical-manual-library:'.$this->type, 30);
        if (!$lock->get()) { $this->addError('version', 'Hay otra actualización en curso. Intenta nuevamente.'); return; }
        $path = null;
        try {
            if (ClinicalSource::where('is_manual', true)->where('manual_type', $this->type)->where('manual_version', $this->version)->exists()) {
                $this->addError('version', 'Esta versión ya existe. Usa un número de versión nuevo.'); return;
            }
            $path = $this->file?->store('clinical-manuals', 'local');
            DB::transaction(function () use ($path): void {
                ClinicalSource::current()->where('is_manual', true)->where(function ($query) {
                    $query->where('manual_type', $this->type);
                    if ($this->type === 'npt_adulto') $query->orWhere(fn ($legacy) => $legacy->where('category', 'nutricionales')->where(fn ($type) => $type->whereNull('manual_type')->orWhere('manual_type', 'nutricionales')));
                })->update(['superseded_at' => now()]);
                ClinicalSource::create([
                    'title' => 'Manual maestro · '.self::TYPES[$this->type],
                    'category' => in_array($this->type, ['oncologicos', 'antibioticos'], true) ? $this->type : 'nutricionales',
                    'manual_type' => $this->type, 'manual_version' => $this->version,
                    'reference' => $this->reference, 'content' => $this->content,
                    'sha256' => hash('sha256', $this->content), 'is_manual' => true,
                    'file_path' => $path, 'file_name' => $this->file?->getClientOriginalName(),
                    'file_sha256' => $this->file ? hash_file('sha256', $this->file->getRealPath()) : null,
                    'uploaded_by' => Auth::id(),
                ]);
            });
            $this->reset('file', 'version', 'reference', 'content');
            $this->modal = '';
            $this->notice = 'Versión guardada. La versión anterior permanece en el historial. Pendiente de revisión profesional.';
        } catch (\Throwable $e) {
            if ($path) Storage::disk('local')->delete($path);
            throw $e;
        } finally { $lock->release(); }
    }

    public function download(int $id)
    {
        $this->authorizeAccess();
        $source = ClinicalSource::where('is_manual', true)->findOrFail($id);
        abort_unless($source->file_path && Storage::disk('local')->exists($source->file_path), 404);
        return Storage::disk('local')->download($source->file_path, $source->file_name);
    }

    public function analyze(int $id): void
    {
        $this->authorizeAccess();
        $source = ClinicalSource::where('is_manual', true)->findOrFail($id);
        $previous = ClinicalSource::where('is_manual', true)->where('manual_type', $source->manual_type)->where('id', '<', $source->id)->latest('id')->first();
        $source->update(['manual_analysis' => array_merge($source->manual_analysis ?? [], [
            'analyzed_at' => now()->toIso8601String(), 'analyzed_by' => Auth::id(),
            'characters' => mb_strlen($source->content),
            'words' => count(preg_split('/\s+/u', trim($source->content))),
            'previous_version' => $previous?->manual_version,
            'content_changed' => $previous ? $previous->sha256 !== $source->sha256 : null,
            'sections' => collect(preg_split('/\R/u', $source->content))->filter(fn ($line) => preg_match('/^(?:\d+[.\- ]|cap[ií]tulo|secci[oó]n|supuesto|criterio|referencias|bibliograf[ií]a)/iu', trim($line)))->take(50)->values()->all(),
        ])]);
        $this->notice = 'Análisis documental actualizado: estructura, extensión y comparación con la versión anterior.';
    }

    public function analyzeWithAgent(int $id): void
    {
        $this->authorizeAccess();
        $source = ClinicalSource::where('is_manual', true)->findOrFail($id);
        $agent = app(\App\Services\Clinical\ClinicalEvidence::class)->agent();
        if (!$agent?->is_active) { $this->addError('analysis', 'Activa el agente de soporte químico y clínico para analizar el manual.'); return; }
        try {
            $result = app(\App\Services\Clinical\OpenAiClinicalChat::class)->reply($agent, [],
                'Analiza el manual maestro de '.self::TYPES[$source->manual_type === 'nutricionales' ? 'npt_adulto' : ($source->manual_type ?: ($source->category === 'antibioticos' ? 'antibioticos' : 'npt_adulto'))].', versión '.($source->manual_version ?: 'sin versión registrada').'.', Auth::id(), [[
                    'id' => 'S'.$source->id, 'title' => $source->title, 'reference' => $source->reference,
                    'category' => $source->category, 'manual_type' => $source->manual_type,
                    'manual_version' => $source->manual_version, 'content' => $source->content,
                    'sha256' => $source->sha256, 'reviewed' => $source->isReviewed(), 'is_manual' => true,
                    'resolves_manual_ambiguities' => false,
                ]]);
            $source->update(['manual_analysis' => array_merge($source->manual_analysis ?? [], [
                'agent_result' => $result, 'agent_analyzed_at' => now()->toIso8601String(), 'analyzed_by' => Auth::id(),
            ])]);
            $this->notice = 'Análisis del agente guardado para esta versión.';
            $this->viewingId = $id;
            $this->modal = 'view';
        } catch (\App\Services\Clinical\ClinicalChatUnavailable $e) {
            $this->addError('analysis', $e->getMessage());
        }
    }

    public function render()
    {
        $this->authorizeAccess();
        return view('livewire.admin.clinical-manual-library', [
            'types' => self::TYPES,
            'viewing' => $this->viewingId ? ClinicalSource::where('is_manual', true)->findOrFail($this->viewingId) : null,
            'manuals' => ClinicalSource::where('is_manual', true)->latest('id')->get()->groupBy(fn ($source) => $source->manual_type === 'nutricionales' ? 'npt_adulto' : ($source->manual_type ?: ($source->category === 'antibioticos' ? 'antibioticos' : 'npt_adulto'))),
        ]);
    }
}

