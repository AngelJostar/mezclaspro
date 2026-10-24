<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'title', 'description', 'owner', 'status', 'module_definitions', 'exam_definitions', 'task_definitions', 'exams_imported', 'students', 'is_custom', 'legacy_imported'];
    protected $casts = ['module_definitions' => 'array', 'exam_definitions' => 'array', 'task_definitions' => 'array', 'exams_imported' => 'boolean', 'is_custom' => 'boolean', 'legacy_imported' => 'boolean'];

    public function catalogEntry(): array
    {
        return [
            'id' => $this->id, 'title' => $this->title, 'description' => $this->description ?? '',
            'owner' => $this->owner, 'status' => $this->status, 'modules' => count($this->module_definitions ?? []),
            'module_definitions' => $this->module_definitions ?? [], 'students' => $this->students,
            'exam_definitions' => array_map(fn ($exam) => array_replace($exam, ['project' => $this->title]), $this->exam_definitions ?? []),
            'task_definitions' => array_map(fn ($task) => array_replace($task, ['project' => $this->title]), $this->task_definitions ?? []),
            'isCustom' => $this->is_custom,
            'tone' => ['Activo' => 'active', 'Borrador' => 'draft', 'Inactivo' => 'inactive'][$this->status] ?? 'draft',
        ];
    }
}
