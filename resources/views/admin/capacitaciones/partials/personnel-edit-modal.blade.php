<dialog class="personnel-edit-modal" data-personnel-edit-modal aria-labelledby="personnel-edit-title">
    <form method="POST" enctype="multipart/form-data" data-personnel-edit-form>
        @csrf
        @method('PATCH')
        <header class="personnel-edit-header">
            <div>
                <h2 id="personnel-edit-title">Editar personal</h2>
                <p data-personnel-edit-name></p>
            </div>
            <button type="button" class="personnel-edit-close" data-close-personnel-edit
                aria-label="Cerrar edicion de personal" title="Cerrar">
                <i data-personnel-icon="x" aria-hidden="true"></i>
            </button>
        </header>

        <div class="personnel-edit-content">
            <p data-personnel-edit-loading role="status">Cargando informacion...</p>
            <div class="personnel-edit-errors" data-personnel-edit-errors role="alert" tabindex="-1" hidden></div>
            <fieldset data-personnel-edit-fields disabled hidden>
                <section class="personnel-edit-section" aria-labelledby="personnel-edit-personal-title">
                    <h3 id="personnel-edit-personal-title">Datos personales</h3>
                    <div class="personnel-fields-grid is-three-columns">
                        <label class="personnel-field">
                            <span>Nombre(s) *</span>
                            <input name="first_name" required maxlength="120" autocomplete="given-name">
                        </label>
                        <label class="personnel-field">
                            <span>Apellido paterno *</span>
                            <input name="paternal_surname" required maxlength="120" autocomplete="family-name">
                        </label>
                        <label class="personnel-field">
                            <span>Apellido materno</span>
                            <input name="maternal_surname" maxlength="120">
                        </label>
                    </div>
                    <div class="personnel-fields-grid is-two-columns">
                        <label class="personnel-field">
                            <span>Telefono</span>
                            <input type="tel" name="phone" maxlength="40" autocomplete="tel">
                        </label>
                        <label class="personnel-field">
                            <span>Correo personal *</span>
                            <input type="email" name="personal_email" required maxlength="255" autocomplete="email">
                        </label>
                    </div>
                </section>

                <section class="personnel-edit-section" aria-labelledby="personnel-edit-employment-title">
                    <h3 id="personnel-edit-employment-title">Informacion laboral</h3>
                    <div class="personnel-fields-grid is-three-columns">
                        <label class="personnel-field">
                            <span>Central de mezclas *</span>
                            <select name="laboratory_id" required>
                                <option value="">Selecciona una central</option>
                                @foreach ($laboratories as $laboratory)
                                    <option value="{{ $laboratory->id }}">{{ $laboratory->nombre }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="personnel-field">
                            <span>Departamento *</span>
                            <select name="department" required>
                                <option value="">Selecciona un departamento</option>
                                @foreach (['Administracion', 'Almacen', 'Calidad', 'Operaciones', 'Produccion'] as $department)
                                    <option value="{{ $department }}">{{ $department }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="personnel-field">
                            <span>Fecha de ingreso *</span>
                            <input type="date" name="hire_date" required max="{{ now()->toDateString() }}">
                        </label>
                    </div>
                    <fieldset class="personnel-edit-jobs">
                        <legend>Puestos asignados *</legend>
                        <div class="personnel-edit-job-groups">
                            @foreach ($jobCatalog as $group => $jobs)
                                <div>
                                    <h4>{{ $group }}</h4>
                                    @foreach ($jobs as $job)
                                        <label>
                                            <input type="checkbox" name="positions[]" value="{{ $job }}">
                                            <span>{{ $job }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                            <div data-personnel-edit-legacy-jobs hidden></div>
                        </div>
                    </fieldset>
                </section>

                <section class="personnel-edit-section" aria-labelledby="personnel-edit-profile-title">
                    <h3 id="personnel-edit-profile-title">Perfil y documentacion</h3>
                    <label class="personnel-field">
                        <span>Curriculum vitae</span>
                        <span class="personnel-edit-cv" data-personnel-edit-cv></span>
                        <input type="file" name="cv" accept=".pdf,.doc,.docx">
                    </label>
                    <div class="personnel-fields-grid is-two-columns">
                        <label class="personnel-field">
                            <span>Experiencia laboral anterior</span>
                            <textarea name="prior_experience" rows="3" maxlength="3000"></textarea>
                        </label>
                        <label class="personnel-field">
                            <span>Informacion adicional</span>
                            <textarea name="additional_information" rows="3" maxlength="5000"></textarea>
                        </label>
                    </div>
                </section>
            </fieldset>
        </div>
        <footer class="personnel-edit-footer">
            <button type="button" class="training-secondary-button" data-close-personnel-edit>Cancelar</button>
            <button type="submit" class="training-primary-button" data-personnel-edit-save disabled>Guardar cambios</button>
        </footer>
    </form>
</dialog>

@push('css')
    <style>
        .personnel-edit-modal {
            width: min(960px, calc(100% - 24px));
            max-width: none;
            max-height: calc(100dvh - 24px);
            padding: 0;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            color: #243b53;
            background: #fff;
            box-shadow: 0 20px 60px #10285240;
        }
        .personnel-edit-modal::backdrop { background: #10233280; }
        .personnel-edit-modal form { display: flex; flex-direction: column; max-height: calc(100dvh - 26px); }
        .personnel-edit-header, .personnel-edit-footer { display: flex; align-items: center; gap: 16px; padding: 16px 20px; flex-shrink: 0; }
        .personnel-edit-header { justify-content: space-between; border-bottom: 1px solid #d9e2ec; }
        .personnel-edit-header h2 { margin: 0; font-size: 20px; font-weight: 600; }
        .personnel-edit-header p { margin: 4px 0 0; font-size: 14px; overflow-wrap: anywhere; }
        .personnel-edit-close { display: grid; place-items: center; width: 36px; height: 36px; flex: 0 0 36px; border: 1px solid #d9e2ec; border-radius: 6px; color: #52687f; }
        .personnel-edit-close:hover { background: #f1f5f9; color: #b42332; }
        .personnel-edit-close svg { width: 20px; height: 20px; }
        .personnel-edit-content { padding: 0 20px; min-height: 0; overflow-y: auto; }
        .personnel-edit-content > p { padding: 24px 0; }
        .personnel-edit-content [hidden] { display: none; }
        .personnel-edit-section { padding: 20px 0; border-bottom: 1px solid #e7edf5; }
        .personnel-edit-section:last-child { border: 0; }
        .personnel-edit-section h3 { margin: 0 0 14px; font-size: 16px; font-weight: 600; }
        .personnel-edit-section .personnel-fields-grid + .personnel-fields-grid,
        .personnel-edit-section > .personnel-field + .personnel-fields-grid { margin-top: 14px; }
        .personnel-edit-modal .personnel-field { font-size: 13px; }
        .personnel-edit-modal .personnel-field > span { font-size: 13px; }
        .personnel-edit-modal input:not([type=checkbox]), .personnel-edit-modal select, .personnel-edit-modal textarea { font-size: 14px; min-height: 38px; }
        .personnel-edit-modal [aria-invalid=true] { border-color: #b42332; }
        .personnel-edit-jobs { margin-top: 18px; }
        .personnel-edit-jobs legend { font-size: 13px; font-weight: 600; margin-bottom: 10px; }
        .personnel-edit-job-groups { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .personnel-edit-job-groups h4 { font-size: 13px; font-weight: 600; margin-bottom: 8px; }
        .personnel-edit-job-groups label { display: flex; align-items: flex-start; gap: 8px; font-size: 13px; line-height: 1.4; padding: 4px 0; }
        .personnel-edit-job-groups input { width: 16px; height: 16px; margin-top: 1px; flex: 0 0 16px; accent-color: #0f9987; }
        .personnel-edit-cv { color: #62788c; overflow-wrap: anywhere; }
        .personnel-edit-footer { justify-content: flex-end; border-top: 1px solid #d9e2ec; }
        .personnel-edit-footer button:disabled { opacity: .55; cursor: wait; }
        .personnel-edit-errors { margin-top: 16px; padding: 12px 16px; border: 1px solid #f0b5b5; border-radius: 6px; background: #fff5f5; color: #a32532; font-size: 14px; }
        .personnel-edit-errors ul { list-style: disc; padding-left: 18px; }
        @media (max-width: 720px) {
            .personnel-edit-header, .personnel-edit-footer { padding: 12px; }
            .personnel-edit-content { padding: 0 12px; }
            .personnel-edit-job-groups { grid-template-columns: 1fr; gap: 12px; }
            .personnel-edit-footer { gap: 8px; }
        }
    </style>
@endpush
