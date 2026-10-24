<?php

namespace App\Support;

use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainingAccess
{
    public static function catalog(?User $user): array
    {
        $programs = TrainingProgram::query()->orderBy('created_at')->orderBy('id')->get();
        if ($user?->personnelProfile?->training_access_configured
            && ! $user->hasAnyRole(['Super Admin', 'Admin'])
            && ! $user->can('menu.capacitaciones.personal')) {
            $access = DB::table('training_program_access')->where('user_id', $user->id)->get()->keyBy('training_program_id');
            $programs = $programs->filter(fn ($program) => $access->has($program->id));
            foreach ($programs as $program) {
                $entry = $access[$program->id];
                if (! $entry->all_modules) {
                    $ids = json_decode($entry->module_ids, true);
                    $program->module_definitions = array_values(array_filter($program->module_definitions,
                        fn ($module) => in_array($module['id'], $ids, true)));
                    foreach (['exam_definitions', 'task_definitions'] as $field) {
                        $program->$field = array_values(array_filter($program->$field ?? [],
                            fn ($item) => in_array($item['moduleId'] ?? '', $ids, true)));
                    }
                }
            }
        }
        return $programs->map->catalogEntry()->values()->all();
    }

    /** Call inside the personnel creation transaction; locks prevent assigning a deleted program. */
    public static function selections(array $data): array
    {
        $all = $data['training_all'] ?? [];
        $modules = array_filter($data['training_modules'] ?? []);
        $ids = array_values(array_unique(array_merge($all, array_keys($modules))));
        $programs = TrainingProgram::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        if (count($ids) !== $programs->count()) {
            throw ValidationException::withMessages(['training_modules' => 'Uno de los programas ya no está disponible. Revisa la selección.']);
        }
        $rows = [];
        foreach ($ids as $id) {
            $available = array_column($programs[$id]->module_definitions, 'id');
            $selected = array_values(array_unique($modules[$id] ?? []));
            if (array_diff($selected, $available)) {
                throw ValidationException::withMessages(['training_modules' => 'Selecciona únicamente módulos del programa correspondiente.']);
            }
            $full = in_array($id, $all, true);
            $rows[] = ['training_program_id' => $id, 'module_ids' => json_encode($full ? $available : $selected),
                'all_modules' => $full, 'created_at' => now(), 'updated_at' => now()];
        }
        return $rows;
    }
}
