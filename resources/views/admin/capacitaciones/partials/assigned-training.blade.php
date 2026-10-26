<section class="training-own-assignments" data-own-assignments aria-labelledby="own-training-title">
    <header>
        <div>
            <h2 id="own-training-title">Mis capacitaciones asignadas</h2>
            <p>{{ trim(auth()->user()?->name.' '.auth()->user()?->lastname) }} · Programas y módulos asignados por tu administrador.</p>
        </div>
        <button type="button" class="training-secondary-button" data-refresh-assignments>Actualizar</button>
    </header>
    <p data-own-assignment-status role="status">Cargando capacitaciones…</p>
    <div data-own-assignment-list class="training-own-assignment-list"></div>
</section>
<dialog class="training-program-modal training-assigned-reader" data-assigned-reader aria-labelledby="assigned-reader-title">
    <header>
        <div><span>Mi capacitación</span><h3 id="assigned-reader-title" data-assigned-reader-title></h3></div>
        <button type="button" class="training-modal-close" data-close-assigned-reader aria-label="Cerrar capacitación">×</button>
    </header>
    <div class="training-assigned-reader-body">
        <label>Módulo <select data-assigned-module aria-label="Módulo asignado"></select></label>
        <p data-assigned-description></p>
        <div data-assigned-chapters></div>
    </div>
</dialog>
@push('css')
<style>
[data-learner-panel="home"] > [hidden] { display:none !important; }
.training-obligation-flow[hidden] { display:none !important; }
.training-own-assignments { padding:24px; border:1px solid #d6e0f0; border-radius:12px; margin-bottom:20px; background:#fff; }
.training-own-assignments header, .training-own-assignment-card { display:flex; align-items:center; justify-content:space-between; gap:20px; }
.training-own-assignments h2 { font-size:23px; margin:0 0 8px; }
.training-own-assignments p, .training-own-assignment-card small { color:#60708d; }
.training-own-assignment-list { display:grid; gap:12px; margin-top:20px; }
.training-own-assignment-card { border:1px solid #b8e5dd; background:#f4fcfa; border-radius:10px; padding:20px; }
.training-own-assignment-card h3 { margin:0 0 8px; font-size:17px; }
.training-own-assignment-card p { margin:5px 0; }
.training-assigned-reader { width:min(1040px,94vw); max-width:94vw; max-height:90vh; padding:0; }
.training-assigned-reader header { display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:1px solid #d6e0f0; }
.training-assigned-reader-body { padding:24px; max-height:72vh; overflow:auto; }
.training-assigned-reader label { display:grid; gap:8px; }
.training-assigned-reader select { border:1px solid #c4d1e5; padding:10px; border-radius:6px; width:100%; }
.training-assigned-reader article { border:1px solid #d6e0f0; padding:18px; border-radius:8px; margin-top:14px; }
.training-assigned-reader video { display:block; max-width:100%; max-height:360px; margin-top:12px; }
.training-assigned-reader a { display:inline-block; color:#008b80; text-decoration:underline; margin-top:8px; }
[data-assignment-error] { color:#b42318; }
@media(max-width:640px) { .training-own-assignments header, .training-own-assignment-card { align-items:stretch; flex-direction:column; } .training-own-assignments { padding:16px; } }
</style>
@endpush
