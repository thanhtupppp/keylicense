@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'autocomplete' => null,
])

<div class="field">
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif>{{ $label }}</label>
    @endif
    <input
        {{ $attributes }}
        @if ($name) name="{{ $name }}" @endif
        @if ($name) id="{{ $name }}" @endif
        type="{{ $type }}"
        @if (! is_null($value)) value="{{ $value }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
    />
</div>
