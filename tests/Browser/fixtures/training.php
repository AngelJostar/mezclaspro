<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
foreach (['2014_10_12_000000_create_users_table.php', '2024_04_11_013606_create_permission_tables.php',
    '2025_11_06_183220_create_laboratories_table.php', '2026_08_20_000006_create_personnel_profiles_table.php',
    '2026_10_23_000001_create_training_program_catalog.php', '2026_10_23_000002_add_training_content.php',
    '2026_10_26_000001_create_training_completions_table.php', '2026_10_26_000002_create_training_assignments_table.php',
    '2026_10_26_000003_add_training_learning_progress.php'] as $file) (require database_path('migrations/'.$file))->up();
$user = App\Models\User::create(['name' => 'Admin', 'lastname' => 'Prueba', 'username' => 'prueba', 'password' => bcrypt('prueba'), 'is_active' => true]);
$user->assignRole(Spatie\Permission\Models\Role::findOrCreate('Admin'));
Illuminate\Support\Facades\Auth::login($user);
$type = $argv[1] ?? 'programas';
foreach (json_decode($argv[2] ?? '[]', true) as $id) App\Models\TrainingProgram::whereKey($id)->delete();
foreach (json_decode($argv[3] ?? '[]', true) as $record) App\Models\TrainingProgram::updateOrCreate(['id' => $record['id']], $record);
$access = json_decode($argv[4] ?? 'null', true);
if (is_array($access)) {
    $user->syncRoles([Spatie\Permission\Models\Role::findOrCreate('Capacitacion')]);
    $user->personnelProfile()->create(['paternal_surname' => 'Prueba', 'department' => 'Calidad', 'positions' => [], 'hire_date' => '2026-01-01', 'training_access_configured' => true]);
    foreach ($access as $programId => $modules) {
        Illuminate\Support\Facades\DB::table('training_program_access')->insert(['user_id' => $user->id,
            'training_program_id' => $programId, 'module_ids' => json_encode($modules === true ? [] : $modules), 'all_modules' => $modules === true]);
    }
}
if ($type === 'continuous') {
    $request = Illuminate\Http\Request::create('http://training.test/admin/capacitaciones/continua');
    $request->setUserResolver(fn () => $user);
    Illuminate\Support\Facades\URL::forceRootUrl('http://training.test');
    echo app(App\Http\Controllers\Admin\TrainingContinuousController::class)->index($request)->getContent();
    exit;
}
if ($type === 'catalog') {
    echo app(App\Http\Controllers\Admin\TrainingPersonnelController::class)->trainingPrograms()->getContent();
    exit;
}
$scenario = json_decode($argv[5] ?? '{}', true);
if (isset($scenario['role'])) $user->syncRoles([Spatie\Permission\Models\Role::findOrCreate($scenario['role'])]);
foreach ($scenario['personnel'] ?? [] as $person) {
    $employee = App\Models\User::create(\Illuminate\Support\Arr::except($person, ['role']) + ['username' => 'student-'.$person['id'], 'password' => bcrypt('test'), 'is_active' => true]);
    $employee->assignRole(Spatie\Permission\Models\Role::findOrCreate($person['role'] ?? 'Capacitacion'));
}
foreach ($scenario['assignments'] ?? [] as $assignment) {
    App\Models\TrainingAssignment::create($assignment + ['user_id' => $user->id, 'assigned_by' => $user->id, 'status' => 'assigned']);
}
foreach ($scenario['submissions'] ?? [] as $submission) App\Models\TrainingTaskSubmission::create($submission);
foreach ($scenario['attempts'] ?? [] as $attempt) App\Models\TrainingExamAttempt::create($attempt);
if (isset($scenario['as_user'])) {
    $user = App\Models\User::findOrFail($scenario['as_user']);
    Illuminate\Support\Facades\Auth::login($user);
}
if ($type === 'assignments') {
    $request = Illuminate\Http\Request::create('http://training.test/admin/capacitaciones/asignaciones', 'GET', isset($scenario['view_user']) ? ['user_id' => $scenario['view_user']] : []);
    $request->setUserResolver(fn () => $user);
    Illuminate\Support\Facades\URL::forceRootUrl('http://training.test');
    echo app(App\Http\Controllers\Admin\TrainingAssignmentController::class)->index($request)->getContent();
    exit;
}
foreach ($scenario['completions'] ?? [] as $completion) {
    $student = collect(App\Support\TrainingStudentDefaults::records())->firstWhere('studentKey', $completion['student_key']);
    foreach ($completion['browser_assignments'] ?? [] as $assignment) {
        $student['currentPrograms'][] = ['id' => $assignment['programId'], 'name' => App\Models\TrainingProgram::findOrFail($assignment['programId'])->title,
            'startDate' => Illuminate\Support\Carbon::parse($assignment['startDate'])->format('d/m/Y'), 'progress' => $assignment['progress'] ?? 0];
    }
    unset($completion['browser_assignments']);
    App\Models\TrainingCompletion::create($completion + ['student_name' => $student['name'], 'student_snapshot' => $student,
        'completed_at' => '2026-10-26 12:00:00', 'completed_by' => $user->id, 'completed_by_name' => 'Admin Prueba']);
}
$request = Illuminate\Http\Request::create('http://training.test/admin/capacitaciones/'.$type);
$request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('admin.capacitaciones.'.strtok($type, '?')));
$request->setLaravelSession(session()->driver());
$app->instance('request', $request);
Illuminate\Support\Facades\URL::forceRootUrl('http://training.test');
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/capacitaciones/index.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source."\n@stack('css')\n@stack('js')", [
    'persistedPersonnel' => [], 'isSuperAdmin' => false, 'jobCatalog' => ['Calidad' => ['Verificador']],
    'studentPersonnel' => App\Support\TrainingStudentCompletions::apply(array_merge(App\Support\TrainingStudentDefaults::records(), App\Support\TrainingAssignedStudents::records()), App\Models\TrainingCompletion::all()),
    'assignmentPersonnel' => App\Models\User::all()->map(fn ($person) => ['id' => $person->id, 'name' => trim($person->name.' '.$person->lastname)])->all(),
    'trainingCompletions' => App\Support\TrainingStudentCompletions::browserRecords(App\Models\TrainingCompletion::all()),
    'laboratories' => collect(), 'errors' => new Illuminate\Support\ViewErrorBag,
]);
