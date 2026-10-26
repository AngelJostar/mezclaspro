<div id="learner-panel-continuous" class="training-learner-view" data-learner-panel="continuous"
    role="tabpanel" aria-labelledby="learner-tab-continuous" hidden>
    <section class="training-learner-section continuous-training" data-continuous-library
        data-catalog-url="{{ route('admin.capacitaciones.continua.index') }}">
        <header class="training-learner-section-heading">
            <div>
                <span class="continuous-training-badge">Consulta libre</span>
                <h2>Capacitación Continua</h2>
                <p>Revisa tus programas, módulos y capítulos habilitados cuando lo necesites, en el orden que prefieras.</p>
            </div>
            <button type="button" class="training-secondary-button" data-continuous-refresh>
                <i class="fa-solid fa-rotate" aria-hidden="true"></i> Actualizar
            </button>
        </header>
        <div class="continuous-training-notice" role="status" aria-live="polite" data-continuous-status></div>
        <div class="continuous-training-empty" data-continuous-empty hidden>
            <i class="fa-solid fa-book-open" aria-hidden="true"></i>
            <h3>Aún no tienes programas habilitados</h3>
            <p>Cuando el administrador habilite programas para ti, podrás consultarlos aquí.</p>
        </div>
        <div data-continuous-content hidden>
            <div class="continuous-training-program">
                <label for="continuous-program">Programa de capacitación
                    <select id="continuous-program" data-continuous-program></select>
                </label>
                <p data-continuous-description></p>
            </div>
            <div class="continuous-training-grid">
                <aside class="continuous-training-modules" aria-label="Módulos disponibles">
                    <h3>Módulos del programa</h3>
                    <div data-continuous-modules></div>
                </aside>
                <section class="continuous-training-reader" aria-label="Contenido del módulo" data-continuous-reader></section>
            </div>
        </div>
    </section>
</div>

@push('css')
<style>
    .training-tabs.training-learner-tabs {
        grid-template-columns: none;
        grid-auto-flow: column;
        grid-auto-columns: minmax(9.25rem, 1fr);
        overflow-x: auto;
    }
    .training-learner-tabs button[hidden], .training-obligation-flow[hidden], .continuous-training [hidden] { display: none !important; }
    .continuous-training .training-learner-section-heading { flex-wrap: wrap; gap: 1rem; }
    .continuous-training .training-learner-section-heading > div { flex: 1 1 22rem; }
    .continuous-training .training-secondary-button { flex-shrink: 0; }
    .continuous-training-badge { display: inline-block; margin-bottom: .5rem; border-radius: 1rem; padding: .25rem .75rem; background: #e3f8f1; color: #007b69; font-size: .8rem; font-weight: 600; }
    .continuous-training-notice:not(:empty) { margin: 1rem 0; padding: .8rem 1rem; border-radius: .5rem; background: #f0f5ff; color: #254573; }
    .continuous-training-notice.is-error { background: #fff2ed; color: #9b3218; }
    .continuous-training-program { margin: 1.25rem 0; }
    .continuous-training-program label { display: grid; gap: .5rem; font-weight: 600; max-width: 40rem; }
    .continuous-training-program select { width: 100%; border: 1px solid var(--training-line); border-radius: .5rem; padding: .7rem 2.5rem .7rem .8rem; background-color: white; font-weight: 400; }
    .continuous-training-program p { color: #52687f; margin-top: .75rem; white-space: pre-wrap; }
    .continuous-training-grid { display: grid; grid-template-columns: minmax(13rem, 30%) minmax(0, 1fr); gap: 1rem; }
    .continuous-training-modules, .continuous-training-reader { min-width: 0; border: 1px solid var(--training-line); border-radius: .6rem; overflow-wrap: anywhere; }
    .continuous-training-modules { align-self: start; overflow: hidden; }
    .continuous-training-modules h3 { padding: 1rem; font-weight: 600; border-bottom: 1px solid var(--training-line); }
    .continuous-training-modules button { display: grid; gap: .4rem; width: 100%; padding: 1rem; text-align: left; border: 0; border-bottom: 1px solid var(--training-line); background: white; color: #173451; cursor: pointer; }
    .continuous-training-modules button:last-child { border-bottom: 0; }
    .continuous-training-modules button[aria-pressed="true"] { background: #eff7ff; box-shadow: inset 3px 0 #3865df; }
    .continuous-training-modules button small { color: #52687f; }
    .continuous-training-reader { padding: 1.25rem; }
    .continuous-training-reader h3 { font-size: 1.1rem; font-weight: 600; margin: .3rem 0 .7rem; }
    .continuous-training-reader > p { color: #52687f; white-space: pre-wrap; }
    .continuous-training-chapter { margin-top: 1rem; border: 1px solid var(--training-line); border-radius: .5rem; overflow: hidden; }
    .continuous-training-chapter summary { padding: 1rem; cursor: pointer; background: #f7faff; font-weight: 600; }
    .continuous-training-chapter summary small { margin-left: .8rem; font-weight: 400; color: #52687f; }
    .continuous-training-chapter-body { padding: 1rem; }
    .continuous-training-chapter-body p { white-space: pre-wrap; margin-bottom: .75rem; }
    .continuous-training-chapter video { display: block; width: 100%; max-height: 28rem; margin: .75rem 0; border-radius: .4rem; background: #13263f; }
    .continuous-training-chapter a { display: inline-flex; align-items: center; gap: .5rem; color: #1958bb; text-decoration: underline; }
    .continuous-training-empty { padding: 2.5rem 1rem; text-align: center; color: #52687f; }
    .continuous-training-empty h3 { font-weight: 600; color: #173451; margin: .75rem 0; }
    .continuous-training :is(button, select, summary, a):focus-visible { outline: 2px solid #3865df; outline-offset: -2px; }
    @media (max-width: 760px) { .continuous-training-grid { grid-template-columns: minmax(0, 1fr); } }
</style>
@endpush
