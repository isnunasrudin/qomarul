<?php

namespace App\Services\User;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Impor massal pengguna dari Excel dengan pratinjau validasi per baris.
 *
 * Mengikuti pola EmployeeImportService: pratinjau tidak menyimpan apa pun,
 * dan penyimpanan berjalan dalam satu transaksi (all-or-nothing).
 */
class UserImportService
{
    /**
     * Validasi seluruh baris tanpa menyimpan apa pun.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{valid: array<int, array<string, mixed>>, errors: array<int, array<int, string>>, total: int}
     */
    public function preview(array $rows): array
    {
        $valid = [];
        $errors = [];
        $seen = ['username' => [], 'email' => []];

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $normalized = $this->normalize($row);
            $messages = validator($normalized, $this->rules())->errors()->all();

            // Aturan `unique` hanya memeriksa basis data, bukan baris lain di
            // berkas yang sama — duplikat di dalam berkas dicek terpisah.
            foreach (['username' => 'Username', 'email' => 'Email'] as $field => $label) {
                $value = mb_strtolower($normalized[$field]);

                if ($value === '') {
                    continue;
                }

                if (isset($seen[$field][$value])) {
                    $messages[] = "{$label} ganda di dalam berkas (sama dengan baris {$seen[$field][$value]}).";
                }
            }

            // GTK yang sudah punya akun tidak boleh dibuatkan akun kedua.
            if ($normalized['peran'] === UserRole::Employee->value && filled($normalized['nigy'])) {
                $employee = Employee::query()->where('nigy', $normalized['nigy'])->first();

                if ($employee && $employee->user()->exists()) {
                    $messages[] = "GTK dengan NIGY {$employee->nigy} sudah memiliki akun pengguna.";
                }
            }

            if ($messages !== []) {
                $errors[$line] = $messages;

                continue;
            }

            $seen['username'][mb_strtolower($normalized['username'])] = $line;
            $seen['email'][mb_strtolower($normalized['email'])] = $line;

            $valid[] = $this->map($normalized);
        }

        return [
            'valid' => $valid,
            'errors' => $errors,
            'total' => count($valid) + count($errors),
        ];
    }

    /**
     * Simpan seluruh baris valid dalam satu transaksi.
     *
     * @param  array<int, array<string, mixed>>  $rows  hasil preview()['valid']
     * @return array{saved: int, credentials: array<int, array{username: string, password: string, role: string}>}
     */
    public function import(array $rows): array
    {
        $saved = 0;
        $credentials = [];

        DB::transaction(function () use ($rows, &$saved, &$credentials): void {
            foreach ($rows as $row) {
                $password = blank($row['kata_sandi'] ?? null)
                    ? Str::password(12, symbols: true)
                    : (string) $row['kata_sandi'];

                User::create([
                    'name' => $row['name'],
                    'username' => $row['username'],
                    'email' => $row['email'],
                    'password' => Hash::make($password),
                    'role' => UserRole::from($row['role']),
                    'work_unit_id' => $row['work_unit_id'],
                    'employee_id' => $row['employee_id'],
                    'is_active' => $row['is_active'],
                    // Sama seperti pembuatan akun lain: sandi awal wajib diganti.
                    'must_change_password' => true,
                ]);

                $saved++;

                $credentials[] = [
                    'username' => $row['username'],
                    'password' => $password,
                    'role' => $row['role'],
                ];
            }
        });

        return ['saved' => $saved, 'credentials' => $credentials];
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'peran' => ['required', Rule::in(UserRole::values())],
            'kode_satker' => [
                'nullable', 'string', 'exists:work_units,code',
                'required_if:peran,'.UserRole::UnitAdmin->value,
            ],
            'nigy' => [
                'nullable', 'string', 'exists:employees,nigy',
                'required_if:peran,'.UserRole::Employee->value,
            ],
            'kata_sandi' => ['nullable', 'string', 'min:8'],
            'aktif' => ['boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalize(array $row): array
    {
        return [
            'nama' => trim((string) ($row['nama'] ?? '')),
            'username' => trim((string) ($row['username'] ?? '')),
            'email' => mb_strtolower(trim((string) ($row['email'] ?? ''))),
            'peran' => $this->parseRole($row['peran'] ?? ''),
            // Kosong → null, supaya aturan exists/unique dilewati oleh `nullable`
            // (string kosong tetap diuji dan akan gagal exists).
            'kode_satker' => trim((string) ($row['kode_satker'] ?? '')) ?: null,
            'nigy' => trim((string) ($row['nigy'] ?? '')) ?: null,
            // Kosong dinormalkan jadi null supaya aturan min:8 tidak menolak
            // kolom yang memang sengaja dikosongkan.
            'kata_sandi' => trim((string) ($row['kata_sandi'] ?? '')) ?: null,
            'aktif' => $this->parseBoolean($row['aktif'] ?? ''),
        ];
    }

    /**
     * Terima nilai enum maupun label Indonesianya (mis. "unit_admin" atau
     * "Admin Satuan Kerja"). Nilai tak dikenal dikembalikan apa adanya agar
     * aturan Rule::in yang menolaknya.
     */
    protected function parseRole(mixed $value): string
    {
        $needle = mb_strtolower(trim((string) $value));

        foreach (UserRole::cases() as $role) {
            if ($needle === $role->value || $needle === mb_strtolower($role->label())) {
                return $role->value;
            }
        }

        return $needle;
    }

    /** Kosong dianggap aktif, seperti nilai bawaan kolom `is_active`. */
    protected function parseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $needle = mb_strtolower(trim((string) $value));

        if ($needle === '') {
            return true;
        }

        return in_array($needle, ['1', 'ya', 'y', 'true', 'aktif', 'yes'], true);
    }

    /**
     * Susun atribut pengguna dari baris yang sudah tervalidasi.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function map(array $row): array
    {
        $workUnit = filled($row['kode_satker'])
            ? WorkUnit::query()->where('code', $row['kode_satker'])->first(['id', 'name'])
            : null;

        $employee = null;

        if ($row['peran'] === UserRole::Employee->value && filled($row['nigy'])) {
            $employee = Employee::query()->where('nigy', $row['nigy'])->first(['id', 'nigy', 'work_unit_id']);
        }

        return [
            'name' => $row['nama'],
            'username' => $row['username'],
            'email' => $row['email'],
            'role' => $row['peran'],
            // Satker mengikuti GTK-nya bila peran employee.
            'work_unit_id' => $employee?->work_unit_id ?? $workUnit?->id,
            'employee_id' => $employee?->id,
            'is_active' => $row['aktif'],
            // Dipakai saat import; tidak ditampilkan di halaman pratinjau.
            'kata_sandi' => $row['kata_sandi'],
            // Hanya untuk tampilan pratinjau — tidak ikut disimpan.
            'employee_nigy' => $employee?->nigy ?? $row['nigy'],
        ];
    }

    /** @param  array<string, mixed>  $row */
    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
