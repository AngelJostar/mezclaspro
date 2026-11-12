<section class="learner-dashboard" data-learner-dashboard aria-labelledby="learner-dashboard-title" aria-busy="true">
    <header class="learner-dashboard-heading">
        <div>
            <span class="learner-dashboard-eyebrow">MI APRENDIZAJE</span>
            <h2 id="learner-dashboard-title">Resumen del alumno</h2>
            <p data-dashboard-student-name>{{ trim(auth()->user()?->name.' '.auth()->user()?->lastname) }}</p>
            <p>Consulta tus actividades, resultados y certificaciones. El temario y tu avance están en Mi capacitación.</p>
        </div>
        <button type="button" class="training-secondary-button" data-refresh-dashboard>Actualizar</button>
    </header>
    <p class="learner-dashboard-message" data-dashboard-message role="status">Cargando resumen…</p>
    <div data-dashboard-content hidden>
        <div class="learner-dashboard-metrics" data-dashboard-metrics aria-label="Resumen de capacitación"></div>
        <div class="learner-dashboard-next">
            <div><h3>Tu ruta de aprendizaje</h3><p data-dashboard-training-summary></p></div>
            <button type="button" class="training-primary-button" data-learner-go-to="training">Ir a Mi capacitación <span aria-hidden="true">→</span></button>
        </div>
        <div class="learner-dashboard-grid">
            <section class="learner-dashboard-card" aria-labelledby="dashboard-tasks-title">
                <header><h3 id="dashboard-tasks-title">Tareas pendientes</h3><button type="button" data-learner-go-to="tasks">Ver tareas <span aria-hidden="true">→</span></button></header>
                <div data-dashboard-tasks></div>
            </section>
            <section class="learner-dashboard-card" aria-labelledby="dashboard-exams-title">
                <header><h3 id="dashboard-exams-title">Exámenes pendientes</h3><button type="button" data-learner-go-to="assigned-exams">Ver exámenes <span aria-hidden="true">→</span></button></header>
                <div data-dashboard-exams></div>
            </section>
            <section class="learner-dashboard-card" aria-labelledby="dashboard-results-title">
                <header><h3 id="dashboard-results-title">Resultados recientes</h3><button type="button" data-learner-go-to="results">Ver resultados <span aria-hidden="true">→</span></button></header>
                <div data-dashboard-results></div>
            </section>
            <section class="learner-dashboard-card" aria-labelledby="dashboard-certificates-title">
                <header><h3 id="dashboard-certificates-title">Certificaciones</h3><button type="button" data-learner-go-to="certificates">Ver certificaciones <span aria-hidden="true">→</span></button></header>
                <p class="learner-dashboard-note">Consulta con tu administrador la emisión de los certificados de tus capacitaciones concluidas.</p>
                <div data-dashboard-certificates></div>
            </section>
        </div>
    </div>
</section>
@push('css')
<style>
.learner-dashboard { padding:26px; border:1px solid #d6e0f0; border-radius:12px; background:#f8fafd; color:#123157; }
.learner-dashboard-heading { display:flex; justify-content:space-between; align-items:center; gap:20px; margin-bottom:22px; }
.learner-dashboard-eyebrow { color:#078a84; font-size:11px; font-weight:700; letter-spacing:.1em; }
.learner-dashboard h2 { margin:5px 0; font-size:25px; font-weight:600; }
.learner-dashboard-heading p { margin:5px 0 0; font-size:13px; color:#60708d; }
.learner-dashboard [data-dashboard-student-name] { font-weight:600; color:#244a73; }
.learner-dashboard-message { font-size:14px; color:#60708d; }
.learner-dashboard-message:empty { display:none; }
.learner-dashboard-metrics { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:14px; }
.learner-dashboard-metric { display:flex; flex-direction:column; align-items:flex-start; gap:8px; padding:18px; border:1px solid #d6e0f0; border-radius:10px; background:#fff; color:#123157; text-align:left; min-width:0; }
.learner-dashboard-metric:hover { border-color:#18a99c; background:#f5fcfa; }
.learner-dashboard-metric:focus-visible, .learner-dashboard-card button:focus-visible { outline:2px solid #2583ed; outline-offset:3px; }
.learner-dashboard-metric-label { display:flex; align-items:center; gap:9px; font-size:13px; font-weight:600; }
.learner-dashboard-icon { display:grid; place-items:center; flex-shrink:0; width:32px; height:32px; border-radius:9px; background:#e6f6f3; color:#008f83; }
.learner-dashboard-icon svg { width:18px; height:18px; }
.learner-dashboard-metric:nth-child(2) .learner-dashboard-icon { color:#a66c15; background:#fff5df; }
.learner-dashboard-metric:nth-child(3) .learner-dashboard-icon { color:#4458bc; background:#edf0ff; }
.learner-dashboard-metric:nth-child(4) .learner-dashboard-icon { color:#087caa; background:#e7f6fc; }
.learner-dashboard-metric:nth-child(5) .learner-dashboard-icon { color:#8d50ba; background:#f5edfb; }
.learner-dashboard-metric > strong { font-size:29px; font-weight:700; line-height:1.2; }
.learner-dashboard-metric > small { color:#60708d; font-size:12px; line-height:1.5; }
.learner-dashboard-next { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:20px; border:1px solid #b8e5dd; border-radius:10px; background:#effaf7; margin:20px 0; }
.learner-dashboard h3 { font-size:16px; font-weight:600; margin:0; }
.learner-dashboard-next p { font-size:13px; margin:6px 0 0; color:#50718a; }
.learner-dashboard-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; }
.learner-dashboard-card { min-width:0; padding:20px; border:1px solid #d6e0f0; border-radius:10px; background:#fff; }
.learner-dashboard-card > header { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:10px; margin-bottom:16px; }
.learner-dashboard-card button { color:#00897d; font-size:12px; font-weight:600; padding:4px 0; }
.learner-dashboard-note, .learner-dashboard-empty { font-size:13px; color:#60708d; line-height:1.6; }
.learner-dashboard-empty { padding:16px 0; }
.learner-dashboard-row { display:flex; justify-content:space-between; align-items:center; gap:14px; padding:13px 0; border-top:1px solid #edf1f7; }
.learner-dashboard-row > div { min-width:0; }
.learner-dashboard-row strong { display:block; font-size:13px; font-weight:600; overflow-wrap:anywhere; }
.learner-dashboard-row small { display:block; margin-top:4px; color:#60708d; font-size:12px; overflow-wrap:anywhere; }
.learner-dashboard-row .assigned-status { flex-shrink:0; font-size:11px; }
@media(max-width:1100px) { .learner-dashboard-metrics { grid-template-columns:repeat(3,minmax(0,1fr)); } }
@media(max-width:700px) { .learner-dashboard { padding:16px; } .learner-dashboard-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; } .learner-dashboard-metric { padding:14px; } .learner-dashboard-metric:last-child { grid-column:1/-1; } .learner-dashboard-heading, .learner-dashboard-next { align-items:flex-start; flex-direction:column; } .learner-dashboard-heading { gap:12px; } .learner-dashboard-grid { grid-template-columns:1fr; } .learner-dashboard-card { padding:16px; } .learner-dashboard-row { align-items:flex-start; flex-direction:column; gap:8px; } }
</style>
@endpush
