<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Penampil audit log (PRD F9.3–F9.5) — append-only, tanpa rute hapus/ubah.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = AuditLog::query()
            ->with('user:id,name')
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($request->filled('auditable_type'), fn ($query) => $query->where('auditable_type', $request->string('auditable_type')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->string('to')))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['user_id', 'action', 'auditable_type', 'from', 'to']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'entityTypes' => AuditLog::query()
                ->distinct()
                ->orderBy('auditable_type')
                ->pluck('auditable_type')
                ->map(fn ($type) => $type)
                ->values(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::query()
            ->with('user:id,name')
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($request->filled('auditable_type'), fn ($query) => $query->where('auditable_type', $request->string('auditable_type')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->string('to')))
            ->orderByDesc('created_at');

        return Excel::download(new ReportExport($query, [
            ['key' => 'created_at', 'label' => 'Waktu'],
            ['key' => 'user.name', 'label' => 'Pengguna'],
            ['key' => 'action', 'label' => 'Aksi'],
            ['key' => 'auditable_type', 'label' => 'Entitas'],
            ['key' => 'auditable_id', 'label' => 'ID'],
            ['key' => 'old_values', 'label' => 'Nilai Lama'],
            ['key' => 'new_values', 'label' => 'Nilai Baru'],
            ['key' => 'ip', 'label' => 'IP'],
        ]), 'audit-log-'.now()->format('Ymd-His').'.xlsx');
    }
}
