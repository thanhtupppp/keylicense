<x-site-layout title="KeyLicense - License management platform" description="Nền tảng quản lý license, sản phẩm và kích hoạt bản quyền cho doanh nghiệp.">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
            <div>
                <div class="inline-flex items-center gap-3 rounded-full border border-[#F8B803]/20 bg-white/5 px-4 py-2 text-sm text-slate-200 backdrop-blur">
                    <span class="h-2 w-2 rounded-full bg-[#F8B803]"></span>
                    KeyLicense Platform
                </div>

                <h1 class="mt-6 max-w-2xl text-4xl font-bold tracking-tight text-white sm:text-6xl">
                    Quản lý license, activation và product trên một nền tảng.
                </h1>

                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                    Xây dựng cho các team cần cấp license, giám sát kích hoạt, hỗ trợ floating seats và audit trạng thái vận hành một cách an toàn.
                </p>

                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="{{ route('admin.login') }}" class="rounded-full bg-linear-to-r from-[#F8B803] to-[#F0ACB8] px-6 py-3 text-sm font-semibold text-[#07111f] shadow-lg shadow-[#F8B803]/20 transition hover:brightness-105">
                        Vào Admin Portal
                    </a>
                    <a href="#features" class="rounded-full border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-white transition hover:border-[#F8B803]/40 hover:bg-[#F8B803]/10">
                        Khám phá tính năng
                    </a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-3xl border border-white/10 bg-white/10 p-6 backdrop-blur-xl">
                    <div class="text-sm text-slate-300">License lifecycle</div>
                    <div class="mt-2 text-2xl font-bold text-white">Create, revoke, renew</div>
                    <p class="mt-3 text-sm leading-6 text-slate-300">Quản lý vòng đời license với luồng rõ ràng và kiểm soát trạng thái chặt chẽ.</p>
                </div>
                <div class="rounded-3xl border border-white/10 bg-white/10 p-6 backdrop-blur-xl">
                    <div class="text-sm text-slate-300">Activation flow</div>
                    <div class="mt-2 text-2xl font-bold text-white">Per-device, per-user, floating</div>
                    <p class="mt-3 text-sm leading-6 text-slate-300">Hỗ trợ nhiều mô hình cấp phép cho các use case khác nhau.</p>
                </div>
                <div id="features" class="rounded-3xl border border-white/10 bg-white/10 p-6 backdrop-blur-xl sm:col-span-2">
                    <div class="text-sm text-slate-300">Brand system</div>
                    <div class="mt-2 text-2xl font-bold text-white">Đồng bộ UI giữa frontend và admin</div>
                    <p class="mt-3 text-sm leading-6 text-slate-300">Palette tối, accent vàng hổ phách và pink accent theo cùng một ngôn ngữ thị giác.</p>
                </div>
            </div>
        </div>
    </section>
</x-site-layout>
