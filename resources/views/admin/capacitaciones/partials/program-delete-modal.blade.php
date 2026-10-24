<dialog class="training-program-modal" data-delete-program-modal aria-labelledby="delete-program-title" aria-describedby="delete-program-message">
    <header><h3 id="delete-program-title">Eliminar programa</h3></header>
    <div class="training-program-modal-body">
        <p class="is-wide" id="delete-program-message" data-delete-program-message></p>
        <p class="is-wide training-program-error" data-delete-program-error role="alert" hidden></p>
    </div>
    <footer>
        <button type="button" class="training-secondary-button" data-cancel-delete-program autofocus>No</button>
        <button type="button" class="training-primary-button training-delete-confirm" data-confirm-delete-program>Sí</button>
    </footer>
</dialog>
@push('css')
<style>
    .training-delete-program { margin-right: auto; color: #b42318; border-color: #f3b4ae; }
    .training-delete-confirm { background: #b42318; border-color: #b42318; }
    .training-program-error { color: #b42318; margin: 0; padding: .75rem 1.15rem; }
    .training-program-modal button:disabled { opacity: .6; cursor: wait; }
</style>
@endpush
