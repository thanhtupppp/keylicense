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
        ]);
    }

    public function retry(string $id): JsonResponse
    {
        $job = FailedJobEntry::query()->findOrFail($id);
        $job->delete();

        return ApiResponse::success(['retried' => true]);
    }

    public function discard(string $id): JsonResponse
    {
        $job = FailedJobEntry::query()->findOrFail($id);
        $job->delete();

        return ApiResponse::success(['discarded' => true]);
    }
}
