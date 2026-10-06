<section class="manual-library" aria-label="Manuales maestros por tipo de mezcla">
    <style>
        .manual-library{color:#13245d;margin:22px 0;font-size:14px;line-height:1.5}.manual-library *{box-sizing:border-box}.manual-library h4,.manual-library h3,.manual-library p{margin:0}.manual-library .ml-heading{font-size:18px;font-weight:700;margin-bottom:4px}.ml-muted{color:#667393;font-size:13px}.ml-notice{padding:12px;background:#e9faf6;border:1px solid #a8e0d6;border-radius:10px;margin:12px 0}
        .ml-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;max-width:1000px;margin-top:18px}.ml-card{border:1px solid #e2eaf4;border-radius:13px;padding:20px;background:#fff;box-shadow:0 3px 14px #16376d08;display:flex;flex-direction:column;gap:16px;min-height:205px}.ml-card.has-manual{border-color:#0eb0b2;background:linear-gradient(135deg,#fff,#f1fcfc)}.ml-card-top{display:flex;gap:16px;align-items:flex-start;flex:1}.ml-icon{width:58px;height:58px;flex:none;border-radius:50%;display:grid;place-items:center;background:#eaf4ff;color:#087cff}.ml-icon svg{width:32px;height:32px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}.ml-icon.npt_pediatrico{background:#f2edff;color:#6848f6}.ml-icon.oncologicos{background:#ffedf3;color:#ed427e}.ml-icon.antibioticos{background:#ddf8f5;color:#009d99}.ml-card h4{font-size:16px;font-weight:700;margin:4px 0}.ml-description{font-size:13px;color:#536488;margin-top:8px!important}
        .ml-badges{display:flex;flex-wrap:wrap;gap:6px;margin:7px 0}.ml-badge{display:inline-flex;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:500;background:#e7ecf3;color:#465474}.ml-badge.current{background:#def0ff;color:#0670d3}.ml-badge.pending{background:#fff0df;color:#bd5800}.ml-badge.reviewed{background:#dcf6e9;color:#008457}.ml-actions{display:flex;gap:10px}.ml-button{border:1px solid #879ac1;background:#fff;color:#13245d;border-radius:6px;padding:9px 14px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:7px}.ml-actions .ml-button{flex:1}.ml-button.primary{background:#009fa3;border-color:#009fa3;color:white}.ml-button.dark{background:#172968;border-color:#172968;color:white}.ml-button:disabled{opacity:.5;cursor:wait}.ml-button:focus-visible,.ml-history:focus-visible{outline:3px solid #93c5fd;outline-offset:2px}.ml-history{background:transparent;border:0;color:#0874df;font-size:13px;cursor:pointer;text-align:center;padding:0}
        .ml-dialog{width:min(760px,calc(100vw - 32px));max-height:88dvh;border:1px solid #e1e8f2;border-radius:14px;padding:0;color:#13245d;box-shadow:0 24px 80px #0f244d40;margin:auto;font-size:14px}.ml-dialog[open]{display:flex;flex-direction:column}.ml-dialog::backdrop{background:#18233f80;backdrop-filter:blur(2px)}.ml-dialog header{padding:20px 24px 14px;border-bottom:1px solid #edf1f7;display:flex;justify-content:space-between;gap:16px;flex-shrink:0}.ml-dialog h3{font-size:23px;font-weight:700;line-height:1.2}.ml-close{background:none;border:0;font-size:24px;color:#344774;cursor:pointer;width:30px;height:30px;line-height:1}.ml-dialog-body{padding:20px 24px;overflow:auto;min-height:0}.ml-dialog footer{padding:16px 24px;display:flex;justify-content:flex-end;gap:10px;border-top:1px solid #edf1f7;flex-shrink:0;background:#fff}.ml-form{display:flex;flex-direction:column;min-height:0}.ml-fields{display:grid;grid-template-columns:1fr 1fr;gap:16px}.ml-fields label{display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:500}.ml-fields .full{grid-column:1/-1}.ml-fields input:not([type=file]),.ml-fields select,.ml-fields textarea{width:100%;border:1px solid #cbd6e8;border-radius:6px;padding:10px;font:inherit;background:white;color:#172968}.ml-fields textarea{min-height:125px;resize:vertical}.ml-upload{position:relative;display:flex;align-items:center;justify-content:center;gap:12px;border:1px dashed #aebee2;border-radius:8px;background:#f7faff;padding:22px;cursor:pointer}.ml-upload input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%}.ml-upload small{font-weight:400;color:#7b89a7}.ml-upload:focus-within{outline:3px solid #93c5fd}
        .ml-tabs{display:flex;gap:8px;border-bottom:1px solid #e1e8f2;margin:16px 0}.ml-tabs button{background:none;border:0;border-bottom:2px solid transparent;padding:10px;color:#536488;cursor:pointer;font:inherit}.ml-tabs button.active{color:#0874df;border-bottom-color:#0874df}.ml-reader{background:#f1f5fa;border:1px solid #e1e8f2;border-radius:8px;min-height:280px;max-height:45dvh;overflow:auto;padding:20px}.ml-reader pre{white-space:pre-wrap;overflow-wrap:anywhere;background:#fff;border:1px solid #e4e9f1;padding:24px;margin:0;color:#283655;font:inherit}.ml-pdf{width:100%;height:42dvh;border:0}.ml-empty{text-align:center;display:grid;place-content:center;min-height:240px;gap:10px}.ml-analysis{margin-top:16px;padding:16px;border:1px solid #dce5f2;border-radius:8px}.ml-analysis pre{white-space:pre-wrap;overflow-wrap:anywhere;font:inherit}.ml-error{color:#b42318;padding:8px 0}.ml-history-row{display:flex;justify-content:space-between;gap:12px;padding:16px 0;border-bottom:1px solid #e4eaf4;align-items:center}
        .ml-modal-overlay{position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;padding:16px;background:#18233f80;backdrop-filter:blur(2px)}
        .ml-modal-overlay .ml-dialog{display:flex;flex-direction:column;background:#fff}
        .manual-library .ml-heading{font-size:24px;line-height:1.3;margin-bottom:8px}
        .manual-library > .ml-muted{font-size:16px}
        .manual-library .ml-grid{grid-template-columns:repeat(4,minmax(0,1fr));max-width:none;width:100%;gap:16px;margin-top:24px;align-items:stretch}
        .manual-library .ml-card{min-height:320px;padding:24px;gap:20px;box-shadow:none}
        .manual-library .ml-card-top{gap:18px}
        .manual-library .ml-card-top > div:last-child{min-width:0;overflow-wrap:anywhere}
        .manual-library .ml-card h4{font-size:18px;line-height:1.35;margin:8px 0 10px}
        .manual-library .ml-icon{width:64px;height:64px}
        .manual-library .ml-description{font-size:15px;line-height:1.7;margin-top:12px!important}
        .manual-library .ml-card .ml-muted{font-size:14px;line-height:1.6}
        .manual-library .ml-card .ml-badge{font-size:12px}
        .manual-library .ml-card-footer{margin-top:auto;display:flex;flex-direction:column;gap:14px;min-height:86px;justify-content:flex-start}
        .manual-library .ml-card:not(.has-manual) .ml-card-footer{justify-content:flex-end}
        .manual-library .ml-card .ml-button{padding:12px 10px;min-height:46px;font-size:14px}
        .manual-library .ml-card .ml-history{font-size:14px}
        @media(max-width:1450px){.manual-library .ml-card{padding:18px;min-height:300px}.manual-library .ml-card-top{gap:12px}.manual-library .ml-icon{width:48px;height:48px}.manual-library .ml-card h4{font-size:16px}.manual-library .ml-card .ml-actions{flex-wrap:wrap}.manual-library .ml-card .ml-button{font-size:13px}}
        @media(max-width:1100px){.manual-library .ml-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.manual-library .ml-card .ml-actions{flex-wrap:nowrap}}
        @media(max-width:640px){.manual-library .ml-grid,.ml-fields{grid-template-columns:1fr}.manual-library .ml-card{padding:16px;min-height:260px}.ml-dialog header,.ml-dialog-body,.ml-dialog footer{padding:16px}.ml-actions{flex-wrap:wrap}}
    </style>
    <h4 class="ml-heading">Manuales maestros</h4><p class="ml-muted">Documentos de consulta por tipo de mezcla. En nutrición parenteral se utiliza el manual de Adulto o Pediátrico según la selección del usuario.</p>
    @if ($notice)<div class="ml-notice" role="status">{{ $notice }}</div>@endif
    <div class="ml-grid">
        @foreach ($types as $key => $label)
            @php($current = $manuals->get($key, collect())->first(fn ($item) => !$item->superseded_at))
            <article class="ml-card {{ $current ? 'has-manual' : '' }}" wire:key="manual-card-{{ $key }}">
                <div class="ml-card-top">
                    <div class="ml-icon {{ $key }}" aria-hidden="true"><svg viewBox="0 0 32 32">
                        @if ($key === 'npt_adulto')<path d="M13 5V3h6v2M11 6h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H11a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2ZM12 10h8M12 14h8M16 24v5M13 27h6"/>
                        @elseif ($key === 'npt_pediatrico')<circle cx="16" cy="18" r="10"/><path d="M6 16H4v5h3M26 16h2v5h-3M13 17h.1M19 17h.1M12 22q4 4 8 0M16 8c-5-2-2-7 1-5s-1 5-1 5"/>
                        @elseif ($key === 'oncologicos')<path d="M12 4c-4 5-2 10 2 15L7 28l4 2 8-12c4-6 6-10 1-14-2-2-6-2-8 0Zm0 0c0 5 5 10 13 24l-4 2L9 12"/>
                        @else<path d="M9 3h10l6 6v20H9V3Zm10 0v7h6M13 15h8M13 20h8M13 25h6"/>@endif
                    </svg></div>
                    <div><h4>{{ $label }}</h4>
                        @if ($current)
                            <p class="ml-muted">{{ $current->title }}</p><div class="ml-badges"><span class="ml-badge current">Versión actual</span><span class="ml-badge {{ $current->isReviewed() ? 'reviewed' : 'pending' }}">{{ $current->isReviewed() ? 'Revisada' : '◷ Pendiente de revisión' }}</span></div><p class="ml-muted ml-description">{{ $current->file_name ?: ($current->manual_version ? 'Versión '.$current->manual_version : $current->reference) }}</p>
                        @else
                            <div class="ml-badges"><span class="ml-badge">Sin manual</span></div><p class="ml-description">Agrega el documento de referencia<br>de esta categoría.</p>
                        @endif
                    </div>
                </div>
                <div class="ml-card-footer">
                <div class="ml-actions">@if ($current)<button class="ml-button primary" type="button" wire:click="viewManual({{ $current->id }})">◉ Ver manual</button>@endif<button class="ml-button" type="button" wire:click="openForm('{{ $key }}')">{{ $current ? '✎ Actualizar versión' : '＋ Cargar manual' }}</button></div>
                @if ($manuals->get($key, collect())->isNotEmpty())<button class="ml-history" type="button" wire:click="showHistory('{{ $key }}')">↶ Historial de versiones</button>@endif
                </div>
            </article>
        @endforeach
    </div>
    @if ($modal)
        <div class="ml-modal-overlay" wire:key="manual-dialog-{{ $modal }}-{{ $viewingId ?? $type }}" x-data x-trap.inert.noscroll="true" @keydown.escape.prevent.stop="$wire.closeModal()">
        <section class="ml-dialog" role="dialog" aria-modal="true" aria-labelledby="ml-dialog-title" tabindex="-1" x-init="$nextTick(() => $el.focus())">
            <header><div><h3 id="ml-dialog-title">{{ $modal === 'form' ? ($manuals->get($type, collect())->isNotEmpty() ? 'Actualizar versión' : 'Cargar manual') : ($modal === 'history' ? 'Historial de versiones' : 'Ver manual') }}</h3><p class="ml-muted">{{ $modal === 'view' ? $viewing->title : $types[$type] }}</p></div><button type="button" class="ml-close" wire:click="closeModal" aria-label="Cerrar ventana">×</button></header>
            @if ($modal === 'form')
                <form wire:submit="save" class="ml-form"><div class="ml-dialog-body"><div class="ml-fields">
                    <label>Tipo de mezcla<select wire:model="type">@foreach ($types as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label><label>Nueva versión<input wire:model="version" required maxlength="20" placeholder="Ej. 4"></label>
                    <label class="full">Referencia documental<input wire:model="reference" required maxlength="1000" placeholder="Título, autor, fecha y referencia"></label>
                    <label class="full">Seleccionar archivo<span class="ml-upload"><span aria-hidden="true">▧</span><span>{{ $file ? $file->getClientOriginalName() : 'Seleccionar nuevo archivo' }}<br><small>PDF, DOCX o TXT · Máximo 10 MB</small></span><input type="file" wire:model="file" accept=".pdf,.docx,.txt" aria-label="Seleccionar archivo del manual"></span></label>
                    <span class="ml-muted full" wire:loading wire:target="file">Cargando archivo…</span><label class="full">Texto del manual para consulta y análisis<textarea wire:model="content" rows="5" required minlength="100" maxlength="30000" placeholder="Pega o revisa el contenido del manual"></textarea></label>
                    <p class="ml-muted full">DOCX y TXT: extracción de texto. PDF: pega el texto aplicable.<br>La versión anterior se conservará en el historial.</p>
                </div>@foreach ($errors->all() as $error)<p class="ml-error" role="alert">{{ $error }}</p>@endforeach</div><footer><button class="ml-button" wire:click="closeModal" type="button">Cancelar</button><button class="ml-button primary" type="submit" wire:loading.attr="disabled">Guardar para revisión</button></footer></form>
            @elseif ($modal === 'history')
                <div class="ml-dialog-body">@foreach ($manuals->get($type, collect()) as $previous)<div class="ml-history-row"><div><strong>{{ $previous->manual_version ? 'Versión '.$previous->manual_version : $previous->title }}</strong><p class="ml-muted">{{ $previous->created_at->format('d/m/Y H:i') }} · {{ $previous->superseded_at ? 'Histórico' : 'Actual' }}</p></div><button class="ml-button" type="button" wire:click="viewManual({{ $previous->id }})">Ver manual</button></div>@endforeach</div><footer><button class="ml-button dark" type="button" wire:click="closeModal">Cerrar</button></footer>
            @else
                <div class="ml-dialog-body" x-data="{tab:'document'}">
                    <div class="ml-badges"><span class="ml-badge">{{ $types[$viewing->manual_type === 'nutricionales' ? 'npt_adulto' : ($viewing->manual_type ?: ($viewing->category === 'antibioticos' ? 'antibioticos' : 'npt_adulto'))] }}</span>@if ($viewing->manual_version)<span class="ml-badge">Versión {{ $viewing->manual_version }}</span>@endif<span class="ml-badge current">{{ $viewing->superseded_at ? 'Histórico' : 'Versión actual' }}</span><span class="ml-badge {{ $viewing->isReviewed() ? 'reviewed' : 'pending' }}">{{ $viewing->isReviewed() ? 'Revisada' : 'Pendiente de revisión' }}</span></div>
                    <div class="ml-tabs" role="tablist" aria-label="Contenido del manual"><button id="ml-document-tab" type="button" role="tab" :aria-selected="tab==='document'" aria-controls="ml-document-panel" :class="{active:tab==='document'}" @click="tab='document'">▤ Documento</button><button id="ml-text-tab" type="button" role="tab" :aria-selected="tab==='text'" aria-controls="ml-text-panel" :class="{active:tab==='text'}" @click="tab='text'">▧ Texto para consulta</button></div>
                    <div id="ml-document-panel" role="tabpanel" aria-labelledby="ml-document-tab" x-show="tab==='document'" class="ml-reader">
                        @if ($viewing->file_path && strtolower(pathinfo($viewing->file_path, PATHINFO_EXTENSION)) === 'pdf')<iframe class="ml-pdf" title="Documento del manual" src="{{ route('admin.clinical-manuals.document', $viewing) }}"></iframe>
                        @elseif ($viewing->file_path)<div class="ml-empty"><strong>{{ $viewing->file_name }}</strong><p class="ml-muted">Consulta el contenido en “Texto para consulta”<br>o descarga el archivo original.</p><button class="ml-button" type="button" wire:click="download({{ $viewing->id }})">↓ Descargar archivo</button></div>
                        @else<div class="ml-empty"><strong>Manual registrado como texto</strong><p class="ml-muted">El contenido está disponible en “Texto para consulta”.</p><button class="ml-button" type="button" @click="tab='text'">Ver texto del manual</button></div>@endif
                    </div>
                    <div id="ml-text-panel" role="tabpanel" aria-labelledby="ml-text-tab" x-show="tab==='text'" class="ml-reader"><pre>{{ $viewing->content }}</pre></div>
                    <div class="ml-actions" style="margin-top:16px"><button class="ml-button" wire:click="analyze({{ $viewing->id }})" type="button" wire:loading.attr="disabled">Analizar documento</button><button class="ml-button primary" wire:click="analyzeWithAgent({{ $viewing->id }})" type="button" wire:loading.attr="disabled">Analizar con IA</button></div><p class="ml-muted" style="margin-top:8px">El análisis con IA envía el texto de esta versión a OpenAI.</p><span wire:loading wire:target="analyzeWithAgent">Analizando…</span>@error('analysis')<p class="ml-error" role="alert">{{ $message }}</p>@enderror
                    @if ($viewing->manual_analysis)<div class="ml-analysis">
                        @isset($viewing->manual_analysis['words'])<p>{{ $viewing->manual_analysis['words'] }} palabras · {{ $viewing->manual_analysis['characters'] }} caracteres</p>@endisset
                        @if (!empty($viewing->manual_analysis['previous_version']))<p>Comparación con versión {{ $viewing->manual_analysis['previous_version'] }}: {{ $viewing->manual_analysis['content_changed'] ? 'El contenido cambió.' : 'El contenido es idéntico.' }}</p>@endif
                        @foreach ($viewing->manual_analysis['sections'] ?? [] as $heading)<p>{{ $heading }}</p>@endforeach
                        @isset($viewing->manual_analysis['agent_result'])<h4>Análisis del agente</h4><pre>{{ $viewing->manual_analysis['agent_result']['answer'] }}</pre>@foreach ($viewing->manual_analysis['agent_result']['citations'] as $citation)<p>{{ $citation['source_id'] }} · {{ $citation['section'] }}</p>@endforeach@endisset<p class="ml-muted">Este análisis no constituye aprobación clínica.</p>
                    </div>@endif
                </div><footer>@if ($viewing->file_path)<button class="ml-button" type="button" wire:click="download({{ $viewing->id }})">↓ Descargar archivo</button>@endif<button class="ml-button dark" type="button" wire:click="closeModal">Cerrar</button></footer>
            @endif
        </section>
        </div>
    @endif
</section>

