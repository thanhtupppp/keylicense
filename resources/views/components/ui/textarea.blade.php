@props([
    'label' => null,
    'name' => null,
    'placeholder' => null,
    'required' => false,
])

<div class="field">
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif>{{ $label }}</label>
    @endif
    <textarea
        {{ $attributes }}
        @if ($name) name="{{ $name }}" @endif
        @if ($name) id="{{ $name }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
    >{{ $slot }}</textarea>
</div>
