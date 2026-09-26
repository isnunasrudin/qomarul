<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Exports\UserTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\UserImport;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\User\UserImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->with(['workUnit', 'employee:id,id,nigy,name'])
                ->orderBy('name')
                ->paginate(20)
                ->through(fn (User $user) => tap($user, fn () => $user->can_impersonate = request()->user()->can('impersonate', $user))),
            'roles' => $this->roleOptions(),
            'workUnits' => WorkUnit::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            // Ketua Yayasan boleh melihat daftar tetapi tidak boleh membuat
            // pengguna, jadi tombol tambah/impor/template perlu dijaga.
            'can' => [
                'create' => request()->user()->can('create', User::class),
            ],
        ]);
    }

    /** @return array<int, array{value: string, label: string}> */
    protected function roleOptions(): array
    {
        return collect(UserRole::cases())->map(fn (UserRole $role) => [
            'value' => $role->value,
            'label' => $role->label(),
        ])->all();
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', Password::min(8)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'work_unit_id' => ['nullable', 'integer', 'exists:work_units,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'must_change_password' => ['boolean'],
        ]);

        $data['role'] = UserRole::from($data['role']);
        $data['must_change_password'] = $request->boolean('must_change_password', true);

        if ($data['role'] === UserRole::UnitAdmin && ! $data['work_unit_id']) {
            return back()->withErrors(['work_unit_id' => __('validation.required')])->withInput();
        }

        if ($data['role'] === UserRole::Employee && ! $data['employee_id']) {
            return back()->withErrors(['employee_id' => __('validation.required')])->withInput();
        }

        User::create($data);

        return back()->with('success', __('common.created'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user)],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'work_unit_id' => ['nullable', 'integer', 'exists:work_units,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'is_active' => ['boolean'],
        ]);

        $data['role'] = UserRole::from($data['role']);
        $data['is_active'] = $request->boolean('is_active', true);

        $user->update($data);

        return back()->with('success', __('common.updated'));
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update([
            'password' => $data['password'],
            'must_change_password' => true,
        ]);

        return back()->with('success', __('common.updated'));
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', __('common.updated'));
    }

    public function toggleTwoFactor(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update(['two_factor_enabled' => ! $user->two_factor_enabled]);

        return back()->with('success', __('common.updated'));
    }

    public function resetTwoFactor(User $user): RedirectResponse
    {
        $this->authorize('resetTwoFactor', $user);

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
        ]);

        return back()->with('success', '2FA pengguna telah direset. Pengguna perlu mengaktifkannya kembali.');
    }

    /**
     * Unduh template Excel impor pengguna (sheet "Pengguna" + "Petunjuk").
     */
    public function importTemplate(): BinaryFileResponse
    {
        $this->authorize('create', User::class);

        return Excel::download(new UserTemplateExport, 'template-import-pengguna.xlsx');
    }

    /**
     * Baca berkas impor, validasi seluruh baris, lalu tampilkan pratinjau.
     * Tidak ada data yang disimpan pada langkah ini.
     */
    public function importPreview(Request $request): Response
    {
        $this->authorize('create', User::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

        // Hanya sheet pertama ("Pengguna") yang dibaca; sheet petunjuk diabaikan.
        $rows = Excel::toArray(new UserImport, $request->file('file'))[0] ?? [];

        $preview = app(UserImportService::class)->preview($rows);

        // Baris valid disimpan di sesi agar pratinjau tidak bisa disalahgunakan
        // untuk menyimpan data yang belum ditinjau.
        $request->session()->put('user_import.preview', $preview['valid']);

        return Inertia::render('Admin/Users/ImportPreview', [
            'preview' => [
                ...$preview,
                // Sandi tidak ikut dikirim ke peramban; dibaca kembali dari sesi
                // saat impor dikonfirmasi.
                'valid' => array_map(
                    fn (array $row) => Arr::except($row, ['kata_sandi']),
                    $preview['valid'],
                ),
            ],
            // Untuk label peran & satuan kerja di tabel pratinjau. Satker
            // ditampilkan seluruhnya (termasuk nonaktif) agar baris lama tetap terbaca.
            'roles' => $this->roleOptions(),
            'workUnits' => WorkUnit::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Simpan baris hasil pratinjau yang tersimpan di sesi.
     */
    public function importStore(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $rows = $request->session()->pull('user_import.preview', []);

        if (! $rows) {
            return back()->with('error', 'Sesi pratinjau impor sudah kedaluwarsa. Unggah ulang berkas Anda.');
        }

        $result = app(UserImportService::class)->import($rows);

        $credentials = collect($result['credentials'])
            ->map(fn (array $item) => $item['username'].' / '.$item['password'])
            ->implode(' · ');

        return redirect()->route('admin.users.index')->with(
            'success',
            "Impor selesai: {$result['saved']} pengguna dibuat. Sandi awal (wajib diganti saat masuk pertama): {$credentials}",
        );
    }
}
