@foreach(['service'=>'Servicio', 'provider'=>'Proveedor de servicios', 'appointment'=>'Programación del servicio', 'document'=>'Adjuntar archivo', 'close'=>'Revisar y cerrar reporte'] as $kind=>$title)
<dialog class="mw-dialog" data-maint-dialog="{{ $kind }}" aria-labelledby="mw-title-{{ $kind }}">
<form data-maint-form="{{ $kind }}" @if(in_array($kind,['service','provider','appointment'])) data-store-url="{{ route($routePrefix.['service'=>'services','provider'=>'providers','appointment'=>'appointments'][$kind].'.store') }}" @endif>
    <header><h2 id="mw-title-{{ $kind }}">{{ $title }}</h2><button type="button" data-maint-close aria-label="Cerrar ventana">×</button></header>
    <div class="mw-dialog-body"><p class="mw-error" role="alert" hidden></p>
    @if($kind === 'service')
        <div class="mw-form-grid">
            <label class="mw-wide">Nombre del servicio<input name="service" required maxlength="255"></label>
            <label>Tipo<input name="type" required maxlength="120" list="mw-types"></label>
            <label>Frecuencia sugerida<input name="frequency" maxlength="120" list="mw-frequencies"></label>
            <label>Central<select name="laboratory_id"><option value="">Todas las centrales</option>@foreach($laboratories as $lab)<option value="{{ $lab->id }}" @selected($lab->id===$laboratoryId)>{{ $lab->nombre }}</option>@endforeach</select></label>
            <label>Estado<select name="is_active"><option value="1">Activo</option><option value="0">Inactivo</option></select></label>
            <label class="mw-wide">Descripción<textarea name="description" maxlength="5000" rows="3"></textarea></label>
            <label>Activos aplicables<input name="applicable_assets" maxlength="255"></label><label>Entregables<input name="deliverables" maxlength="255"></label>
            <label>Reporte requerido<select name="report_required"><option value="1">Sí</option><option value="0">No</option></select></label>
        </div>
        <fieldset class="mw-checks"><legend>Proveedores vinculados</legend>@forelse($providers as $provider)<label><input type="checkbox" name="provider_ids[]" value="{{ $provider->id }}">{{ $provider->name }}{{ !$provider->is_active ? ' (Inactivo)' : '' }}</label>@empty<p>Registra proveedores en su catálogo para vincularlos.</p>@endforelse</fieldset>
    @elseif($kind === 'provider')
        <div class="mw-form-grid"><label class="mw-wide">Nombre del proveedor<input name="name" required maxlength="255"></label><label>Especialidad<input name="specialty" maxlength="255"></label><label>Contacto<input name="contact_name" maxlength="255"></label><label>Correo electrónico<input name="email" type="email" maxlength="255"></label><label>Teléfono<input name="phone" maxlength="40"></label><label>Cobertura<input name="coverage" maxlength="255"></label><label>Estado<select name="is_active"><option value="1">Activo</option><option value="0">Inactivo</option></select></label></div>
        <fieldset class="mw-checks"><legend>Servicios vinculados</legend>@forelse($allServices as $service)<label><input type="checkbox" name="service_ids[]" value="{{ $service->id }}">{{ $service->service }}{{ !$service->is_active ? ' (Inactivo)' : '' }}</label>@empty<p>Registra servicios en el catálogo para vincularlos.</p>@endforelse</fieldset>
    @elseif($kind === 'appointment')
        <div class="mw-form-grid">
            <label class="mw-wide">Servicio<select name="service_id" required><option value="">Seleccionar servicio</option>@foreach($services as $service)<option value="{{ $service->id }}" data-frequency="{{ $service->frequency }}" data-laboratory="{{ $service->laboratory_id }}">{{ $service->service }}{{ !$service->is_active ? ' (Inactivo)' : '' }}</option>@endforeach</select></label>
            <label>Proveedor<select name="provider_id"><option value="">Sin asignar</option>@foreach($providers as $provider)<option value="{{ $provider->id }}" data-services="{{ $provider->services->pluck('id')->join(',') }}">{{ $provider->name }}{{ !$provider->is_active ? ' (Inactivo)' : '' }}</option>@endforeach</select></label>
            <label>Central<select name="laboratory_id" required><option value="">Seleccionar central</option>@foreach($laboratories as $lab)<option value="{{ $lab->id }}" @selected($lab->id===$laboratoryId)>{{ $lab->nombre }}</option>@endforeach</select></label>
            <label>Activo / identificación<input name="asset" required maxlength="255" placeholder="Ej. Refrigerador RF-01"></label><label>Ubicación<input name="location" maxlength="255"></label>
            <label>Inicio<input type="datetime-local" name="starts_at" required value="{{ $date->format('Y-m-d') }}T09:00"></label><label>Fin<input type="datetime-local" name="ends_at"></label>
            <label>Frecuencia<input name="frequency" maxlength="120" list="mw-frequencies"></label><label>Próximo servicio<input type="date" name="next_service_at"></label>
            <label>Responsable interno<input name="responsible_name" maxlength="255"></label><label>Estado<select name="status">@foreach($statusLabels as $key=>$label)<option value="{{ $key }}" @selected($key==='scheduled')>{{ $label }}</option>@endforeach</select></label>
            <label class="mw-wide">Actividades realizadas y observaciones<textarea name="report_notes" rows="3" maxlength="10000"></textarea></label>
        </div><p class="mw-hint">Los proveedores se vinculan desde el catálogo de servicios o la ficha del proveedor.</p>
    @elseif($kind === 'document')
        <label>Archivo<input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"></label><p class="mw-hint">PDF, imágenes, Word o Excel. Máximo 20 MB.</p><label>Descripción<input name="description" maxlength="255"></label>
    @else
        <p>Confirma que revisaste las actividades y los archivos del reporte. Al cerrar se registrará tu revisión y el reporte dejará de ser editable.</p>
    @endif
    </div><footer><button class="mw-button" type="button" data-maint-close>Cerrar</button><button class="mw-button mw-primary" type="submit">{{ $kind === 'close' ? 'Revisar y cerrar' : 'Guardar cambios' }}</button></footer>
</form></dialog>
@endforeach
<datalist id="mw-types">@foreach(collect(['Preventivo','Correctivo','Calibración','Calificación','Certificación'])->merge($services->pluck('type'))->filter()->unique() as $type)<option value="{{ $type }}">@endforeach</datalist>
<datalist id="mw-frequencies">@foreach(['A demanda','Mensual','Bimestral','Trimestral','Semestral','Anual','Por definir'] as $frequency)<option value="{{ $frequency }}">@endforeach</datalist>
