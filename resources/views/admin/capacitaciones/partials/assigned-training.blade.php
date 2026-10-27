<section class="training-own-assignments" data-own-assignments aria-labelledby="own-training-title">
    <header class="assigned-heading">
        <div><h2 id="own-training-title">Mis capacitaciones asignadas</h2><p data-assigned-student-name>{{ trim(auth()->user()?->name.' '.auth()->user()?->lastname) }}</p></div>
        <button type="button" class="training-secondary-button" data-refresh-assignments>Actualizar</button>
    </header>
    <p data-own-assignment-status role="status">Cargando capacitaciones…</p>
    <div data-own-assignment-list class="assigned-programs" aria-label="Programas asignados"></div>
    <div data-assigned-syllabus></div>
</section>
<dialog class="training-program-modal training-assigned-reader" data-assigned-reader aria-labelledby="assigned-reader-title">
    <header class="assigned-heading">
        <div><span>Mi capacitación</span><h3 id="assigned-reader-title" data-assigned-reader-title></h3></div>
        <button type="button" class="training-modal-close" data-close-assigned-reader aria-label="Cerrar capacitación">×</button>
    </header>
    <div class="training-assigned-reader-body"><p data-assigned-chapter-position></p><div data-assigned-chapters></div><p role="status" data-reader-feedback></p></div>
</dialog>
<dialog class="training-program-modal assigned-activity-dialog" data-assigned-activity aria-labelledby="assigned-activity-title">
    <header class="assigned-heading"><h3 id="assigned-activity-title"></h3><button type="button" class="training-modal-close" data-close-assigned-activity aria-label="Cerrar actividad">×</button></header>
    <form data-assigned-activity-form><div data-assigned-activity-body></div><p data-assigned-activity-error role="alert"></p><footer><button type="submit" class="training-primary-button" data-assigned-activity-submit>Enviar</button></footer></form>
</dialog>
@push('css')
<style>
[data-role-panel="learner"] [hidden], .training-obligation-flow[hidden] { display:none !important; }
[data-training-screen] > [data-role-panel="learner"] { min-width:0; }
.training-own-assignments { border:1px solid #d6e0f0; border-radius:12px; padding:24px; background:white; }
.assigned-heading { display:flex; align-items:center; justify-content:space-between; gap:20px; }
.assigned-heading h2 { font-size:23px; margin:0 0 6px; }
.assigned-heading p, [data-own-assignment-status], .assigned-muted { color:#60708d; font-size:13px; }
.assigned-programs { display:flex; gap:12px; overflow-x:auto; padding:18px 1px; }
.assigned-programs .training-program-carousel-card { min-width:265px; flex:0 0 290px; }
.assigned-programs .training-program-carousel-select { width:100%; text-align:left; }
.assigned-programs progress, .assigned-summary progress { display:block; width:100%; height:8px; accent-color:#00a992; margin-top:9px; }
.assigned-programs progress, .assigned-summary progress { appearance:none; border:0; border-radius:8px; overflow:hidden; background:#e1e6ee; }
.assigned-programs progress::-webkit-progress-bar, .assigned-summary progress::-webkit-progress-bar { background:#e1e6ee; }
.assigned-programs progress::-webkit-progress-value, .assigned-summary progress::-webkit-progress-value { background:linear-gradient(90deg,#4265e8,#00a992); border-radius:8px; }
.assigned-programs progress::-moz-progress-bar, .assigned-summary progress::-moz-progress-bar { background:linear-gradient(90deg,#4265e8,#00a992); border-radius:8px; }
.assigned-summary { padding:18px; border:1px solid #b8e5dd; border-radius:9px; background:#f0fbf8; margin:8px 0 20px; }
.assigned-summary h3 { font-size:19px; margin:0 0 5px; }
.assigned-summary p { margin:6px 0; }
[data-assigned-syllabus] .training-module-workspace { grid-template-columns:minmax(240px,30%) minmax(0,1fr); align-items:start; }
[data-assigned-syllabus] .training-module-item { width:100%; text-align:left; }
[data-assigned-syllabus] .training-module-item small { display:block; }
.assigned-status { display:inline-flex; border-radius:20px; padding:4px 10px; font-size:12px; background:#eef2ff; color:#3a4ba0; }
.assigned-status.is-completed { background:#dbf7ee; color:#008978; }
.assigned-status.is-locked { background:#f1f3f6; color:#62718a; }
.assigned-chapter { display:flex; align-items:start; justify-content:space-between; gap:15px; border:1px solid #d6e0f0; padding:18px; border-radius:8px; margin-top:12px; }
.assigned-chapter h4 { margin:0 0 6px; font-weight:700; }
.assigned-chapter.is-current { border-color:#3c8cff; background:#f3f8ff; }
.assigned-activities { padding:24px; border:1px solid #d6e0f0; border-radius:12px; background:white; }
.assigned-activities h2 { font-size:22px; margin-bottom:12px; }
.assigned-activities article { padding:18px; border:1px solid #d6e0f0; border-radius:8px; margin-top:12px; }
.assigned-activities h3 { font-weight:700; font-size:17px; }
.assigned-activities p { margin:8px 0; white-space:pre-wrap; }
.assigned-activities a { color:#087f73; text-decoration:underline; }
dialog.training-program-modal.training-assigned-reader { width:96vw; max-width:1500px; height:92vh; max-height:92vh; padding:0; }
dialog.training-program-modal.training-assigned-reader[open] { display:flex; flex-direction:column; }
.training-assigned-reader > header, .assigned-activity-dialog > header { padding:18px 24px; border-bottom:1px solid #d6e0f0; }
.training-assigned-reader > header { flex-shrink:0; }
.training-assigned-reader-body { padding:18px 28px; overflow:auto; min-height:0; flex:1; }
[data-assigned-chapters] article { max-width:1200px; margin:0 auto; }
[data-assigned-chapters] h4 { font-size:23px; margin:10px 0; }
[data-assigned-chapters] p { white-space:pre-wrap; margin:10px 0; }
[data-assigned-chapters] video, [data-assigned-chapters] iframe { display:block; width:100%; height:clamp(200px,calc(92vh - 370px),650px); margin:16px 0; background:#10213b; border:0; border-radius:8px; }
[data-assigned-chapters] a { display:block; color:#087f73; text-decoration:underline; }
[data-assigned-chapters] button { margin-top:16px; }
dialog.training-program-modal.assigned-activity-dialog { width:min(900px,94vw); max-width:94vw; max-height:92vh; padding:0; }
.assigned-activity-dialog form { padding:24px; max-height:76vh; overflow:auto; }
.assigned-activity-dialog label { display:block; margin:10px 0; }
.assigned-activity-dialog textarea { width:100%; min-height:125px; border:1px solid #c4d1e5; border-radius:6px; }
.assigned-activity-dialog fieldset { border:1px solid #d6e0f0; border-radius:8px; padding:16px; margin-top:16px; }
.assigned-activity-dialog legend { font-weight:700; }
.assigned-activity-dialog input[type=radio], .assigned-activity-dialog input[type=checkbox] { margin-right:10px; }
[data-assigned-activity-error], [data-assignment-error] { color:#b42318; }
@media(max-width:700px) { .assigned-heading { align-items:stretch; flex-direction:column; } .training-own-assignments { padding:14px; } [data-assigned-syllabus] .training-module-workspace { grid-template-columns:1fr; } .training-assigned-reader-body { padding:16px; } }
</style>
@endpush
