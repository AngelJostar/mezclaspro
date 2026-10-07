<nav class="ht-quick-filters conciliation-context" aria-label="Filtrar solicitudes de conciliación">
    @foreach (\App\Support\ConciliationInboxTable::TABS as $tab => $label)
        <a href="{{ route('admin.instituciones.reportes', array_replace($filterQuery, ['bandeja' => $tab])) }}" @if ($activeTab === $tab) aria-current="page" @endif>
            {{ $label }} <span class="ml-2" data-conciliation-count="{{ $tab }}">{{ $tabCounts[$tab] }}</span>
        </a>
    @endforeach
</nav>
