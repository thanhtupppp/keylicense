@props([
    'name',
    'label' => null,
    'checked' => false,
    'description' => null,
])

@php
    $id = $attributes->get('id', $name);
    $error = null;

    if (isset($errors) && is_object($errors) && method_exists($errors, 'first')) {
        $error = $errors->first($name);
    }

    $isChecked = old($name, $checked);
@endphp

<div {{ $attributes->class('space-y-1.5') }}>
    <label for="{{ $id }}" class="flex cursor-pointer items-start gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 transition hover:border-white/20 hover:bg-white/10">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="checkbox"
            class="mt-0.5 h-4 w-4 rounded border-white/25 bg-[#0A1220] text-[#F8B803] shadow-sm outline-none transition focus:ring-2 focus:ring-[#F8B803]/30 focus:ring-offset-0"
            @checked($isChecked)
        >

        <div class="min-w-0">
            <div class="text-sm font-medium text-slate-100">
                {{ $label ?? str_replace('_', ' ', ucfirst($name)) }}
            </div>

            @if ($description)
                <p class="mt-0.5 text-xs text-slate-400">{{ $description }}</p>
            @endif
        </div>
    </label>

    @if ($error)
        <p class="text-xs text-rose-300">{{ $error }}</p>
    @endif
</div>
