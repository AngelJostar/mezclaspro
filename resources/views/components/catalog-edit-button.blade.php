@props(['href'])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center whitespace-nowrap rounded-full bg-azul-prodifem px-4 py-2 text-xs font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-300',
    ]) }}>
    <i class="fa-solid fa-pen pr-1"></i> Editar
</a>
