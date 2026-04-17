<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use Illuminate\View\View;

class AdminPortalEntitlementController extends Controller
{
    public function detail(string $id): View
    {
        return view('admin.entitlement-detail', [
            'entitlement' => Entitlement::query()->with(['plan.product', 'licenses.activations'])->findOrFail($id),
        ]);
    }
}
