<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Models\ClinicalSource;
use App\Services\Agents\AgentConfiguration;
use App\Services\Clinical\ClinicalEvidence;
use Illuminate\Console\Command;

class ImportClinicalManual extends Command
{
    protected $signature = 'clinical:import-manual {path : Archivo DOCX local}';
    protected $description = 'Importa el manual pendiente de revision y registra el agente clinico bajo demanda';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (!is_file($path) || filesize($path) > 10 * 1024 * 1024) {
            $this->error('Archivo inexistente o mayor a 10 MB.');
            return self::FAILURE;
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return self::FAILURE;
        $entry = $zip->statName('word/document.xml');
        if (!$entry || $entry['size'] > 5 * 1024 * 1024) { $zip->close(); return self::FAILURE; }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if (!$xml || strlen($xml) > 5 * 1024 * 1024 || str_contains($xml, '<!DOCTYPE')) return self::FAILURE;
        $doc = new \DOMDocument();
        if (!$doc->loadXML($xml, LIBXML_NONET)) return self::FAILURE;
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $paragraphs = [];
        foreach ($xpath->query('//w:body//w:p') as $p) {
            $text = '';
            foreach ($xpath->query('.//w:t', $p) as $t) $text .= $t->textContent;
            if (trim($text) !== '') $paragraphs[] = $text;
        }
        $text = implode("\n", $paragraphs);
        if (mb_strlen($text) < 100 || mb_strlen($text) > 100000) return self::FAILURE;
        $source = ClinicalSource::firstOrCreate(['sha256' => hash('sha256', $text), 'is_manual' => true], [
            'title' => 'Manual maestro de validacion clinica - revision 3', 'reference' => basename($path),
            'category' => 'nutricionales', 'content' => $text,
        ]);
        $agent = AiAgent::where('integration_key', ClinicalEvidence::KEY)->first();
        if (!$agent) {
            $agent = AiAgent::where('name', ClinicalEvidence::NAME)->whereNull('integration_key')
                ->where('instructions', ClinicalEvidence::INSTRUCTIONS)->first() ?? new AiAgent();
            $agent->forceFill(['integration_key' => ClinicalEvidence::KEY]);
        }
        if (!$agent->exists) {
            $agent->fill(['name' => ClinicalEvidence::NAME, 'description' => 'Apoyo a solicitudes y mensajes de mezclas. No sustituye la aprobacion profesional.',
                'instructions' => ClinicalEvidence::INSTRUCTIONS, 'is_active' => true]);
            $agent->configuration = AgentConfiguration::defaults($agent);
            $agent->save();
        }
        elseif ($agent->isDirty('integration_key')) $agent->save();
        $this->info('Manual S'.$source->id.' importado sin aprobar. Agente bajo demanda; las consultas requieren conexion y evidencia suficiente.');
        return self::SUCCESS;
    }
}
