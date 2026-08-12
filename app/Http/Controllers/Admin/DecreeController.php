<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DecreeStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Decree;
use App\Models\DecreeSignature;
use App\Models\DecreeType;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkUnit;
use App\Notifications\DecreeStatusChanged;
use App\Services\Decree\DecreeSnapshotBuilder;
use App\Services\Decree\DecreeWorkflowService;
use App\Services\Decree\PdfRenderer;
use App\Services\Signing\CertificateManager;
use App\Services\Signing\SelfSignedPkcs12Signer;
use App\Support\QrCodePng;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DecreeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Decree::class);

        $decrees = Decree::query()
            ->with(['employee:id,nigy,name', 'decreeType:id,code,name', 'workUnit:id,code,name'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q')->trim();

                $query->where(fn ($query) => $query
                    ->where('decree_number', 'like', "%{$q}%")
                    ->orWhereHas('employee', fn ($query) => $query
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('nigy', 'like', "%{$q}%")));
            })
            ->when($request->filled('work_unit_id'), fn ($query) => $query->where('work_unit_id', $request->integer('work_unit_id')))
            ->when($request->filled('decree_type_id'), fn ($query) => $query->where('decree_type_id', $request->integer('decree_type_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('academic_year'), fn ($query) => $query->where('academic_year', $request->string('academic_year')))
            ->when($request->filled('is_legacy'), fn ($query) => $query->where('is_legacy', $request->boolean('is_legacy')))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Decrees/Index', [
            'decrees' => $decrees,
            'filters' => $request->only(['q', 'work_unit_id', 'decree_type_id', 'status', 'academic_year', 'is_legacy']),
            'workUnits' => $this->visibleWorkUnits()->get(['id', 'code', 'name']),
            'decreeTypes' => DecreeType::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'statuses' => collect(DecreeStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Decree::class);

        return Inertia::render('Admin/Decrees/Form', [
            'employee' => $request->filled('employee_id')
                ? Employee::query()->with(['workUnit:id,code,name', 'position:id,name'])->findOrFail($request->integer('employee_id'))
                : null,
            'employees' => Employee::query()
                ->where('is_active', true)
                ->with('position:id,name')
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'nigy', 'name', 'work_unit_id', 'position_id']),
            'decreeTypes' => DecreeType::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'workUnits' => $this->visibleWorkUnits()->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Decree::class);

        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'decree_type_id' => ['required', 'integer', 'exists:decree_types,id'],
            'work_unit_id' => ['required', 'integer', 'exists:work_units,id'],
            'academic_year' => ['required', 'string', 'max:9'],
            'effective_date' => ['required', 'date'],
            'issued_date' => ['required', 'date'],
            'issued_place' => ['nullable', 'string', 'max:255'],
            'appointed_as' => ['nullable', 'string', 'max:255'],
            'position_snapshot' => ['nullable', 'string', 'max:255'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);

        $data = array_merge($data, [
            'uuid' => (string) Str::uuid(),
            'position_snapshot' => $data['position_snapshot'] ?: $employee->position?->name,
            'appointed_as' => $data['appointed_as'] ?: $employee->position?->name,
            'issued_place' => $data['issued_place'] ?: Setting::get('foundation.default_issued_place', 'Gondang'),
            'status' => DecreeStatus::Draft,
            'created_by' => $request->user()->id,
        ]);

        $decree = Decree::create($data);

        return redirect()->route('admin.decrees.show', $decree)->with('success', __('common.created'));
    }

    public function show(Decree $decree): Response
    {
        $this->authorize('view', $decree);

        $decree->load([
            'employee:id,nigy,name,work_unit_id,position_id',
            'employee.workUnit:id,name',
            'employee.position:id,name',
            'decreeType',
            'workUnit:id,code,name',
            'workflowLogs' => fn ($query) => $query->latest('created_at'),
            'workflowLogs.user:id,name',
            'creator:id,name',
            'verifiedBy:id,name',
            'signedBy:id,name',
            'replacement:id,decree_number,status',
        ]);

        return Inertia::render('Admin/Decrees/Show', [
            'decree' => $decree,
            'can' => [
                'submit' => request()->user()->can('submit', $decree),
                'verify' => request()->user()->can('verify', $decree),
                'reject' => request()->user()->can('reject', $decree),
                'sign' => request()->user()->can('sign', $decree),
                'cancel' => request()->user()->can('cancel', $decree),
                'update' => request()->user()->can('update', $decree),
            ],
            'downloadUrl' => $decree->status === DecreeStatus::Issued && $decree->pdf_path
                ? URL::temporarySignedRoute(
                    'admin.decrees.download',
                    now()->addHour(),
                    ['decree' => $decree->id],
                )
                : null,
            'statuses' => collect(DecreeStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function submit(Decree $decree): RedirectResponse
    {
        $this->authorize('submit', $decree);

        return $this->transition($decree, DecreeStatus::Submitted, notifyAdmins: true);
    }

    public function verify(Decree $decree): RedirectResponse
    {
        $this->authorize('verify', $decree);

        return $this->transition($decree, DecreeStatus::Verified, notifyCreator: true);
    }

    public function reject(Request $request, Decree $decree): RedirectResponse
    {
        $this->authorize('reject', $decree);

        $data = $request->validate([
            'notes' => ['required', 'string', 'min:5'],
        ]);

        return $this->transition($decree, DecreeStatus::Rejected, $data['notes'], notifyCreator: true);
    }

    public function issue(Request $request, Decree $decree): RedirectResponse
    {
        $this->authorize('sign', $decree);

        try {
            $decree = app(DecreeWorkflowService::class)->transition($decree, DecreeStatus::Issued, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['action' => $e->getMessage()])->withInput();
        }

        // bekukan snapshot
        $decree = app(DecreeSnapshotBuilder::class)->freeze($decree);

        // QR verifikasi → data URI PNG
        $verificationUrl = rtrim(config('app.url'), '/').'/verifikasi/'.$decree->uuid;
        $qrDataUri = $this->qrDataUri($verificationUrl);

        // render PDF (dengan QR, tanpa tanda tangan basah di pratinjau)
        $pdf = app(PdfRenderer::class)->render($decree, qrDataUri: $qrDataUri);

        // tanda tangan kriptografis
        $certificate = app(CertificateManager::class)->activeCertificate();
        $signer = app(SelfSignedPkcs12Signer::class);

        if ($certificate) {
            $pdf = $signer->sign($this->writeTempPdf($pdf), [
                'p12_path' => Storage::disk('private')->path($certificate->p12_path),
                'certificate_password' => Crypt::decryptString($certificate->password_encrypted),
                'name' => Setting::get('foundation.chairman_name', ''),
                'reason' => 'Pengesahan SK',
                'location' => Setting::get('foundation.default_issued_place', 'Gondang'),
            ]);
        }

        $hash = hash('sha256', $pdf);

        $path = 'decrees/'.$decree->issued_date?->year.'/'.str_replace('/', '-', $decree->registration_number ?? $decree->uuid).'-'.$decree->employee->nigy.'.pdf';

        Storage::disk('private')->put($path, $pdf);

        $decree->update([
            'pdf_path' => $path,
            'pdf_hash' => $hash,
        ]);

        DecreeSignature::create([
            'decree_id' => $decree->id,
            'certificate_id' => $certificate?->id,
            'signer_name' => Setting::get('foundation.chairman_name', ''),
            'signed_at' => now(),
            'hash_sha256' => $hash,
            'signature_meta' => [
                'reason' => 'Pengesahan SK',
                'location' => Setting::get('foundation.default_issued_place', 'Gondang'),
                'certificate_fingerprint' => $certificate?->fingerprint,
                'qr_url' => $verificationUrl,
            ],
        ]);

        $this->notify($decree, 'issued', $decree->created_by, 'SK diterbitkan.');
        $this->notifyEmployee($decree);

        return back()->with('success', 'SK berhasil diterbitkan, ditandatangani digital, dan siap diverifikasi via QR.');
    }

    protected function qrDataUri(string $url): string
    {
        return QrCodePng::dataUri($url);
    }

    protected function writeTempPdf(string $pdf): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sk-unsigned').'.pdf';
        file_put_contents($path, $pdf);

        return $path;
    }

    public function cancel(Request $request, Decree $decree): RedirectResponse
    {
        $this->authorize('cancel', $decree);

        $data = $request->validate([
            'notes' => ['required', 'string', 'min:5'],
        ]);

        return $this->transition($decree, DecreeStatus::Cancelled, $data['notes'], notifyCreator: true);
    }

    public function previewPdf(Decree $decree): \Illuminate\Http\Response
    {
        $this->authorize('view', $decree);

        $pdf = app(PdfRenderer::class)->render($decree, draftPreview: true);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="pratinjau-draft.pdf"',
        ]);
    }

    public function downloadPdf(Decree $decree): StreamedResponse
    {
        $this->authorize('view', $decree);

        if ($decree->status !== DecreeStatus::Issued || ! $decree->pdf_path) {
            abort(404, __('common.not_found'));
        }

        $filename = $decree->decree_number
            ? str_replace(['/', '\\'], '-', $decree->decree_number).'.pdf'
            : 'sk-'.$decree->uuid.'.pdf';

        return Storage::disk('private')->download($decree->pdf_path, $filename);
    }

    protected function transition(Decree $decree, DecreeStatus $to, ?string $notes = null, bool $notifyCreator = false, bool $notifyAdmins = false): RedirectResponse
    {
        try {
            $decree = app(DecreeWorkflowService::class)->transition($decree, $to, request()->user(), $notes);
        } catch (RuntimeException $e) {
            return back()->withErrors(['action' => $e->getMessage()])->withInput();
        }

        if ($notifyCreator && $decree->created_by) {
            $this->notify($decree, $to->value, $decree->created_by, $notes);
        }

        if ($notifyAdmins) {
            User::query()
                ->where('role', UserRole::FoundationAdmin)
                ->get()
                ->each(fn (User $user) => $this->notify($decree, $to->value, $user->id, $notes));
        }

        return back()->with('success', 'Status SK: '.$decree->status->label().'.');
    }

    protected function notify(Decree $decree, string $toStatus, ?int $userId, ?string $notes): void
    {
        if ($userId) {
            User::find($userId)?->notify(new DecreeStatusChanged($decree, $decree->status->value, $toStatus, $notes));
        }
    }

    protected function notifyEmployee(Decree $decree): void
    {
        $decree->employee->user?->notify(new DecreeStatusChanged($decree, 'issued', 'issued', 'SK Anda telah diterbitkan.'));
    }

    /** @return Builder<WorkUnit> */
    protected function visibleWorkUnits(): Builder
    {
        $user = request()->user();

        if ($user->role === UserRole::UnitAdmin) {
            return WorkUnit::query()->where('id', $user->work_unit_id);
        }

        return WorkUnit::query()->where('is_active', true)->orderBy('code');
    }
}
