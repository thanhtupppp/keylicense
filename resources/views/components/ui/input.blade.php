@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'autocomplete' => null,
    'prefix' => null,
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

    <div class="relative">
        @if ($prefix)
            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-500">{{ $prefix }}</span>
        @endif

        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($required) required @endif
            {{ $attributes->except(['id'])->class(
                'block w-full rounded-2xl border border-white/10 bg-[#0A1220] '.($prefix ? 'pl-10 ' : '').'px-4 py-3 text-sm text-slate-50 placeholder:text-slate-500 shadow-sm outline-none transition focus:border-[#F8B803]/70 focus:ring-2 focus:ring-[#F8B803]/20 '.($error ? 'border-rose-400/80 focus:border-rose-400/80 focus:ring-rose-400/20' : '')
            ) }}
        >
    </div>

    @if ($error)
        <p class="text-xs text-rose-300">{{ $error }}</p>
    @endif
</div>
