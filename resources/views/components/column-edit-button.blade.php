@props(['label', 'disabled' => false])
<button type="button" @disabled($disabled) title="{{ $label }}" aria-label="{{ $label }}" aria-haspopup="dialog"
    {{ $attributes->class('inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-indigo-200 bg-white text-indigo-800 hover:bg-indigo-50 focus:ring-2 focus:ring-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed') }}>
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 3 5 5M4 15 16 3a3.5 3.5 0 0 1 5 5L9 20l-6 1 1-6Z" /></svg>
</button>
