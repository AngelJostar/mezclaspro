@php($fieldType = in_array($type ?? '', ['number', 'date', 'datetime-local', 'textarea']) ? $type : 'text')
<label class="quotation-field {{ ($wide ?? false) ? 'quotation-wide' : '' }}">
    <span>{{ $label }}@if ($required ?? false)<span aria-hidden="true"> *</span>@endif</span>
    @if ($fieldType === 'textarea')
        <textarea name="{{ $name }}" rows="3" maxlength="{{ $max ?? 500 }}" @required($required ?? false)></textarea>
    @else
        <input name="{{ $name }}" type="{{ $fieldType }}" @required($required ?? false)
            @if ($fieldType === 'number') step="{{ $step ?? 'any' }}" min="{{ $min ?? '0.0001' }}" @endif
            @if (isset($max) && $fieldType !== 'text') max="{{ $max }}" @endif
            @if ($fieldType === 'text') maxlength="{{ $max ?? 255 }}" @endif>
    @endif
</label>
