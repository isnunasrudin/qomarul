<?php

namespace App\Http\Controllers\Portal;

use App\Enums\DecreeStatus;
use App\Enums\DocumentCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UpdateOwnProfileRequest;
use App\Models\Decree;
use App\Models\DecreeType;
use App\Models\Document;
use App\Models\Employee;
use App\Services\Employee\ProfileCompletenessService;
use App\Support\PhotoProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalController extends Controller
{
    public function home(): Response
    {
        $employee = $this->ownEmployee();

        $employee->load([
            'workUnit:id,name',
            'position:id,name',
            'employmentStatus:id,name',
            'educations',
            'documents',
        ]);

        $employee->documents->each(function (Document $document): void {
            $document->signed_url = URL::temporarySignedRoute(
                'portal.documents.download',
                now()->addHour(),
                ['document' => $document->id],
            );
        });

        $recentDecrees = Decree::query()
            ->where('employee_id', $employee->id)
            ->where('status', DecreeStatus::Issued)
            ->where(function ($query) {
                $query->where('is_legacy', false)
                    ->orWhere(function ($query) {
                        $query->where('is_legacy', true)->whereNotNull('legacy_verified_at');
                    });
            })
            ->latest('signed_at')
            ->limit(5)
            ->get(['id', 'decree_number', 'decree_type_id', 'effective_date', 'issued_date', 'status', 'is_legacy', 'uuid'])
            ->load('decreeType:id,name');

        $recentDecrees->each(function (Decree $decree): void {
            if (! $decree->is_legacy) {
                $decree->download_url = URL::temporarySignedRoute(
                    'portal.decrees.download',
                    now()->addHour(),
                    ['decree' => $decree->id],
                );
            }
        });

        return Inertia::render('Portal/Home', [
            'employee' => $employee,
            'completeness' => app(ProfileCompletenessService::class)->evaluate($employee),
            'recentDecrees' => $recentDecrees,
            'activeDuties' => $employee->additionalDuties()
                ->where('status', 'active')
                ->with('additionalDuty:id,name')
                ->get(),
            'documentCategories' => collect(DocumentCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function updateProfile(UpdateOwnProfileRequest $request): RedirectResponse
    {
        $employee = $this->ownEmployee();

        $data = $request->allowedData();

        if ($request->hasFile('photo')) {
            try {
                $data['photo_path'] = app(PhotoProcessor::class)
                    ->process($request->file('photo'), 'private', 'photos');
            } catch (\RuntimeException $e) {
                return back()->withErrors(['photo' => $e->getMessage()])->withInput();
            }
        }

        $employee->update($data);

        return back()->with('success', __('common.updated'));
    }

    public function uploadDocument(Request $request): RedirectResponse
    {
        $employee = $this->ownEmployee();

        $data = $request->validate([
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'name' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $file = $request->file('file');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($file->getContent());

        if (! in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            return back()->withErrors(['file' => 'Berkas harus berupa PDF, JPG, atau PNG.'])->withInput();
        }

        $path = $file->store('documents/'.$employee->id, 'private');

        Document::create([
            'employee_id' => $employee->id,
            'category' => $data['category'],
            'name' => $data['name'] ?? $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $mime,
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', __('common.upload'));
    }

    public function uploadLegacy(Request $request): RedirectResponse
    {
        $employee = $this->ownEmployee();

        $data = $request->validate([
            'file' => ['required', 'file', 'max:5120'],
            'decree_number' => ['nullable', 'string', 'max:255'],
            'effective_date' => ['nullable', 'date'],
            'issued_date' => ['nullable', 'date'],
            'academic_year' => ['nullable', 'string', 'max:9'],
        ]);

        $file = $request->file('file');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($file->getContent());

        if ($mime !== 'application/pdf') {
            return back()->withErrors(['file' => 'Arsip SK harus berupa PDF.'])->withInput();
        }

        $path = $file->store('legacy/'.$employee->id, 'private');

        Decree::create([
            'uuid' => (string) Str::uuid(),
            'decree_type_id' => $this->guessLegacyType(),
            'employee_id' => $employee->id,
            'work_unit_id' => $employee->work_unit_id,
            'decree_number' => $data['decree_number'] ?? null,
            'effective_date' => $data['effective_date'] ?? null,
            'issued_date' => $data['issued_date'] ?? null,
            'academic_year' => $data['academic_year'] ?? null,
            'status' => DecreeStatus::Issued,
            'pdf_path' => $path,
            'is_legacy' => true,
            'legacy_verified_at' => null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Arsip SK diterima dan menunggu verifikasi admin.');
    }

    public function downloadDocument(Request $request, Document $document): StreamedResponse
    {
        $employee = $this->ownEmployee();

        if ($document->employee_id !== $employee->id) {
            abort(403, __('common.forbidden'));
        }

        return Storage::disk('private')->download($document->path, $document->name);
    }

    public function downloadDecree(Request $request, Decree $decree): StreamedResponse
    {
        $employee = $this->ownEmployee();

        if ($decree->employee_id !== $employee->id) {
            abort(403, __('common.forbidden'));
        }

        if ($decree->status !== DecreeStatus::Issued) {
            abort(404, __('common.not_found'));
        }

        $path = $decree->pdf_path;

        if (! $path) {
            abort(404, __('common.not_found'));
        }

        $filename = $decree->decree_number
            ? str_replace(['/', '\\'], '-', $decree->decree_number).'.pdf'
            : 'sk-'.$decree->uuid.'.pdf';

        return Storage::disk('private')->download($path, $filename);
    }

    protected function ownEmployee(): Employee
    {
        $employee = request()->user()?->employee;

        if (! $employee) {
            abort(403, __('common.forbidden'));
        }

        return $employee;
    }

    protected function guessLegacyType(): int
    {
        $typeId = DecreeType::query()
            ->where('code', 'SK-PPT')
            ->value('id')
            ?? DecreeType::query()->value('id');

        if (! $typeId) {
            abort(422, 'Jenis SK belum terkonfigurasi. Hubungi Admin Yayasan.');
        }

        return (int) $typeId;
    }
}
