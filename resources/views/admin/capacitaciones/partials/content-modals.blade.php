<dialog class="training-program-modal" data-content-dialog aria-labelledby="training-content-title">
    <form data-content-form enctype="multipart/form-data">
        <header><div><span data-content-program></span><h3 id="training-content-title"></h3></div>
            <button type="button" class="training-modal-close" data-close-content aria-label="Cerrar edición">×</button></header>
        <div class="training-program-modal-body" data-content-fields></div>
        <p class="training-program-error" data-content-error role="alert" hidden></p>
        <footer><button type="button" class="training-secondary-button" data-close-content>Cancelar</button>
            <button type="submit" class="training-primary-button" data-content-save>Guardar cambios</button></footer>
    </form>
</dialog>
<dialog class="training-program-modal" data-content-confirm aria-labelledby="training-content-confirm-title">
    <form data-content-confirm-form>
        <header><h3 id="training-content-confirm-title">Eliminar capítulo</h3>
            <button type="button" class="training-modal-close" data-cancel-content-delete aria-label="Cerrar confirmación">×</button></header>
        <div class="training-program-modal-body"><p class="is-wide" data-content-confirm-message></p></div>
        <p class="training-program-error" role="alert" data-content-delete-error hidden></p>
        <footer><button type="button" class="training-secondary-button" data-cancel-content-delete>No, conservar</button>
            <button type="submit" class="training-primary-button">Sí, eliminar</button></footer>
    </form>
</dialog>
<p class="training-exam-save-feedback" data-content-feedback role="status" hidden></p>
<style>
    [data-content-dialog] { width: min(46rem, calc(100vw - 2rem)); }
    [data-content-dialog] input[type="checkbox"], [data-exam-editor-form] input[type="checkbox"] { width: 1.2rem; height: 1.2rem; min-height: 0; padding: 0; margin-right: .5rem; justify-self: start; accent-color: #00a58c; }
    [data-content-dialog] input[type="file"] { width: 100%; font-size: .8rem; }
    [data-content-dialog] small { display: block; color: var(--training-soft); }
    [data-content-confirm] p { margin: 0; }
    [data-content-error], [data-content-delete-error], [data-exam-persistence-error] { margin: 0 1rem 1rem; color: #b42318; }
    [data-chapter-list] .training-chapter-row-heading { gap: .5rem; flex-wrap: wrap; }
    [data-chapter-list] .training-chapter-row-heading > strong { margin-right: auto; }
    [data-chapter-list] .training-chapter-body p { white-space: pre-wrap; overflow-wrap: anywhere; }
    [data-chapter-list] .training-chapter-body small { display: block; color: var(--training-soft); margin-top: .4rem; }
    [data-chapter-list] .training-chapter-body a { color: var(--training-teal-dark); text-decoration: underline; display: inline-block; margin: .5rem 1rem 0 0; }
    [data-module-empty] { padding: 1rem; color: var(--training-soft); }
</style>
