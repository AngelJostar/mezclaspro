@if (isset($nutritionDiluents) && $nutritionDiluents->isNotEmpty())
    <section class="mt-6" aria-labelledby="nutrition-diluents-title">
        <h2 id="nutrition-diluents-title" class="mb-1 text-lg font-bold">DILUYENTES O VEHÍCULOS</h2>
        <p class="mb-3 text-sm text-gray-600">Captura únicamente el volumen indicado por el hospital. El volumen se suma a los componentes de la NPT.</p>
        <hr class="mb-4">

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            @foreach ($nutritionDiluents as $diluent)
                @php
                    $saved = isset($selectedNutritionDiluents) ? $selectedNutritionDiluents->get($diluent->id) : null;
                    $presentationField = "nutrition_diluents.{$diluent->id}.presentation_id";
                    $volumeField = "nutrition_diluents.{$diluent->id}.volume_ml";
                    $selectedPresentation = old($presentationField, $saved?->diluent_presentation_id);
                    $selectedVolume = old($volumeField, $saved?->volume_ml);
                @endphp
                <div class="rounded-md border border-cyan-200 bg-cyan-50 p-4">
                    <h3 class="mb-3 font-bold text-slate-800">{{ $diluent->denominacion_generica }}</h3>
                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="text-sm font-medium text-gray-700">
                            Presentación y lote
                            <select name="nutrition_diluents[{{ $diluent->id }}][presentation_id]"
                                class="mt-1 w-full rounded border-gray-300">
                                <option value="">No utilizar</option>
                                @foreach ($diluent->presentations as $presentation)
                                    <option value="{{ $presentation->id }}" @selected((string) $selectedPresentation === (string) $presentation->id)>
                                        {{ $presentation->denominacion_comercial ?: $presentation->catalogPresentation?->commercial_name ?: $presentation->presentacion }}
                                        · {{ $presentation->volume_ml }} mL
                                        · Lote {{ $presentation->lote ?: 'S/D' }}
                                        · Disponible {{ number_format((float) $presentation->stock_actual, 2) }} pieza(s)
                                    </option>
                                @endforeach
                            </select>
                            @error($presentationField)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>

                        <label class="text-sm font-medium text-gray-700">
                            Volumen solicitado (mL)
                            <input type="number" min="0" max="100000" step="0.0001"
                                name="nutrition_diluents[{{ $diluent->id }}][volume_ml]"
                                value="{{ $selectedVolume }}"
                                class="mt-1 w-full rounded border-gray-300"
                                placeholder="0.0000">
                            @error($volumeField)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
