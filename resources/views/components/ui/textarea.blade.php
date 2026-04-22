@props([
    'name',
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'rows' => 4,
    'required' => false,
])

@php
    $id = $attributes->get('id', $name);
    $error = null;

    if (isset($errors) && is_object($errors) && method_exists($errors, 'first')) {
        $error = $errors->first($name);
    }
@endphp

<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-slate-200">{{ $label }}</label>
    @endif

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        {{ $attributes->except(['id'])->class(
            'block w-full rounded-2xl border border-white/10 bg-[#0A1220] px-4 py-3 text-sm text-slate-50 placeholder:text-slate-500 shadow-sm outline-none transition focus:border-[#F8B803]/70 focus:ring-2 focus:ring-[#F8B803]/20 '.($error ? 'border-rose-400/80 focus:border-rose-400/80 focus:ring-rose-400/20' : '')
        ) }}
    >{{ old($name, $value) }}</textarea>

    @if ($error)
        <p class="text-xs text-rose-300">{{ $error }}</p>
    @endif
</div>
