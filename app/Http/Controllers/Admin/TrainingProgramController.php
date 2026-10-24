<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TrainingProgramController extends Controller
{
    private function rules(): array
    {
        return ['title' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:1000'],
            'owner' => ['required', 'string', 'max:100'], 'status' => ['required', Rule::in(['Activo', 'Borrador', 'Inactivo'])]];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $program = TrainingProgram::create($data + ['id' => 'programa-'.Str::uuid(), 'module_definitions' => [], 'is_custom' => true, 'legacy_imported' => true]);
        return response()->json($program->catalogEntry(), 201);
    }

    public function update(Request $request, string $program)
    {
        $data = $request->validate($this->rules());
        return DB::transaction(function () use ($program, $data) {
            $record = TrainingProgram::query()->lockForUpdate()->findOrFail($program);
            $record->update($data + ['legacy_imported' => true]);
            return response()->json($record->catalogEntry());
        });
    }

    public function destroy(Request $request, string $program)
    {
        $data = $request->validate(['confirmed' => ['required', 'accepted'], 'confirmation_name' => ['required', 'string']]);
        $paths = DB::transaction(function () use ($program, $data) {
            $record = TrainingProgram::query()->lockForUpdate()->findOrFail($program);
            abort_unless(hash_equals($record->title, $data['confirmation_name']), 409, 'El programa cambió. Cierra la ventana y vuelve a abrirlo.');
            DB::table('deleted_training_programs')->insert(['id' => $program, 'deleted_at' => now()]);
            $paths = collect($record->module_definitions)->flatMap(fn ($module) => $module['chapter_definitions'] ?? [])->pluck('video_path')->filter()->all();
            $record->delete();
            return $paths;
        });
        if ($paths) \Illuminate\Support\Facades\Storage::disk('local')->delete($paths);
        return response()->json(['deleted' => $program]);
    }

    public function importLegacy(Request $request)
    {
        $rules = ['programs' => ['present', 'array', 'max:200'], 'programs.*.id' => ['required', 'string', 'max:191', 'regex:/^[a-zA-Z0-9_-]+$/']];
        foreach ($this->rules() as $key => $rule) $rules['programs.*.'.$key] = $rule;
        $data = $request->validate($rules);
        DB::transaction(function () use ($data) {
            foreach ($data['programs'] as $program) {
                $program = \Illuminate\Support\Arr::only($program, ['id', 'title', 'description', 'owner', 'status']);
                $record = TrainingProgram::query()->lockForUpdate()->find($program['id']);
                if (DB::table('deleted_training_programs')->where('id', $program['id'])->lockForUpdate()->first()) continue;
                if ($record?->legacy_imported) continue;
                if ($record) $record->update($program + ['legacy_imported' => true]);
                else TrainingProgram::create($program + ['module_definitions' => [], 'is_custom' => true, 'legacy_imported' => true]);
            }
        });
        return response()->json(['programs' => TrainingProgram::all()->map->catalogEntry()->values()]);
    }
}
