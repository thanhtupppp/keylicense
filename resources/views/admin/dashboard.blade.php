@extends('layouts.admin')

@section('content')
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.24em] text-[#F8B803]">Overview</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">
                    Dashboard
                </h2>
                <p class="mt-2 text-sm text-slate-400">Theo dõi trạng thái hệ thống, hoạt động license và các chỉ số vận hành chính.</p>
            </div>
        </div>
    </x-slot>

    @livewire('admin-dashboard')
@endsection
