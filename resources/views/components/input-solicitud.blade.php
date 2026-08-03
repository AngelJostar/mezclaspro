@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => ' focus:border-indigo-500 border-t-0 border-r-0 border-l-0 border-b-2 border-dotted h-5']) !!}>
