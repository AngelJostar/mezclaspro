@php
    $selectedPositions = old('positions', []);
    $personnelFields = [
        'first_name',
        'paternal_surname',
        'maternal_surname',
        'phone',
        'personal_email',
        'laboratory_id',
        'department',
        'hire_date',
        'employment_status',
        'positions',
        'username',
        'password',
        'cv',
        'prior_experience',
        'additional_information',
    ];
    $openPersonnelModal = $errors->hasAny($personnelFields);
@endphp

<dialog class="personnel-create-modal" data-personnel-create-modal
    data-open-on-errors="{{ $openPersonnelModal ? 'true' : 'false' }}"
    aria-labelledby="personnel-create-title">
    <form method="POST" action="{{ route('admin.capacitaciones.personal.store') }}" enctype="multipart/form-data"
        data-personnel-create-form>
        @csrf

        <header class="personnel-create-header">
            <div>
                <p>Personal y capacitaciones / Personal / Nuevo</p>
                <h3 id="personnel-create-title">Alta de personal</h3>
                <span>Registra sus datos, adscripci&oacute;n y acceso al sistema.</span>
            </div>

            <div class="personnel-create-header-actions">
                <button type="button" class="training-secondary-button" data-close-personnel-create>Cancelar</button>
                <button type="submit" class="training-primary-button" data-save-personnel>
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    <span>Guardar personal</span>
                </button>
            </div>
        </header>

        @if ($openPersonnelModal)
            <div class="personnel-validation-summary" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <div>
                    <strong>Revisa la informaci&oacute;n marcada.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="personnel-create-body">
            <main class="personnel-form-sections">
                <section class="personnel-form-section">
                    <h4><span>1</span> Datos personales</h4>

                    <div class="personnel-fields-grid is-three-columns">
                        <label class="personnel-field">
                            <span>Nombre <b>*</b></span>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" maxlength="120"
                                autocomplete="given-name" required data-personnel-first-name>
                            @error('first_name')<small>{{ $message }}</small>@enderror
                        </label>

                        <label class="personnel-field">
                            <span>Apellido paterno <b>*</b></span>
                            <input type="text" name="paternal_surname" value="{{ old('paternal_surname') }}"
                                maxlength="120" autocomplete="family-name" required data-personnel-paternal-surname>
                            @error('paternal_surname')<small>{{ $message }}</small>@enderror
                        </label>

                        <label class="personnel-field">
                            <span>Apellido materno</span>
                            <input type="text" name="maternal_surname" value="{{ old('maternal_surname') }}"
                                maxlength="120" data-personnel-maternal-surname>
                            @error('maternal_surname')<small>{{ $message }}</small>@enderror
                        </label>
                    </div>

                    <h5>Datos de contacto</h5>
                    <div class="personnel-fields-grid is-two-columns">
                        <label class="personnel-field">
                            <span>Tel&eacute;fono</span>
                            <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="40"
                                autocomplete="tel">
                            @error('phone')<small>{{ $message }}</small>@enderror
                        </label>

                        <label class="personnel-field">
                            <span>Correo personal <b>*</b></span>
                            <input type="email" name="personal_email" value="{{ old('personal_email') }}"
                                maxlength="255" autocomplete="email" required>
                            @error('personal_email')<small>{{ $message }}</small>@enderror
                        </label>
                    </div>
                </section>

                <section class="personnel-form-section">
                    <h4><span>2</span> Adscripci&oacute;n laboral</h4>

                    <div class="personnel-fields-grid is-three-columns">
                        <label class="personnel-field">
                            <span>Central de mezclas <b>*</b></span>
                            <select name="laboratory_id" data-personnel-laboratory required>
                                <option value="">Selecciona una central</option>
                                @foreach ($laboratories as $laboratory)
                                    <option value="{{ $laboratory->id }}" @selected((string) old('laboratory_id') === (string) $laboratory->id)>
                                        {{ $laboratory->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('laboratory_id')<small>{{ $message }}</small>@enderror
                        </label>

                        <label class="personnel-field">
                            <span>Departamento <b>*</b></span>
                            <select name="department" required>
                                <option value="">Selecciona un departamento</option>
                                @foreach (['Administracion', 'Almacen', 'Calidad', 'Operaciones', 'Produccion'] as $department)
                                    <option value="{{ $department }}" @selected(old('department') === $department)>{{ $department }}</option>
                                @endforeach
                            </select>
                            @error('department')<small>{{ $message }}</small>@enderror
                        </label>

                        <label class="personnel-field">
                            <span>Fecha de ingreso <b>*</b></span>
                            <input type="date" name="hire_date" value="{{ old('hire_date', now()->toDateString()) }}"
                                max="{{ now()->toDateString() }}" required>
                            @error('hire_date')<small>{{ $message }}</small>@enderror
                        </label>
                    </div>

                    <div class="personnel-employment-row">
                        <div>
                            <span class="personnel-inline-label">Personal activo</span>
                            <input type="hidden" name="employment_status" value="{{ old('employment_status', 'hired') }}"
                                data-employment-status-input>
                            <label class="personnel-toggle">
                                <input type="checkbox" @checked(old('employment_status', 'hired') === 'hired')
                                    data-employment-toggle>
                                <span aria-hidden="true"></span>
                                <b data-employment-label>{{ old('employment_status', 'hired') === 'hired' ? 'Activo' : 'Baja' }}</b>
                            </label>
                        </div>

                        <div class="personnel-selected-positions">
                            <span class="personnel-inline-label">Puestos asignados <b>*</b></span>
                            <div data-selected-position-chips>
                                @forelse ($selectedPositions as $position)
                                    <span>{{ $position }}</span>
                                @empty
                                    <em>Selecciona uno o varios puestos del cat&aacute;logo.</em>
                                @endforelse
                            </div>
                            @error('positions')<small class="personnel-field-error">{{ $message }}</small>@enderror
                            @error('positions.*')<small class="personnel-field-error">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </section>

                <section class="personnel-form-section">
                    <h4><span>3</span> Acceso al sistema</h4>

                    <div class="personnel-credentials-grid">
                        <label class="personnel-field">
                            <span>Nombre de usuario <b>*</b></span>
                            <span class="personnel-input-with-action">
                                <input type="text" name="username" value="{{ old('username') }}" maxlength="30"
                                    autocomplete="off" required data-personnel-username>
                                <button type="button" title="Copiar usuario" aria-label="Copiar usuario"
                                    data-copy-personnel-field="username">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                </button>
                            </span>
                            @error('username')<small>{{ $message }}</small>@enderror
                        </label>

                        <label class="personnel-field">
                            <span>Contrase&ntilde;a definitiva <b>*</b></span>
                            <span class="personnel-input-with-action">
                                <input type="text" name="password" value="{{ old('password') }}"
                                    maxlength="50" autocomplete="new-password" required data-personnel-password>
                                <button type="button" title="Copiar contrase&ntilde;a" aria-label="Copiar contrase&ntilde;a"
                                    data-copy-personnel-field="password">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                </button>
                            </span>
                            @error('password')<small>{{ $message }}</small>@enderror
                        </label>

                        <button type="button" class="personnel-regenerate-button" data-regenerate-personnel-credentials>
                            <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                            <span>Regenerar credenciales</span>
                        </button>
                    </div>

                </section>

                <section class="personnel-form-section">
                    <h4><span>4</span> Perfil y documentaci&oacute;n</h4>

                    <div class="personnel-document-grid">
                        <label class="personnel-field">
                            <span>Curriculum vitae (PDF o DOCX)</span>
                            <span class="personnel-file-input">
                                <input type="file" name="cv" accept=".pdf,.doc,.docx" data-personnel-cv>
                                <i class="fa-solid fa-arrow-up-from-bracket" aria-hidden="true"></i>
                                <strong data-personnel-cv-label>Adjuntar CV</strong>
                                <small>M&aacute;x. 10 MB</small>
                            </span>
                            @error('cv')<small>{{ $message }}</small>@enderror
                        </label>

                        <label class="personnel-field">
                            <span>Experiencia laboral anterior</span>
                            <textarea name="prior_experience" rows="4" maxlength="3000">{{ old('prior_experience') }}</textarea>
                            @error('prior_experience')<small>{{ $message }}</small>@enderror
                        </label>
                    </div>

                    <label class="personnel-field">
                        <span>Informaci&oacute;n adicional</span>
                        <textarea name="additional_information" rows="3" maxlength="5000"
                            placeholder="Agrega observaciones, referencias o informaci&oacute;n relevante.">{{ old('additional_information') }}</textarea>
                        @error('additional_information')<small>{{ $message }}</small>@enderror
                    </label>
                </section>
            </main>

            <aside class="personnel-job-catalog">
                <div class="personnel-job-catalog-heading">
                    <h4>Cat&aacute;logo de puestos</h4>
                    <p>Selecciona uno o varios puestos.</p>
                </div>

                <label class="personnel-job-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" placeholder="Buscar puesto..." data-job-search>
                </label>

                <div class="personnel-job-groups">
                    @foreach ($jobCatalog as $group => $jobs)
                        <section data-job-group>
                            <h5>{{ $group }}</h5>
                            @foreach ($jobs as $job)
                                <label data-job-option data-job-name="{{ Str::lower($job) }}">
                                    <input type="checkbox" name="positions[]" value="{{ $job }}"
                                        @checked(in_array($job, $selectedPositions, true)) data-position-checkbox>
                                    <span>{{ $job }}</span>
                                </label>
                            @endforeach
                        </section>
                    @endforeach
                </div>

                <footer>
                    <strong data-position-count>{{ count($selectedPositions) }} puestos seleccionados</strong>
                    <button type="button" class="training-primary-button" data-apply-positions>
                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                        <span>Aplicar puestos</span>
                    </button>
                </footer>
            </aside>
        </div>
    </form>
</dialog>

@push('css')
    <style>
        .personnel-create-modal {
            width: min(76rem, calc(100vw - 2rem));
            max-width: none;
            max-height: calc(100vh - 2rem);
            margin: auto;
            border: 0;
            border-radius: 8px;
            padding: 0;
            color: #102a43;
            background: #f6f8fb;
            box-shadow: 0 26px 70px rgba(15, 23, 42, 0.26);
        }

        .personnel-create-modal::backdrop {
            background: rgba(15, 23, 42, 0.58);
            backdrop-filter: blur(2px);
        }

        .personnel-create-modal > form {
            display: grid;
            grid-template-rows: auto auto minmax(0, 1fr);
            max-height: calc(100vh - 2rem);
        }

        .personnel-create-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid #d9e2ec;
            padding: 1rem 1.15rem;
            background: #ffffff;
        }

        .personnel-create-header p,
        .personnel-create-header h3,
        .personnel-create-header span,
        .personnel-job-catalog h4,
        .personnel-job-catalog p {
            margin: 0;
        }

        .personnel-create-header p {
            margin-bottom: 0.18rem;
            color: #5d7590;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .personnel-create-header h3 {
            color: #102a43;
            font-size: 1.45rem;
            line-height: 1.2;
        }

        .personnel-create-header > div > span {
            color: #52687f;
            font-size: 0.78rem;
        }

        .personnel-create-header-actions {
            display: flex;
            flex: none;
            gap: 0.55rem;
        }

        .personnel-validation-summary {
            display: flex;
            gap: 0.7rem;
            margin: 0.8rem 1rem 0;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 0.75rem;
            color: #991b1b;
            background: #fff1f2;
            font-size: 0.76rem;
        }

        .personnel-validation-summary ul {
            margin: 0.25rem 0 0;
            padding-left: 1rem;
        }

        .personnel-create-body {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 20rem;
            gap: 0.9rem;
            min-height: 0;
            overflow-y: auto;
            padding: 0.9rem;
        }

        .personnel-form-sections {
            display: grid;
            align-content: start;
            gap: 0.7rem;
            min-width: 0;
        }

        .personnel-form-section,
        .personnel-job-catalog {
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            background: #ffffff;
        }

        .personnel-form-section {
            display: grid;
            gap: 0.72rem;
            padding: 0.85rem;
        }

        .personnel-form-section h4 {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            margin: 0;
            color: #172b4d;
            font-size: 0.9rem;
        }

        .personnel-form-section h4 > span {
            display: grid;
            width: 1.35rem;
            height: 1.35rem;
            place-items: center;
            border-radius: 50%;
            color: #ffffff;
            background: #2f3f82;
            font-size: 0.68rem;
        }

        .personnel-form-section h5 {
            margin: 0.05rem 0 -0.35rem;
            color: #52687f;
            font-size: 0.72rem;
        }

        .personnel-fields-grid,
        .personnel-document-grid,
        .personnel-credentials-grid {
            display: grid;
            gap: 0.7rem;
        }

        .personnel-fields-grid.is-three-columns {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .personnel-fields-grid.is-two-columns,
        .personnel-document-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .personnel-field {
            display: grid;
            min-width: 0;
            gap: 0.28rem;
            color: #31516f;
            font-size: 0.7rem;
            font-weight: 750;
        }

        .personnel-field b,
        .personnel-inline-label b {
            color: #dc2626;
        }

        .personnel-field input,
        .personnel-field select,
        .personnel-field textarea,
        .personnel-job-search input {
            width: 100%;
            min-height: 2.35rem;
            border: 1px solid #cbd6e2;
            border-radius: 6px;
            padding: 0.52rem 0.65rem;
            color: #102a43;
            background: #ffffff;
            font-size: 0.78rem;
            font-weight: 500;
        }

        .personnel-field textarea {
            min-height: 4.4rem;
            resize: vertical;
        }

        .personnel-field input:focus,
        .personnel-field select:focus,
        .personnel-field textarea:focus,
        .personnel-job-search input:focus {
            outline: 2px solid rgba(29, 112, 216, 0.18);
            border-color: #1d70d8;
        }

        .personnel-field > small,
        .personnel-field-error {
            color: #b91c1c;
            font-size: 0.66rem;
            font-weight: 700;
        }

        .personnel-employment-row {
            display: grid;
            grid-template-columns: 11rem minmax(0, 1fr);
            gap: 0.8rem;
            align-items: start;
        }

        .personnel-employment-row > div {
            display: grid;
            gap: 0.36rem;
        }

        .personnel-inline-label {
            color: #31516f;
            font-size: 0.7rem;
            font-weight: 750;
        }

        .personnel-toggle {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            gap: 0.45rem;
            cursor: pointer;
        }

        .personnel-toggle input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .personnel-toggle > span {
            position: relative;
            width: 2.25rem;
            height: 1.25rem;
            border-radius: 999px;
            background: #94a3b8;
            transition: background-color 160ms ease;
        }

        .personnel-toggle > span::after {
            position: absolute;
            top: 0.16rem;
            left: 0.17rem;
            width: 0.92rem;
            height: 0.92rem;
            border-radius: 50%;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.3);
            content: '';
            transition: transform 160ms ease;
        }

        .personnel-toggle input:checked + span {
            background: #0fa98f;
        }

        .personnel-toggle input:checked + span::after {
            transform: translateX(1rem);
        }

        .personnel-toggle b {
            color: #31516f;
            font-size: 0.72rem;
        }

        .personnel-selected-positions > div {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            min-height: 2rem;
        }

        .personnel-selected-positions > div > span {
            border: 1px solid #b8c9e8;
            border-radius: 5px;
            padding: 0.32rem 0.5rem;
            color: #194f9d;
            background: #eef5ff;
            font-size: 0.68rem;
            font-weight: 750;
        }

        .personnel-selected-positions em {
            align-self: center;
            color: #7890a8;
            font-size: 0.7rem;
        }

        .personnel-credentials-grid {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
            align-items: end;
        }

        .personnel-input-with-action {
            position: relative;
            display: block;
        }

        .personnel-input-with-action input {
            padding-right: 2.45rem;
        }

        .personnel-input-with-action button {
            position: absolute;
            top: 50%;
            right: 0.25rem;
            display: grid;
            width: 1.8rem;
            height: 1.8rem;
            place-items: center;
            border: 0;
            border-radius: 5px;
            color: #31516f;
            background: #eef2f7;
            transform: translateY(-50%);
        }

        .personnel-regenerate-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 2.35rem;
            border: 1px solid #1d70d8;
            border-radius: 6px;
            padding: 0.45rem 0.7rem;
            color: #1958bb;
            background: #ffffff;
            font-size: 0.7rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .personnel-file-input {
            display: grid;
            min-height: 5.5rem;
            place-items: center;
            align-content: center;
            gap: 0.15rem;
            border: 1px dashed #829ab1;
            border-radius: 6px;
            color: #1958bb;
            background: #fbfdff;
            cursor: pointer;
        }

        .personnel-file-input input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
        }

        .personnel-file-input small {
            color: #7890a8;
            font-size: 0.62rem;
        }

        .personnel-job-catalog {
            position: sticky;
            top: 0;
            display: grid;
            grid-template-rows: auto auto minmax(0, 1fr) auto;
            align-self: start;
            max-height: calc(100vh - 8rem);
            overflow: hidden;
        }

        .personnel-job-catalog-heading {
            padding: 0.9rem 0.9rem 0.55rem;
        }

        .personnel-job-catalog-heading h4 {
            color: #172b4d;
            font-size: 0.95rem;
        }

        .personnel-job-catalog-heading p {
            margin-top: 0.18rem;
            color: #52687f;
            font-size: 0.7rem;
        }

        .personnel-job-search {
            position: relative;
            display: block;
            margin: 0 0.9rem 0.55rem;
        }

        .personnel-job-search i {
            position: absolute;
            top: 50%;
            left: 0.65rem;
            color: #7890a8;
            transform: translateY(-50%);
        }

        .personnel-job-search input {
            padding-left: 2rem;
        }

        .personnel-job-groups {
            min-height: 0;
            overflow-y: auto;
            padding: 0 0.9rem 0.7rem;
        }

        .personnel-job-groups section {
            border-top: 1px solid #e7edf5;
            padding: 0.65rem 0;
        }

        .personnel-job-groups section[hidden],
        .personnel-job-groups label[hidden] {
            display: none;
        }

        .personnel-job-groups h5 {
            margin: 0 0 0.42rem;
            color: #31516f;
            font-size: 0.72rem;
        }

        .personnel-job-groups label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-height: 1.8rem;
            color: #243b53;
            font-size: 0.72rem;
            cursor: pointer;
        }

        .personnel-job-groups input {
            width: 1rem;
            height: 1rem;
            accent-color: #1d70d8;
        }

        .personnel-job-catalog > footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.55rem;
            border-top: 1px solid #d9e2ec;
            padding: 0.7rem 0.9rem;
            background: #fbfdff;
        }

        .personnel-job-catalog > footer strong {
            color: #31516f;
            font-size: 0.67rem;
        }

        .personnel-job-catalog > footer .training-primary-button {
            min-height: 2.2rem;
            padding-inline: 0.65rem;
            font-size: 0.68rem;
        }

        @media (max-width: 1050px) {
            .personnel-create-body {
                grid-template-columns: 1fr;
            }

            .personnel-job-catalog {
                position: static;
                max-height: none;
            }

            .personnel-job-groups {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.7rem;
                overflow: visible;
            }
        }

        @media (max-width: 720px) {
            .personnel-create-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .personnel-create-header-actions {
                width: 100%;
            }

            .personnel-create-header-actions button {
                flex: 1;
            }

            .personnel-fields-grid.is-three-columns,
            .personnel-fields-grid.is-two-columns,
            .personnel-document-grid,
            .personnel-credentials-grid,
            .personnel-employment-row,
            .personnel-job-groups {
                grid-template-columns: 1fr;
            }

            .personnel-regenerate-button {
                width: 100%;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.querySelector('[data-personnel-create-modal]');

            if (!modal) {
                return;
            }

            const form = modal.querySelector('[data-personnel-create-form]');
            const firstNameInput = modal.querySelector('[data-personnel-first-name]');
            const paternalSurnameInput = modal.querySelector('[data-personnel-paternal-surname]');
            const maternalSurnameInput = modal.querySelector('[data-personnel-maternal-surname]');
            const usernameInput = modal.querySelector('[data-personnel-username]');
            const passwordInput = modal.querySelector('[data-personnel-password]');
            const employmentToggle = modal.querySelector('[data-employment-toggle]');
            const employmentInput = modal.querySelector('[data-employment-status-input]');
            const employmentLabel = modal.querySelector('[data-employment-label]');
            const positionCheckboxes = Array.from(modal.querySelectorAll('[data-position-checkbox]'));
            const positionChips = modal.querySelector('[data-selected-position-chips]');
            const positionCount = modal.querySelector('[data-position-count]');
            const jobSearch = modal.querySelector('[data-job-search]');
            const cvInput = modal.querySelector('[data-personnel-cv]');
            const cvLabel = modal.querySelector('[data-personnel-cv-label]');
            let usernameWasEdited = Boolean(usernameInput && usernameInput.value);
            let passwordWasEdited = Boolean(passwordInput && passwordInput.value);
            let credentialYear = randomCredentialYear();

            function normalizeCredential(value) {
                return String(value || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '')
                    .slice(0, 18);
            }

            function normalizeUsername(value) {
                return String(value || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/\s+/g, '.')
                    .replace(/[^a-z0-9._-]/g, '')
                    .replace(/\.{2,}/g, '.')
                    .replace(/^[._-]+|[._-]+$/g, '')
                    .slice(0, 30);
            }

            function proposedUsername() {
                const nameInitials = String(firstNameInput && firstNameInput.value || '')
                    .trim()
                    .split(/\s+/)
                    .map(function(name) { return normalizeCredential(name).slice(0, 1); })
                    .filter(Boolean)
                    .join('');
                const paternalSurname = normalizeCredential(paternalSurnameInput && paternalSurnameInput.value);
                const maternalInitial = normalizeCredential(maternalSurnameInput && maternalSurnameInput.value).slice(0, 1);

                return (nameInitials + paternalSurname + maternalInitial).slice(0, 30);
            }

            function randomCredentialYear() {
                return String(2024 + Math.floor(Math.random() * 3));
            }

            function proposedPassword() {
                const username = normalizeUsername(usernameInput && usernameInput.value);

                return username ? username + credentialYear : '';
            }

            function refreshUsername(force) {
                if (!usernameInput || (!force && usernameWasEdited)) {
                    return;
                }

                usernameInput.value = proposedUsername();
            }

            function refreshPassword(force) {
                if (!passwordInput || (!force && passwordWasEdited)) {
                    return;
                }

                passwordInput.value = proposedPassword();
            }

            function refreshCredentials(force) {
                refreshUsername(force);
                refreshPassword(force);
            }

            function ensureCredentials() {
                refreshCredentials(false);
            }

            function openModal() {
                ensureCredentials();

                if (!modal.open) {
                    modal.showModal();
                }

                window.setTimeout(function() {
                    firstNameInput && firstNameInput.focus();
                }, 50);
            }

            function closeModal() {
                if (modal.open) {
                    modal.close();
                }
            }

            function selectedPositions() {
                return positionCheckboxes
                    .filter(function(checkbox) { return checkbox.checked; })
                    .map(function(checkbox) { return checkbox.value; });
            }

            function renderSelectedPositions() {
                const positions = selectedPositions();

                if (positionChips) {
                    positionChips.innerHTML = '';

                    if (positions.length === 0) {
                        const empty = document.createElement('em');
                        empty.textContent = 'Selecciona uno o varios puestos del catalogo.';
                        positionChips.appendChild(empty);
                    } else {
                        positions.forEach(function(position) {
                            const chip = document.createElement('span');
                            chip.textContent = position;
                            positionChips.appendChild(chip);
                        });
                    }
                }

                if (positionCount) {
                    positionCount.textContent = positions.length + (positions.length === 1
                        ? ' puesto seleccionado'
                        : ' puestos seleccionados');
                }
            }

            document.querySelectorAll('[data-open-personnel-create]').forEach(function(button) {
                button.addEventListener('click', openModal);
            });

            modal.querySelectorAll('[data-close-personnel-create]').forEach(function(button) {
                button.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            if (modal.dataset.openOnErrors === 'true') {
                openModal();
            }

            [firstNameInput, paternalSurnameInput, maternalSurnameInput].forEach(function(input) {
                input && input.addEventListener('input', function() {
                    refreshUsername(false);
                    refreshPassword(false);
                });
            });

            usernameInput && usernameInput.addEventListener('input', function() {
                usernameWasEdited = true;
                usernameInput.value = normalizeUsername(usernameInput.value);
                refreshPassword(false);
            });

            passwordInput && passwordInput.addEventListener('input', function() {
                passwordWasEdited = true;
            });

            modal.querySelector('[data-regenerate-personnel-credentials]')?.addEventListener('click', function() {
                usernameWasEdited = false;
                passwordWasEdited = false;
                credentialYear = randomCredentialYear();
                refreshCredentials(true);
            });

            modal.querySelectorAll('[data-copy-personnel-field]').forEach(function(button) {
                button.addEventListener('click', async function() {
                    const input = form.elements[button.dataset.copyPersonnelField];

                    if (!input || !input.value) {
                        return;
                    }

                    try {
                        await navigator.clipboard.writeText(input.value);
                        button.classList.add('is-copied');
                        window.setTimeout(function() { button.classList.remove('is-copied'); }, 900);
                    } catch (error) {
                        input.select();
                        document.execCommand('copy');
                    }
                });
            });

            employmentToggle && employmentToggle.addEventListener('change', function() {
                employmentInput.value = employmentToggle.checked ? 'hired' : 'inactive';
                employmentLabel.textContent = employmentToggle.checked ? 'Activo' : 'Baja';
            });

            positionCheckboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', renderSelectedPositions);
            });

            modal.querySelector('[data-apply-positions]')?.addEventListener('click', function() {
                renderSelectedPositions();
                modal.querySelector('.personnel-selected-positions')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            });

            jobSearch && jobSearch.addEventListener('input', function() {
                const query = normalizeCredential(jobSearch.value);

                modal.querySelectorAll('[data-job-group]').forEach(function(group) {
                    let visibleCount = 0;

                    group.querySelectorAll('[data-job-option]').forEach(function(option) {
                        const searchableName = normalizeCredential(option.dataset.jobName);
                        const isVisible = !query || searchableName.includes(query);
                        option.hidden = !isVisible;
                        visibleCount += isVisible ? 1 : 0;
                    });

                    group.hidden = visibleCount === 0;
                });
            });

            cvInput && cvInput.addEventListener('change', function() {
                cvLabel.textContent = cvInput.files && cvInput.files[0]
                    ? cvInput.files[0].name
                    : 'Adjuntar CV';
            });

            const laboratoryInput = modal.querySelector('[data-personnel-laboratory]');
            const centralRequiredMessage = 'Asigna una central antes de crear el personal.';

            laboratoryInput?.addEventListener('invalid', function() {
                if (!laboratoryInput.value) {
                    laboratoryInput.setCustomValidity(centralRequiredMessage);
                }
            });

            laboratoryInput?.addEventListener('change', function() {
                laboratoryInput.setCustomValidity('');
            });

            form.addEventListener('submit', function(event) {
                if (laboratoryInput && !laboratoryInput.value) {
                    event.preventDefault();
                    laboratoryInput.setCustomValidity(centralRequiredMessage);
                    laboratoryInput.reportValidity();
                    laboratoryInput.focus();

                    return;
                }

                const saveButton = form.querySelector('[data-save-personnel]');

                if (saveButton) {
                    saveButton.disabled = true;
                    saveButton.querySelector('span').textContent = 'Guardando...';
                }
            });

            renderSelectedPositions();
        });
    </script>
@endpush
