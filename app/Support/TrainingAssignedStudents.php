<?php

namespace App\Support;

use App\Models\TrainingAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

class TrainingAssignedStudents
{
    public static function person(User $user): array
    {
        $name = trim($user->name.' '.$user->lastname);
        return [
            'studentKey' => 'user-'.$user->id, 'studentId' => $user->id, 'name' => $name, 'assignmentsPersisted' => true,
            'initials' => collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''),
            'avatar' => 'sky', 'currentPrograms' => [], 'completedPrograms' => [], 'positions' => [], 'examScores' => [],
            'hireDate' => $user->personnelProfile?->hire_date?->format('d/m/Y') ?? 'Sin fecha',
            'department' => $user->personnelProfile?->department ?: 'Sin departamento',
            'employmentStatus' => $user->personnelProfile?->employment_status ?: 'hired', 'activity' => 'Sin actividad',
        ];
    }

    public static function records(?Collection $assignments = null): array
    {
        $assignments ??= TrainingAssignment::with(['user.personnelProfile', 'program'])->orderBy('id')->get();
        return $assignments->groupBy('user_id')->map(function ($records) {
            $person = self::person($records->first()->user);
            foreach ($records as $assignment) {
                if ($assignment->status === 'completed') {
                    $person['completedPrograms'][] = $assignment->program->title;
                    continue;
                }
                $person['currentPrograms'][] = [
                    'id' => $assignment->program_id, 'name' => $assignment->program->title,
                    'assignmentId' => $assignment->id, 'progress' => 0,
                    'startDate' => $assignment->start_date->format('d/m/Y'),
                    'statusLabel' => $assignment->started_at ? 'En curso' : 'Por iniciar',
                    'moduleCount' => count($assignment->modules()),
                ];
                $person['activity'] = ($assignment->started_at ?? $assignment->created_at)->format('d/m/Y H:i');
            }
            return $person;
        })->values()->all();
    }
}
