<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Decree;
use App\Models\Employee;
use App\Models\EmployeeAdditionalDuty;
use App\Models\User;
use App\Services\Employee\ProfileCompletenessService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Laporan (PRD F8.1–F8.5): daftar GTK, rekap SK per periode, pemegang
 * tugas tambahan, profil belum lengkap, mendekati pensiun, akun belum login.
 * Seluruh laporan dapat diekspor ke Excel dan PDF.
 */
class ReportController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Employee::class);

        $reports = [
            ['key' => 'employees', 'label' => 'Daftar GTK', 'description' => 'Seluruh data GTK sesuai filter aktif.', 'icon' => 'users'],
            ['key' => 'decrees', 'label' => 'Rekap SK per Periode', 'description' => 'SK terbit dikelompokkan per bulan dan jenis.', 'icon' => 'scroll'],
            ['key' => 'duties', 'label' => 'Pemegang Tugas Tambahan', 'description' => 'Penetapan tugas per satuan kerja dan tahun pelajaran.', 'icon' => 'briefcase'],
            ['key' => 'incomplete', 'label' => 'Profil Belum Lengkap', 'description' => 'GTK dengan kelengkapan profil di bawah 100%.', 'icon' => 'clipboard'],
            ['key' => 'retiring', 'label' => 'Mendekati Usia Pensiun', 'description' => 'GTK aktif berusia 56 tahun ke atas.', 'icon' => 'user'],
            ['key' => 'never-login', 'label' => 'Akun Belum Pernah Login', 'description' => 'Pemantauan adopsi portal mandiri GTK.', 'icon' => 'key'],
        ];

        return Inertia::render('Admin/Reports/Index', ['reports' => $reports]);
    }

    public function employees(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        $rows = Employee::query()
            ->with(['workUnit:id,code,name', 'position:id,name', 'employmentStatus:id,name'])
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($query) => $query
                ->where('nigy', 'like', '%'.$request->string('q').'%')
                ->orWhere('name', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('work_unit_id'), fn ($query) => $query->where('work_unit_id', $request->integer('work_unit_id')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Reports/Show', [
            'reportKey' => 'employees',
            'title' => 'Daftar GTK',
            'filters' => $request->only(['q', 'work_unit_id']),
            'columns' => [
                ['key' => 'nigy', 'label' => 'NIGY'],
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'work_unit.code', 'label' => 'Satker'],
                ['key' => 'position.name', 'label' => 'Jabatan'],
                ['key' => 'employment_status.name', 'label' => 'Status'],
                ['key' => 'is_active', 'label' => 'Aktif'],
            ],
            'rows' => $rows,
            'exportQuery' => base64_encode(json_encode($request->only(['q', 'work_unit_id']))),
        ]);
    }

    public function decrees(Request $request): Response
    {
        $this->authorize('viewAny', Decree::class);

        $rows = Decree::query()
            ->where('status', 'issued')
            ->with(['employee:id,nigy,name', 'decreeType:id,code,name'])
            ->when($request->filled('from'), fn ($query) => $query->whereDate('issued_date', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('issued_date', '<=', $request->string('to')))
            ->when($request->filled('decree_type_id'), fn ($query) => $query->where('decree_type_id', $request->integer('decree_type_id')))
            ->orderByDesc('issued_date')
            ->paginate(20)
            ->withQueryString();

        $summary = Decree::query()
            ->where('status', 'issued')
            ->when($request->filled('from'), fn ($query) => $query->whereDate('issued_date', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('issued_date', '<=', $request->string('to')))
            ->when($request->filled('decree_type_id'), fn ($query) => $query->where('decree_type_id', $request->integer('decree_type_id')))
            ->get(['issued_date'])
            ->countBy(fn (Decree $decree) => $decree->issued_date?->format('Y-m') ?? '—')
            ->sortKeys()
            ->map(fn ($count, $label) => ['label' => $label, 'total' => $count])
            ->values()
            ->all();

        return Inertia::render('Admin/Reports/Show', [
            'reportKey' => 'decrees',
            'title' => 'Rekap SK Terbit per Periode',
            'filters' => $request->only(['from', 'to', 'decree_type_id']),
            'columns' => [
                ['key' => 'decree_number', 'label' => 'Nomor SK'],
                ['key' => 'employee.name', 'label' => 'GTK'],
                ['key' => 'employee.nigy', 'label' => 'NIGY'],
                ['key' => 'decree_type.name', 'label' => 'Jenis'],
                ['key' => 'issued_date', 'label' => 'Tanggal Terbit'],
            ],
            'rows' => $rows,
            'summary' => $summary,
            'exportQuery' => base64_encode(json_encode($request->only(['from', 'to', 'decree_type_id']))),
        ]);
    }

    public function duties(Request $request): Response
    {
        $this->authorize('viewAny', EmployeeAdditionalDuty::class);

        $rows = EmployeeAdditionalDuty::query()
            ->with(['employee:id,nigy,name', 'additionalDuty:id,name', 'workUnit:id,code,name'])
            ->when($request->filled('work_unit_id'), fn ($query) => $query->where('work_unit_id', $request->integer('work_unit_id')))
            ->when($request->filled('academic_year'), fn ($query) => $query->where('academic_year', $request->string('academic_year')))
            ->orderByDesc('start_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Reports/Show', [
            'reportKey' => 'duties',
            'title' => 'Pemegang Tugas Tambahan',
            'filters' => $request->only(['work_unit_id', 'academic_year']),
            'columns' => [
                ['key' => 'employee.name', 'label' => 'GTK'],
                ['key' => 'employee.nigy', 'label' => 'NIGY'],
                ['key' => 'additional_duty.name', 'label' => 'Tugas'],
                ['key' => 'work_unit.code', 'label' => 'Satker'],
                ['key' => 'academic_year', 'label' => 'TP'],
                ['key' => 'start_date', 'label' => 'Mulai'],
                ['key' => 'end_date', 'label' => 'Selesai'],
            ],
            'rows' => $rows,
            'exportQuery' => base64_encode(json_encode($request->only(['work_unit_id', 'academic_year']))),
        ]);
    }

    public function incomplete(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::query()
            ->where('is_active', true)
            ->with(['workUnit:id,code,name', 'position:id,name'])
            ->orderBy('name')
            ->get();

        $rows = $employees
            ->map(function (Employee $employee) {
                $completeness = app(ProfileCompletenessService::class)->evaluate($employee);

                return (object) [
                    'nigy' => $employee->nigy,
                    'name' => $employee->name,
                    'work_unit_code' => $employee->workUnit?->code,
                    'position_name' => $employee->position?->name,
                    'percentage' => $completeness['percentage'],
                    'missing' => implode(', ', array_slice($completeness['missing'], 0, 4)),
                ];
            })
            ->filter(fn ($row) => $row->percentage < 100)
            ->sortByDesc('percentage')
            ->values();

        $rows = new LengthAwarePaginator(
            $rows->forPage($request->integer('page', 1), 20),
            $rows->count(),
            20,
            $request->integer('page', 1),
            ['path' => $request->url()],
        );

        return Inertia::render('Admin/Reports/Show', [
            'reportKey' => 'incomplete',
            'title' => 'Profil GTK Belum Lengkap',
            'filters' => [],
            'columns' => [
                ['key' => 'nigy', 'label' => 'NIGY'],
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'work_unit_code', 'label' => 'Satker'],
                ['key' => 'position_name', 'label' => 'Jabatan'],
                ['key' => 'percentage', 'label' => 'Kelengkapan'],
                ['key' => 'missing', 'label' => 'Yang Kurang'],
            ],
            'rows' => $rows,
            'exportQuery' => '',
        ]);
    }

    public function retiring(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        $rows = Employee::query()
            ->where('is_active', true)
            ->whereNotNull('birth_date')
            ->whereDate('birth_date', '<=', now()->subYears(56))
            ->with(['workUnit:id,code,name', 'position:id,name'])
            ->orderBy('birth_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Reports/Show', [
            'reportKey' => 'retiring',
            'title' => 'GTK Mendekati Usia Pensiun (≥ 56 tahun)',
            'filters' => [],
            'columns' => [
                ['key' => 'nigy', 'label' => 'NIGY'],
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'birth_date', 'label' => 'Tgl Lahir'],
                ['key' => 'work_unit.code', 'label' => 'Satker'],
                ['key' => 'position.name', 'label' => 'Jabatan'],
            ],
            'rows' => $rows,
            'exportQuery' => '',
        ]);
    }

    public function neverLogin(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $rows = User::query()
            ->where('role', 'employee')
            ->whereNull('last_login_at')
            ->with(['employee:id,nigy,name', 'workUnit:id,code,name'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Reports/Show', [
            'reportKey' => 'never-login',
            'title' => 'Akun GTK Belum Pernah Login',
            'filters' => [],
            'columns' => [
                ['key' => 'username', 'label' => 'Username (NIGY)'],
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'employee.nigy', 'label' => 'NIGY GTK'],
                ['key' => 'work_unit.code', 'label' => 'Satker'],
                ['key' => 'created_at', 'label' => 'Akun Dibuat'],
            ],
            'rows' => $rows,
            'exportQuery' => '',
        ]);
    }

    public function export(Request $request, string $report): BinaryFileResponse
    {
        $this->authorize('viewAny', Employee::class);

        $columns = match ($report) {
            'decrees' => [
                ['key' => 'decree_number', 'label' => 'Nomor SK'],
                ['key' => 'employee.name', 'label' => 'GTK'],
                ['key' => 'employee.nigy', 'label' => 'NIGY'],
                ['key' => 'decree_type.name', 'label' => 'Jenis'],
                ['key' => 'issued_date', 'label' => 'Tanggal Terbit'],
            ],
            'duties' => [
                ['key' => 'employee.name', 'label' => 'GTK'],
                ['key' => 'additional_duty.name', 'label' => 'Tugas'],
                ['key' => 'work_unit.code', 'label' => 'Satker'],
                ['key' => 'academic_year', 'label' => 'TP'],
                ['key' => 'start_date', 'label' => 'Mulai'],
                ['key' => 'end_date', 'label' => 'Selesai'],
            ],
            'retiring' => [
                ['key' => 'nigy', 'label' => 'NIGY'],
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'birth_date', 'label' => 'Tgl Lahir'],
                ['key' => 'work_unit.code', 'label' => 'Satker'],
                ['key' => 'position.name', 'label' => 'Jabatan'],
            ],
            'never-login' => [
                ['key' => 'username', 'label' => 'Username'],
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'work_unit.code', 'label' => 'Satker'],
                ['key' => 'created_at', 'label' => 'Akun Dibuat'],
            ],
            default => [
                ['key' => 'nigy', 'label' => 'NIGY'],
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'work_unit.code', 'label' => 'Satker'],
                ['key' => 'position.name', 'label' => 'Jabatan'],
                ['key' => 'employment_status.name', 'label' => 'Status'],
                ['key' => 'is_active', 'label' => 'Aktif'],
            ],
        };

        $query = $this->reportQuery($report, $request);

        return Excel::download(new ReportExport($query, $columns), 'laporan-'.$report.'-'.now()->format('Ymd-His').'.xlsx');
    }

    public function exportPdf(Request $request, string $report): \Illuminate\Http\Response
    {
        $this->authorize('viewAny', Employee::class);

        $titles = [
            'employees' => 'Daftar GTK',
            'decrees' => 'Rekap SK Terbit',
            'duties' => 'Pemegang Tugas Tambahan',
            'retiring' => 'GTK Mendekati Usia Pensiun',
            'never-login' => 'Akun Belum Pernah Login',
            'incomplete' => 'Profil GTK Belum Lengkap',
        ];

        $columns = [
            ['key' => 'nigy', 'label' => 'NIGY'],
            ['key' => 'name', 'label' => 'Nama'],
            ['key' => 'work_unit.code', 'label' => 'Satker'],
            ['key' => 'position.name', 'label' => 'Jabatan'],
            ['key' => 'employment_status.name', 'label' => 'Status'],
        ];

        if ($report === 'decrees') {
            $columns = [
                ['key' => 'decree_number', 'label' => 'Nomor SK'],
                ['key' => 'employee.name', 'label' => 'GTK'],
                ['key' => 'decree_type.name', 'label' => 'Jenis'],
                ['key' => 'issued_date', 'label' => 'Tanggal'],
            ];
        }

        $rows = $this->reportQuery($report, $request)->limit(500)->getQuery()->get();

        $pdf = Pdf::loadView('reports.pdf', [
            'title' => $titles[$report] ?? 'Laporan',
            'generated_at' => now()->translatedFormat('d F Y H:i'),
            'columns' => $columns,
            'rows' => $rows,
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-'.$report.'.pdf"',
        ]);
    }

    /** @return \Illuminate\Database\Eloquent\Builder<*> */
    protected function reportQuery(string $report, Request $request): Builder
    {
        return match ($report) {
            'decrees' => Decree::query()
                ->where('status', 'issued')
                ->with(['employee:id,nigy,name', 'decreeType:id,name'])
                ->when($request->filled('from'), fn ($query) => $query->whereDate('issued_date', '>=', $request->string('from')))
                ->when($request->filled('to'), fn ($query) => $query->whereDate('issued_date', '<=', $request->string('to')))
                ->when($request->filled('decree_type_id'), fn ($query) => $query->where('decree_type_id', $request->integer('decree_type_id')))
                ->orderByDesc('issued_date'),
            'duties' => EmployeeAdditionalDuty::query()
                ->with(['employee:id,nigy,name', 'additionalDuty:id,name', 'workUnit:id,code,name'])
                ->when($request->filled('work_unit_id'), fn ($query) => $query->where('work_unit_id', $request->integer('work_unit_id')))
                ->when($request->filled('academic_year'), fn ($query) => $query->where('academic_year', $request->string('academic_year')))
                ->orderByDesc('start_date'),
            'retiring' => Employee::query()
                ->where('is_active', true)
                ->whereNotNull('birth_date')
                ->whereDate('birth_date', '<=', now()->subYears(56))
                ->with(['workUnit:id,code,name', 'position:id,name'])
                ->orderBy('birth_date'),
            'never-login' => User::query()
                ->where('role', 'employee')
                ->whereNull('last_login_at')
                ->with(['employee:id,nigy,name', 'workUnit:id,code,name'])
                ->orderBy('name'),
            default => Employee::query()
                ->with(['workUnit:id,code,name', 'position:id,name', 'employmentStatus:id,name'])
                ->when($request->filled('q'), fn ($query) => $query->where(fn ($query) => $query
                    ->where('nigy', 'like', '%'.$request->string('q').'%')
                    ->orWhere('name', 'like', '%'.$request->string('q').'%')))
                ->when($request->filled('work_unit_id'), fn ($query) => $query->where('work_unit_id', $request->integer('work_unit_id')))
                ->orderBy('name'),
        };
    }
}
