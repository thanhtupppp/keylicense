@extends('layouts.client', [
    'title' => 'Chi tiết license | KeyLicense',
    'description' => 'Chi tiết license, policy và activation',
])

@section('content')
    <div class="topbar">
        <div class="brand">
            <span class="mark"></span>
            <div>
                <h1 class="title">Chi tiết license</h1>
                <div class="muted">{{ $licenseId ?? 'License #' }} · trạng thái, policy và activation</div>
            </div>
        </div>
        <x-ui.button :href="route('client.licenses')" variant="alt">← Trở về danh sách</x-ui.button>
    </div>

    <div class="grid cols-2">
        <section class="card stack">
            <x-ui.section-header title="Tổng quan license" subtitle="Thông tin chính của key và entitlement." />
            <div class="stats">
                <x-ui.stat value="Hoạt động" label="Trạng thái" />
                <x-ui.stat value="2027-04-13" label="Hết hạn" />
                <x-ui.stat value="3/3" label="Activation" />
            </div>
            <div class="codebox" style="margin-top:16px;">
                key_display: PROD1-****-****-IJKL4<br>
                product_code: PLUGIN_SEO<br>
                plan_code: SEO_PRO_ANNUAL<br>
                customer: customer@example.com
            </div>
        </section>

        <aside class="stack">
            <section class="card">
                <x-ui.section-header title="Policy snapshot" subtitle="Feature flags và giới hạn hiện tại." />
                <div class="codebox">
                    EXPORT_CSV: true<br>
                    MAX_KEYWORDS: 500<br>
                    offline_allowed: false<br>
                    grace_period_days: 7<br>
                    max_activations: 3
                </div>
            </section>

            <section class="card">
                <x-ui.section-header title="Activation" subtitle="Danh sách các thiết bị đang sử dụng license." />
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Thiết bị</th>
                                <th>IP</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="mono">Desktop Win11</td>
                                <td>1.2.3.4</td>
                                <td><span class="badge ok">Hoạt động</span></td>
                                <td><x-ui.button variant="danger">Thu hồi</x-ui.button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </aside>
    </div>
@endsection
