<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $query = AuditLog::query()->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('event_type', 'like', "%{$search}%")
                    ->orWhere('subject_id', 'like', "%{$search}%")
                    ->orWhere('actor_name', 'like', "%{$search}%");
            });
        }

        foreach (['eventType' => 'event_type', 'subjectType' => 'subject_type', 'severity' => 'severity', 'subjectId' => 'subject_id', 'ipAddress' => 'ip_address'] as $input => $column) {
            if ($request->filled($input)) {
                $query->where($column, $request->input($input));
            }
        }

        if ($request->filled('dateFrom')) {
            $query->whereDate('created_at', '>=', $request->input('dateFrom'));
        }

        if ($request->filled('dateTo')) {
            $query->whereDate('created_at', '<=', $request->input('dateTo'));
        }

        $logs = $query->get();
        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'created_at',
                'event_type',
                'event_label',
                'actor_label',
                'subject_type',
                'subject_id',
                'ip_address',
                'severity',
                'result',
                'payload',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->event_type,
                    $log->event_label,
                    $log->actor_label,
                    $log->subject_type,
                    $log->subject_id,
                    $log->ip_address,
                    $log->severity,
                    $log->result,
                    json_encode($log->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
