<x-admin-layout>
    <div class="hospital-edit-heading">
        <h1>Editar Hospital</h1>
    </div>

    <form action="{{ route('admin.hospitals.update', $hospital) }}" method="POST"
        class="hospital-edit-form" data-hospital-edit-form>
        @csrf
        @method('PUT')

        <x-validation-errors class="mb-4" />

        <div class="hospital-edit-grid hospital-edit-general">
            <div class="hospital-edit-field">
                <label for="laboratory_id">Central que surte al hospital</label>
                <select id="laboratory_id" name="laboratory_id">
                    <option value="">Sin central asignada</option>
                    @foreach ($laboratories as $lab)
                        <option value="{{ $lab->id }}" @selected(old('laboratory_id', $hospital->laboratory_id) == $lab->id)>
                            {{ $lab->nombre }}{{ $lab->activo ? '' : ' (Inactiva)' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="hospital-edit-field">
                <span id="hospital-institutions-label">Instituci&oacute;n a la que pertenece</span>
                <div class="hospital-edit-readonly" role="group" aria-labelledby="hospital-institutions-label">
                    @forelse ($hospital->instituciones as $institucion)
                        <p>{{ $institucion->nombre }}</p>
                    @empty
                        <p>Sin instituci&oacute;n asignada</p>
                    @endforelse
                </div>
            </div>
            <div class="hospital-edit-field">
                <label for="hospital_name">Nombre</label>
                <input id="hospital_name" name="name" required maxlength="255"
                    placeholder="Nombre del hospital" value="{{ old('name', $hospital->name) }}">
            </div>
            <div class="hospital-edit-field hospital-edit-state">
                <span id="hospital-state-label">Estado</span>
                <div class="hospital-edit-state-controls" role="group" aria-labelledby="hospital-state-label">
                    <input name="is_active" type="hidden" value="0">
                    <label class="hospital-edit-toggle">
                        <input id="hospital_is_active" name="is_active" type="checkbox" value="1"
                            @checked(old('is_active', $hospital->is_active) == 1)>
                        <span class="hospital-edit-toggle-track" aria-hidden="true"></span>
                        <span>Activar</span>
                    </label>
                    <span class="hospital-edit-status {{ old('is_active', $hospital->is_active) == 1 ? 'is-active' : '' }}"
                        data-hospital-active-label aria-live="polite">
                        <span class="hospital-edit-status-dot" aria-hidden="true"></span>
                        <span data-hospital-active-text>{{ old('is_active', $hospital->is_active) == 1 ? 'Activo' : 'Inactivo' }}</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="hospital-edit-grid hospital-edit-location">
            <div class="hospital-edit-field hospital-edit-address">
                <label for="hospital_address">Direcci&oacute;n</label>
                <input id="hospital_address" name="adress" required maxlength="400"
                    value="{{ old('adress', $hospital->adress) }}">
            </div>
            <div class="hospital-edit-field">
                <label for="google_maps_url">Ubicaci&oacute;n en Google Maps</label>
                <div class="hospital-edit-map">
                    <input id="google_maps_url" type="url" name="google_maps_url" maxlength="2048"
                        placeholder="https://maps.google.com/..."
                        value="{{ old('google_maps_url', $hospital->google_maps_url) }}">
                    <a data-hospital-map-link target="_blank" rel="noopener noreferrer"
                        aria-label="Abrir ubicacion en Google Maps" title="Abrir ubicacion en Google Maps"
                        aria-disabled="true" tabindex="-1">
                        <i data-hospital-icon="external-link" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            <div class="hospital-edit-field">
                <label for="country">Pa&iacute;s</label>
                <input id="country" name="country" required maxlength="100"
                    value="{{ old('country', $hospital->country ?: 'México') }}">
            </div>
        </div>

        <div class="hospital-edit-field hospital-edit-notes">
            <label for="free_text">Informaci&oacute;n adicional</label>
            <textarea id="free_text" name="free_text" rows="2" maxlength="10000"
                placeholder="Agrega cualquier informacion adicional sobre el hospital.">{{ old('free_text', $hospital->free_text) }}</textarea>
        </div>

        <section class="hospital-edit-lists" aria-labelledby="hospital-lists-title">
            <h2 id="hospital-lists-title">Listas asignadas</h2>
            <div class="hospital-edit-grid">
                <div class="hospital-edit-field">
                    <label for="onco_medicine_list_id">Medicamentos oncol&oacute;gicos</label>
                    <select name="onco_medicine_list_id" id="onco_medicine_list_id">
                        <option value="">Seleccione una lista</option>
                        @foreach ($oncoMedicineLists as $list)
                            <option value="{{ $list->id }}" @selected(old('onco_medicine_list_id', $hospital->onco_medicine_list_id) == $list->id)>
                                {{ $list->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="hospital-edit-field">
                    <label for="nutri_medicine_list_id">Nutrici&oacute;n</label>
                    <select name="nutri_medicine_list_id" id="nutri_medicine_list_id">
                        <option value="">Seleccione una lista</option>
                        @foreach ($nutriMedicineLists as $list)
                            <option value="{{ $list->id }}" @selected(old('nutri_medicine_list_id', $hospital->nutri_medicine_list_id) == $list->id)>
                                {{ $list->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="hospital-edit-field">
                    <label for="antibiotic_medicine_list_id">Antibi&oacute;ticos</label>
                    <select name="antibiotic_medicine_list_id" id="antibiotic_medicine_list_id">
                        <option value="">Seleccione una lista</option>
                        @foreach ($antibioticMedicineLists as $list)
                            <option value="{{ $list->id }}" @selected(old('antibiotic_medicine_list_id', $hospital->antibiotic_medicine_list_id) == $list->id)>
                                {{ $list->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <div class="hospital-edit-actions">
            <button type="submit">Actualizar hospital</button>
        </div>
    </form>

    @push('css')
        <style>
            .hospital-edit-heading { margin: 0 0 18px; }
            .hospital-edit-heading h1 { margin: 0; font-size: 22px; font-weight: 600; color: #172b4d; }
            .hospital-edit-form { padding: 0; }
            .hospital-edit-grid { display: grid; gap: 16px 20px; }
            .hospital-edit-general { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1.2fr) 190px; }
            .hospital-edit-location { grid-template-columns: minmax(0, 1.6fr) minmax(0, 1.2fr) minmax(0, 1fr); margin-top: 18px; }
            .hospital-edit-field { min-width: 0; }
            .hospital-edit-field > label, .hospital-edit-field > span {
                display: block; margin: 0 0 6px; font-size: 12px; font-weight: 600; line-height: 1.4; color: #52627a;
            }
            .hospital-edit-form input:not([type=checkbox]):not([type=hidden]),
            .hospital-edit-form select, .hospital-edit-form textarea, .hospital-edit-readonly {
                display: block; width: 100%; min-width: 0; min-height: 36px; margin: 0;
                border: 1px solid #cbd5e1; border-radius: 5px; background-color: #fff;
                padding: 7px 10px; color: #173352; font-size: 13px; line-height: 20px; box-shadow: 0 1px 2px #172b4d08;
            }
            .hospital-edit-form select { padding-right: 30px; }
            .hospital-edit-form input::placeholder, .hospital-edit-form textarea::placeholder { color: #718096; }
            .hospital-edit-form input:focus, .hospital-edit-form select:focus, .hospital-edit-form textarea:focus {
                border-color: #3765b6; outline: 2px solid #3765b622; outline-offset: 1px; box-shadow: none;
            }
            .hospital-edit-readonly { background: #f8fafc; overflow-wrap: anywhere; }
            .hospital-edit-readonly p { margin: 0; }
            .hospital-edit-state-controls { display: flex; align-items: center; gap: 12px; min-height: 36px; }
            .hospital-edit-toggle { position: relative; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; }
            .hospital-edit-toggle input { position: absolute; width: 1px; height: 1px; opacity: 0; }
            .hospital-edit-toggle-track { position: relative; display: block; flex: 0 0 36px; width: 36px; height: 20px; border-radius: 10px; background: #a7b3c4; transition: background-color 150ms; }
            .hospital-edit-toggle-track::after { position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px #172b4d33; content: ''; transition: transform 150ms; }
            .hospital-edit-toggle input:checked + .hospital-edit-toggle-track { background: #2563eb; }
            .hospital-edit-toggle input:checked + .hospital-edit-toggle-track::after { transform: translateX(16px); }
            .hospital-edit-toggle input:focus-visible + .hospital-edit-toggle-track { outline: 2px solid #3765b6; outline-offset: 3px; }
            .hospital-edit-status { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-width: 78px; padding: 5px 8px; border-radius: 6px; color: #8c3741; background: #fff0f2; font-size: 12px; line-height: 18px; }
            .hospital-edit-status.is-active { color: #078653; background: #e4f6ea; }
            .hospital-edit-status-dot { width: 7px; height: 7px; flex-shrink: 0; border-radius: 50%; background: currentColor; }
            .hospital-edit-map { position: relative; }
            .hospital-edit-map input { padding-right: 40px !important; }
            .hospital-edit-map a { position: absolute; top: 1px; right: 1px; display: grid; place-items: center; width: 34px; height: 34px; color: #304566; border-radius: 4px; }
            .hospital-edit-map a:hover { background: #edf3fc; }
            .hospital-edit-map a[aria-disabled=true] { opacity: .35; cursor: default; }
            .hospital-edit-map svg { width: 16px; height: 16px; }
            .hospital-edit-notes { margin-top: 18px; }
            .hospital-edit-form textarea { height: 52px; min-height: 52px; resize: vertical; }
            .hospital-edit-lists { margin-top: 18px; padding-top: 14px; border-top: 1px solid #cbd5e1; }
            .hospital-edit-lists h2 { margin: 0 0 12px; color: #172b4d; font-size: 15px; font-weight: 600; }
            .hospital-edit-lists .hospital-edit-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .hospital-edit-actions { display: flex; justify-content: flex-end; margin-top: 18px; }
            .hospital-edit-actions button { min-height: 36px; padding: 8px 16px; border: 1px solid #304583; border-radius: 5px; color: #fff; background: #304583; font-size: 13px; font-weight: 600; line-height: 18px; }
            .hospital-edit-actions button:hover { background: #243569; }
            .hospital-edit-actions button:focus-visible, .hospital-edit-map a:focus-visible { outline: 2px solid #3765b6; outline-offset: 3px; }
            @media (max-width: 1100px) {
                .hospital-edit-general { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .hospital-edit-location { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .hospital-edit-address { grid-column: 1 / -1; }
            }
            @media (max-width: 600px) {
                .hospital-edit-general, .hospital-edit-location, .hospital-edit-lists .hospital-edit-grid { grid-template-columns: minmax(0, 1fr); }
                .hospital-edit-grid { gap: 14px; }
                .hospital-edit-state-controls { justify-content: flex-start; }
            }
        </style>
    @endpush
</x-admin-layout>
