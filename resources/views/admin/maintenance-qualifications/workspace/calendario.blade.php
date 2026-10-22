<div class="mw-heading"><h2>Calendario de servicios</h2><button class="mw-button mw-primary" data-maint-open="appointment">＋ Programar servicio</button></div>
<div class="mw-toolbar">
    @include('admin.maintenance-qualifications.workspace.period')
    <div class="mw-segment" aria-label="Vista del calendario">@foreach(['mes' => 'Mes', 'semana' => 'Semana', 'lista' => 'Lista'] as $key => $label)<a href="{{ $url(['vista' => $key, 'seleccion' => null]) }}" class="{{ $mode === $key ? 'is-active' : '' }}" @if($mode === $key) aria-current="true" @endif>{{ $label }}</a>@endforeach</div>
    <form method="get" class="mw-filters">
        <input type="hidden" name="seccion" value="calendario"><input type="hidden" name="fecha" value="{{ $date->toDateString() }}"><input type="hidden" name="vista" value="{{ $mode }}">
        @if($laboratoryId)<input type="hidden" name="laboratory_id" value="{{ $laboratoryId }}">@endif
        <select name="tipo" aria-label="Tipo de servicio"><option value="">Tipo: Todos</option>@foreach($services->pluck('type')->filter()->unique()->sort() as $type)<option @selected(($filters['tipo'] ?? '') === $type)>{{ $type }}</option>@endforeach</select><button class="mw-button">Filtrar</button>
    </form>
</div>
@if($mode !== 'lista')
    <div class="mw-scroll" data-disable-sticky-x>
    @if($mode === 'semana')
        @php $hours = $appointments->map(fn ($a) => $a->starts_at->hour); $firstHour = min(8, $hours->min() ?? 8); $lastHour = max(18, $hours->max() ?? 18); @endphp
        <table class="mw-calendar mw-week"><thead><tr><th>Hora</th>@for($i=0;$i<7;$i++) @php $day=$start->copy()->addDays($i); @endphp<th class="{{ $day->isToday() ? 'mw-today' : '' }}">{{ $day->locale('es')->translatedFormat('D d') }}</th>@endfor</tr></thead><tbody>
        @for($hour=$firstHour;$hour<=$lastHour;$hour++)<tr><th>{{ sprintf('%02d:00', $hour) }}</th>@for($i=0;$i<7;$i++) @php $day=$start->copy()->addDays($i); @endphp<td>
            @foreach($appointments->filter(fn ($a) => $a->starts_at->isSameDay($day) && $a->starts_at->hour === $hour) as $event)@include('admin.maintenance-qualifications.workspace.event')@endforeach
        </td>@endfor</tr>@endfor</tbody></table>
    @else
        <div class="mw-month">@foreach(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $day)<strong>{{ $day }}</strong>@endforeach
        @for($day=$start->copy();$day->lte($end);$day->addDay())<div class="mw-day {{ $day->isToday() ? 'mw-today' : '' }} {{ $day->month !== $date->month ? 'mw-other-month' : '' }}"><span>{{ $day->day }}</span>@foreach($appointments->filter(fn ($a) => $a->starts_at->isSameDay($day)) as $event)@include('admin.maintenance-qualifications.workspace.event')@endforeach</div>@endfor</div>
    @endif
    </div>
@endif
<h3 class="mw-subheading">Servicios {{ $mode === 'mes' ? 'del periodo' : 'de la semana' }}</h3>
@include('admin.maintenance-qualifications.workspace.appointments-table', ['calendarList' => true])
