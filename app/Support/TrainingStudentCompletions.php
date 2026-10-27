<?php

namespace App\Support;

use Illuminate\Support\Collection;

class TrainingStudentCompletions
{
    public static function apply(array $students, Collection $completions): array
    {
        $students = collect($students)->keyBy('studentKey');
        foreach ($completions->groupBy('student_key') as $key => $records) {
            $person = $students->get($key) ?? $records->first()->student_snapshot;
            $person['studentKey'] = $key;
            $person['earlyCompletions'] = [];
            $persisted = $person['assignmentsPersisted'] ?? false;
            $person['currentPrograms'] = collect($persisted && ! $students->has($key) ? [] : ($person['currentPrograms'] ?? []))
                ->concat($persisted ? [] : $records->flatMap(fn ($record) => $record->student_snapshot['currentPrograms'] ?? []))
                ->unique(fn ($program) => $program['id'].'|'.($program['startDate'] ?? ''))->values()->all();
            foreach ($records as $completion) {
                $person['currentPrograms'] = array_values(array_filter($person['currentPrograms'] ?? [],
                    fn ($program) => $program['id'] !== $completion->program_id
                        || ($program['startDate'] ?? '') !== $completion->start_date->format('d/m/Y')));
                $person['completedPrograms'][] = $completion->program_name;
                $person['earlyCompletions'][] = [
                    'programId' => $completion->program_id, 'name' => $completion->program_name,
                    'startDate' => $completion->start_date->format('d/m/Y'),
                    'completedAt' => $completion->completed_at->format('d/m/Y H:i'),
                    'completedBy' => $completion->completed_by_name,
                    'completionType' => $completion->completion_type ?? 'administrative',
                ];
                $person['activity'] = $completion->completed_at->format('d/m/Y H:i');
            }
            $person['completedPrograms'] = array_values(array_unique($person['completedPrograms']));
            $students->put($key, $person);
        }

        return $students->values()->all();
    }

    public static function browserRecords(Collection $completions): array
    {
        return $completions->map(fn ($completion) => [
            'studentKey' => $completion->student_key, 'studentName' => $completion->student_name,
            'programId' => $completion->program_id, 'startDate' => $completion->start_date->format('Y-m-d'),
        ])->values()->all();
    }
}
