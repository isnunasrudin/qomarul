<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentCategory;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\PositionGroup;
use App\Enums\Religion;
use App\Enums\UserRole;
use App\Exports\EmployeeExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateEmployeeUserRequest;
use App\Http\Requests\Admin\EmployeeStoreRequest;
use App\Http\Requests\Admin\EmployeeUpdateRequest;
use App\Imports\EmployeeImport;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Employee\EmployeeImportService;
use App\Services\Employee\ProfileCompletenessService;
use App\Services\Nigy\NigyService;
use App\Support\PhotoProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::query()
            ->with(['workUnit:id,code,name', 'position:id,name', 'employmentStatus:id,name', 'user:id,id,employee_id,role,work_unit_id,is_active'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $q = $request->string('q')->trim();

                    $query->where('nigy', 'like', "%{$q}%")
                        ->orWhere('nik', 'like', "%{$q}%")
                        ->orWhere('nuptk', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->when($request->filled('work_unit_id'), fn ($query) => $query->where('work_unit_id', $request->integer('work_unit_id')))
            ->when($request->filled('position_id'), fn ($query) => $query->where('position_id', $request->integer('position_id')))
            ->when($request->filled('employment_status_id'), fn ($query) => $query->where('employment_status_id', $request->integer('employment_status_id')))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $employees = $employees->through(function (Employee $employee): Employee {
            $employee->can_impersonate = (bool) ($employee->user && request()->user()->can('impersonate', $employee->user));

            return $employee;
        });

        return Inertia::render('Admin/Employees/Index', [
            'employees' => $employees,
            'filters' => $request->only(['q', 'work_unit_id', 'position_id', 'employment_status_id', 'is_active']),
            'workUnits' => $this->visibleWorkUnits()->get(['id', 'code', 'name']),
            'positions' => Position::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employmentStatuses' => EmploymentStatus::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Employee::class);

        return Inertia::render('Admin/Employees/Form', [
            'employee' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(EmployeeStoreRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['nigy', 'photo']);

        if ($request->hasFile('photo')) {
            try {
                $data['photo_path'] = app(PhotoProcessor::class)
                    ->process($request->file('photo'), 'private', 'photos');
            } catch (\RuntimeException $e) {
                return back()->withErrors(['photo' => $e->getMessage()])->withInput();
            }
        }

        // NIGY: manual bila foundation_admin mengirimkan (F3.2d), otomatis bila tidak (F3.2a)
        $data['nigy'] = $request->filled('nigy')
            ? (string) $request->string('nigy')
            : app(NigyService::class)->generateFromData($data);

        $employee = Employee::create($data);

        return redirect()->route('admin.employees.show', $employee)->with('success', __('common.created'));
    }

    public function show(Employee $employee): Response
    {
        $this->authorize('view', $employee);

        $employee->load([
            'workUnit:id,code,name',
            'position:id,name,group',
            'employmentStatus:id,name',
            'educations',
            'documents',
            'user:id,id,username,email,is_active',
        ]);

        $employee->documents->each(function (Document $document): void {
            $document->signed_url = URL::temporarySignedRoute(
                'admin.documents.download',
                now()->addHour(),
                ['document' => $document->id],
            );

            $document->uploaded_by_employee = $document->uploaded_by
                ? User::query()
                    ->where('id', $document->uploaded_by)
                    ->where('role', UserRole::Employee)
                    ->exists()
                : false;
        });

        return Inertia::render('Admin/Employees/Show', [
            'employee' => $employee,
            'completeness' => app(ProfileCompletenessService::class)->evaluate($employee),
            'can' => [
                'update' => request()->user()->can('update', $employee),
                'updateNigy' => request()->user()->can('updateNigy', $employee),
                'delete' => request()->user()->can('delete', $employee),
                'createUser' => request()->user()->can('createUser', $employee),
            ],
            'documentCategories' => collect(DocumentCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'educationLevels' => collect(EducationLevel::cases())->map(fn ($l) => ['value' => $l->value, 'label' => $l->label()]),
        ]);
    }

    public function edit(Employee $employee): Response
    {
        $this->authorize('update', $employee);

        $employee->load(['workUnit', 'educations']);

        return Inertia::render('Admin/Employees/Form', [
            'employee' => $employee,
            'can' => [
                'updateNigy' => request()->user()->can('updateNigy', $employee),
                'delete' => request()->user()->can('delete', $employee),
            ],
            'nigyLocked' => app(NigyService::class)->isLocked($employee),
            ...$this->formOptions(),
        ]);
    }

    public function update(EmployeeUpdateRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->safe()->except(['nigy', 'nigy_reason', 'photo']);

        if ($request->hasFile('photo')) {
            try {
                if ($employee->photo_path) {
                    Storage::disk('private')->delete($employee->photo_path);
                }

                $data['photo_path'] = app(PhotoProcessor::class)
                    ->process($request->file('photo'), 'private', 'photos');
            } catch (\RuntimeException $e) {
                return back()->withErrors(['photo' => $e->getMessage()])->withInput();
            }
        }

        // NIGY (F3.2d–F3.2g)
        if ($request->user()->can('updateNigy', $employee) && $request->filled('nigy')) {
            $nigy = (string) $request->string('nigy');

            if ($nigy !== $employee->nigy) {
                if (app(NigyService::class)->isLocked($employee)) {
                    return back()->withErrors([
                        'nigy' => $this->lockedNigyMessage($employee),
                    ])->withInput();
                }

                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'nigy_override',
                    'auditable_type' => Employee::class,
                    'auditable_id' => $employee->id,
                    'old_values' => ['nigy' => $employee->nigy],
                    'new_values' => ['nigy' => (string) $nigy, 'reason' => $request->string('nigy_reason')],
                ]);

                $data['nigy'] = (string) $nigy;
            }
        }

        $employee->update($data);

        return back()->with('success', __('common.updated'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $employee->delete();

        return redirect()->route('admin.employees.index')->with('success', __('common.deleted'));
    }

    public function importPreview(Request $request): Response
    {
        $this->authorize('create', Employee::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

        $rows = Excel::toArray(new EmployeeImport, $request->file('file'))[0];

        $preview = app(EmployeeImportService::class)->preview($rows);

        $request->session()->put('import.preview', $preview['valid']);
        $request->session()->put('import.create_users', $request->boolean('create_users'));

        return Inertia::render('Admin/Employees/ImportPreview', [
            'preview' => $preview,
            'createUsers' => $request->boolean('create_users'),
        ]);
    }

    public function importStore(Request $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        $rows = $request->session()->pull('import.preview', []);
        $withUsers = (bool) $request->session()->pull('import.create_users', false);

        if (! $rows) {
            return back()->with('error', 'Sesi pratinjau impor sudah kedaluwarsa. Unggah ulang berkas Anda.');
        }

        $result = app(EmployeeImportService::class)->import($rows, $withUsers);

        $message = "Impor selesai: {$result['saved']} GTK tersimpan.";

        if ($result['users']) {
            $credentials = collect($result['users'])
                ->map(fn ($u) => $u['username'].' / '.$u['password'])
                ->implode(' · ');

            $message .= ' Akun pengguna dibuat (username / sandi sementara): '.$credentials;
        }

        return redirect()->route('admin.employees.index')
            ->with('success', $message);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Employee::class);

        $template = $request->boolean('template');

        return Excel::download(
            new EmployeeExport($request, $template),
            $template ? 'template-import-gtk.xlsx' : 'daftar-gtk.xlsx',
        );
    }

    public function createUser(CreateEmployeeUserRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorize('createUser', $employee);

        $username = $request->string('username')->trim()->toString();
        $email = $request->string('email')->trim()->toString();
        $password = $request->filled('password') ? $request->string('password')->toString() : Str::random(12);

        $user = User::create([
            'name' => $employee->name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Employee,
            'employee_id' => $employee->id,
            'work_unit_id' => $employee->work_unit_id,
            'is_active' => true,
            'must_change_password' => ! $request->filled('password'),
        ]);

        return back()->with('success',
            "Akun pengguna dibuat — username: {$user->username}".($request->filled('password') ? '' : ", sandi sementara: {$password} (wajib diganti saat masuk pertama)"));
    }

    protected function lockedNigyMessage(Employee $employee): string
    {
        $numbers = app(NigyService::class)->lockingDecreeNumbers($employee);
        $joined = implode(', ', $numbers);

        return $joined
            ? "NIGY tidak dapat diubah karena sudah tercetak pada SK: {$joined}."
            : 'NIGY tidak dapat diubah karena sudah tercetak pada SK yang terbit.';
    }

    /** @return array<string, mixed> */
    protected function formOptions(): array
    {
        return [
            'workUnits' => $this->visibleWorkUnits()->get(['id', 'code', 'name']),
            'positions' => Position::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'group']),
            'employmentStatuses' => EmploymentStatus::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'genders' => collect(Gender::cases())->map(fn ($g) => ['value' => $g->value, 'label' => $g->label()]),
            'religions' => collect(Religion::cases())->map(fn ($r) => ['value' => $r->value, 'label' => $r->label()]),
            'maritalStatuses' => collect(MaritalStatus::cases())->map(fn ($m) => ['value' => $m->value, 'label' => $m->label()]),
            'positionGroups' => collect(PositionGroup::cases())->map(fn ($p) => ['value' => $p->value, 'label' => $p->label()]),
            'documentCategories' => collect(DocumentCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ];
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
