<td class="w-[13rem] min-w-[13rem] max-w-[13rem] px-2 py-2 text-center whitespace-normal break-words">
    {{ $institutionHospital?->instituciones->pluck('nombre')->filter()->unique()->implode(', ') ?: 'Sin institución' }}
</td>
