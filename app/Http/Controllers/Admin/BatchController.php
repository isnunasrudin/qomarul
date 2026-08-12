<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DecreeBatchStatus;
use App\Enums\DecreeStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessDecreeBatchJob;
use App\Jobs\SignDecreeJob;
use App\Models\DecreeBatch;
use App\Models\DecreeType;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Position;
use App\Models\WorkUnit;
use App\Services\Decree\BatchDecreeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class BatchController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', DecreeBatch::class);

        $batches = DecreeBatch::query()
            ->with('decreeType:id,code,name')
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Admin/Batches/Index', [
            'batches' => $batches,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', DecreeBatch::class);

        $employees = Employee::query()
            ->with(['workUnit:id,code,name', 'position:id,name'])
            ->where('is_active', true)
            ->when($request->filled('work_unit_id'), fn ($query) => $query->where('work_unit_id', $request->integer('work_unit_id')))
            ->when($request->filled('position_id'), fn ($query) => $query->where('position_id', $request->integer('position_id')))
            ->when($request->filled('employment_status_id'), fn ($query) => $query->where('employment_status_id', $request->integer('employment_status_id')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q')->trim();

                $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('nigy', 'like', "%{$q}%"));
            })
            ->orderBy('name')
            ->get(['id', 'nigy', 'name', 'work_unit_id', 'position_id', 'employment_status_id', 'foundation_start_date']);

        return Inertia::render('Admin/Batches/Create', [
            'employees' => $employees,
            'filters' => $request->only(['q', 'work_unit_id', 'position_id', 'employment_status_id']),
            'decreeTypes' => DecreeType::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'workUnits' => WorkUnit::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'positions' => Position::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employmentStatuses' => EmploymentStatus::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'maxBatchSize' => BatchDecreeService::MAX_BATCH_SIZE,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', DecreeBatch::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'decree_type_id' => ['required', 'integer', 'exists:decree_types,id'],
            'academic_year' => ['required', 'string', 'max:9'],
            'effective_date' => ['required', 'date'],
            'issued_date' => ['required', 'date'],
            'issued_place' => ['nullable', 'string', 'max:255'],
            'appointed_as' => ['nullable', 'string', 'max:255'],
            'employee_ids' => ['required', 'array', 'max:500'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ]);

        try {
            $batch = app(BatchDecreeService::class)->createBatch(
                $data['employee_ids'],
                $data,
                $request->user(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['employee_ids' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.batches.show', $batch)->with('success', 'Batch dibuat: '.$batch->decrees()->count().' draft SK siap diproses.');
    }

    public function show(DecreeBatch $batch): Response
    {
        $this->authorize('view', $batch);

        $batch->load([
            'decreeType:id,code,name',
            'decrees' => fn ($query) => $query->with('employee:id,nigy,name,work_unit_id', 'employee.workUnit:id,code'),
        ]);

        $batch->setRelation(
            'decrees',
            $batch->decrees->sortBy(fn ($decree) => $decree->employee?->name)->values(),
        );

        return Inertia::render('Admin/Batches/Show', [
            'batch' => $batch,
            'can' => [
                'process' => request()->user()->can('process', $batch),
                'sign' => request()->user()->can('sign', $batch),
                'cancel' => request()->user()->can('cancel', $batch),
            ],
        ]);
    }

    public function process(DecreeBatch $batch): RedirectResponse
    {
        $this->authorize('process', $batch);

        $batch->update(['status' => DecreeBatchStatus::Processing]);

        ProcessDecreeBatchJob::dispatch($batch->id, request()->user()->id);

        return back()->with('success', 'Batch sedang diproses. Muat ulang halaman untuk melihat hasil.');
    }

    /** @return array<string, int|string> */
    public function progress(DecreeBatch $batch): array
    {
        $this->authorize('view', $batch);

        $total = $batch->total;
        $issued = $batch->decrees()->where('status', DecreeStatus::Issued->value)->count();
        $succeeded = $batch->succeeded;
        $failed = $batch->failed;

        return [
            'status' => $batch->status->value,
            'total' => $total,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'issued' => $issued,
        ];
    }

    public function sign(Request $request, DecreeBatch $batch): RedirectResponse
    {
        $this->authorize('sign', $batch);

        $decrees = $batch->decrees()->where('status', DecreeStatus::Verified->value)->pluck('id');

        if ($decrees->isEmpty()) {
            return back()->withErrors(['action' => 'Tidak ada SK terverifikasi dalam batch ini.']);
        }

        $batch->update(['status' => DecreeBatchStatus::Signing]);

        $jobs = $decrees->map(fn ($id) => new SignDecreeJob($id, $request->user()->id));

        Bus::batch($jobs)->name('sign-batch-'.$batch->id)->dispatch();

        return back()->with('success', 'Penandatanganan batch dimulai ('.$decrees->count().' SK). Progres tampil saat halaman dimuat ulang.');
    }

    public function cancel(DecreeBatch $batch): RedirectResponse
    {
        $this->authorize('cancel', $batch);

        try {
            app(BatchDecreeService::class)->cancelBatch($batch, request()->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }

        return back()->with('success', 'Batch dibatalkan.');
    }

    public function downloadZip(DecreeBatch $batch): StreamedResponse
    {
        $this->authorize('view', $batch);

        $decrees = $batch->decrees()
            ->where('status', DecreeStatus::Issued->value)
            ->whereNotNull('pdf_path')
            ->with('employee.workUnit:id,code')
            ->get();

        if ($decrees->isEmpty()) {
            abort(422, 'Belum ada SK terbit dalam batch ini.');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'batch').'.zip';
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Tidak dapat membuat arsip ZIP.');
        }

        foreach ($decrees as $decree) {
            $folder = $decree->employee->workUnit->code ?? 'tanpa-satker';
            $filename = $folder.'/'.str_replace(['/', '\\'], '-', $decree->decree_number ?? $decree->uuid).'-'.$decree->employee->nigy.'.pdf';

            $zip->addFromString($filename, Storage::disk('private')->get($decree->pdf_path));
        }

        $zip->close();

        return response()->streamDownload(function () use ($zipPath): void {
            readfile($zipPath);
            @unlink($zipPath);
        }, 'batch-'.$batch->id.'-'.now()->format('Ymd-His').'.zip', ['Content-Type' => 'application/zip']);
    }

    public function downloadCombined(DecreeBatch $batch): \Illuminate\Http\Response
    {
        $this->authorize('view', $batch);

        $decrees = $batch->decrees()
            ->where('status', DecreeStatus::Issued->value)
            ->whereNotNull('pdf_path')
            ->get();

        if ($decrees->isEmpty()) {
            abort(422, 'Belum ada SK terbit dalam batch ini.');
        }

        $pdf = new Fpdi;

        foreach ($decrees as $decree) {
            $path = Storage::disk('private')->path($decree->pdf_path);
            $pageCount = $pdf->setSourceFile($path);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        }

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="batch-'.$batch->id.'-gabungan.pdf"',
        ]);
    }
}
