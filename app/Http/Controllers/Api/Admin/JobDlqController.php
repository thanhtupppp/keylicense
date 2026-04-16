<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FailedJobEntry;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class JobDlqController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'jobs' => FailedJobEntry::query()->latest('failed_at')->get(),
            'count' => FailedJobEntry::query()->count(),
        ]);
    }

    public function retry(string $id): JsonResponse
    {
        FailedJobEntry::query()->findOrFail($id)->delete();

        return ApiResponse::success(['retried' => true]);
    }

    public function discard(string $id): JsonResponse
    {
        FailedJobEntry::query()->findOrFail($id)->delete();

        return ApiResponse::success(['discarded' => true]);
    }
}
