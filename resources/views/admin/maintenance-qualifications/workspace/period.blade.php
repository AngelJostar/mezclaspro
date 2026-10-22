<div class="mw-period">
    <a class="mw-button mw-arrow" href="{{ $url(['fecha' => ($mode === 'mes' ? $date->copy()->subMonthNoOverflow() : $date->copy()->subWeek())->toDateString(), 'seleccion' => null]) }}" aria-label="Periodo anterior">‹</a>
    <span>{{ $mode === 'mes' ? $date->locale('es')->translatedFormat('F Y') : $start->format('d').' – '.$end->locale('es')->translatedFormat('d F Y') }}</span>
    <a class="mw-button mw-arrow" href="{{ $url(['fecha' => ($mode === 'mes' ? $date->copy()->addMonthNoOverflow() : $date->copy()->addWeek())->toDateString(), 'seleccion' => null]) }}" aria-label="Periodo siguiente">›</a>
    <a class="mw-button" href="{{ $url(['fecha' => now()->toDateString(), 'seleccion' => null]) }}">Hoy</a>
</div>
