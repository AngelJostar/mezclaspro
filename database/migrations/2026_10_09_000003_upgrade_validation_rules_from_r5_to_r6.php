<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceRevision('R5', 'R6');
    }

    public function down(): void
    {
        $this->replaceRevision('R6', 'R5');
    }

    private function replaceRevision(string $from, string $to): void
    {
        if (! Schema::hasTable('validation_rules')) return;

        DB::table('validation_rules')
            ->where('code', 'like', "NP.{$from}.%")
            ->orderBy('id')
            ->get()
            ->each(function ($rule) use ($from, $to): void {
                $configuration = $this->replaceValue(json_decode($rule->configuration, true), $from, $to);
                DB::table('validation_rules')->where('id', $rule->id)->update([
                    'code' => preg_replace('/^NP\\.'.preg_quote($from, '/').'\\./', "NP.{$to}.", $rule->code),
                    'name' => $this->replaceText($rule->name, $from, $to),
                    'description' => $this->replaceText($rule->description, $from, $to),
                    'configuration' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
            });
    }

    private function replaceValue(mixed $value, string $from, string $to): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->replaceValue($item, $from, $to), $value);
        }

        return is_string($value) ? $this->replaceText($value, $from, $to) : $value;
    }

    private function replaceText(?string $value, string $from, string $to): ?string
    {
        if ($value === null) return null;

        return str_replace([
            "NP.{$from}.",
            "Manual Maestro {$from}",
            "Manual Maestro de Validación {$from}",
            "Combinaciones permitidas {$from}",
        ], [
            "NP.{$to}.",
            "Manual Maestro {$to}",
            "Manual Maestro de Validación {$to}",
            "Combinaciones permitidas {$to}",
        ], $value);
    }
};
