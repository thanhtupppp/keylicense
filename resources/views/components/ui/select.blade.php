@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
])

@php
    $id = $attributes->get('id', $name);
    $error = null;

    if (isset($errors) && is_object($errors) && method_exists($errors, 'first')) {
        $error = $errors->first($name);
    }

    $currentValue = old($name, $value);
@endphp

<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-slate-200">{{ $label }}</label>
    @endif

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        {{ $attributes->except(['id'])->class(
            'block w-full rounded-2xl border border-white/10 bg-[#0A1220] px-4 py-3 text-sm text-slate-50 shadow-sm outline-none transition focus:border-[#F8B803]/70 focus:ring-2 focus:ring-[#F8B803]/20 '.($error ? 'border-rose-400/80 focus:border-rose-400/80 focus:ring-rose-400/20' : '')
        ) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $currentValue === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @if ($error)
        <p class="text-xs text-rose-300">{{ $error }}</p>
    @endif
</div>
