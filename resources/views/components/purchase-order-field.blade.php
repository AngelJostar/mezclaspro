@props(['id', 'label', 'value' => '', 'type' => 'text', 'required' => false, 'readonly' => false, 'rows' => 2, 'restore' => true])
@php($fieldValue = $restore ? old($id, $value) : $value)
@if ($type === 'textarea')
<textarea id="{{ $id }}" name="{{ $id }}" aria-label="{{ $label }}" rows="{{ $rows }}"
    @required($required) @readonly($readonly) {{ $attributes->class(['po-input']) }}>{{ $fieldValue }}</textarea>
@else
<input id="{{ $id }}" name="{{ $id }}" type="{{ $type }}" aria-label="{{ $label }}" value="{{ $fieldValue }}"
    @required($required) @readonly($readonly) {{ $attributes->class(['po-input']) }}>
@endif
