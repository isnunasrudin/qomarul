<?php

namespace App\Services\Employee;

use App\Enums\Gender;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Position;
use App\Models\WorkUnit;
use App\Services\Nigy\NigyService;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Impor massal GTK dari Excel dengan pratinjau validasi per baris
 * (PRD F3.9): baris tidak valid dilaporkan, tidak ada data separuh tersimpan.
 */
class EmployeeImportService
{
    public function __construct(private readonly NigyService $nigyService) {}

    /**
     * Validasi seluruh baris tanpa menyimpan apa pun.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{valid: array<int, array<string, mixed>>, errors: array<int, array<string, string>>, total: int}
     */
    public function preview(array $rows): array
    {
        $valid = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $normalized = $this->normalize($row);
            $validator = validator($normalized, $this->rules());

            if ($validator->fails()) {
                $errors[$line] = $validator->errors()->all();

                continue;
            }

            $valid[] = $this->map($normalized);
        }

        return [
            'valid' => $valid,
            'errors' => $errors,
            'total' => count($valid) + count($errors),
        ];
    }

    /**
     * Simpan seluruh baris valid dalam satu transaksi (all-or-nothing).
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function import(array $rows): int
    {
        $saved = 0;

        DB::transaction(function () use ($rows, &$saved): void {
            foreach ($rows as $row) {
                $row['nigy'] = $this->nigyService->generateFromData($row);

                Employee::create($row);

                $saved++;
            }
        });

        return $saved;
    }

    /** @return array<string, array<int, string>> */
    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tmt_yayasan' => ['required', 'date'],
            'kode_satker' => ['required', 'string', 'exists:work_units,code'],
            'kode_jabatan' => ['required', 'string', 'exists:positions,code'],
            'kode_status' => ['required', 'string', 'exists:employment_statuses,code'],
        ];
    }

    /** @param  array<string, mixed>  $row
     * @return array<string, mixed> */
    protected function normalize(array $row): array
    {
        return [
            'nik' => (string) ($row['nik'] ?? ''),
            'nuptk' => (string) ($row['nuptk'] ?? ''),
            'nip' => (string) ($row['nip'] ?? ''),
            'title_prefix' => (string) ($row['gelar_depan'] ?? ''),
            'nama' => (string) ($row['nama'] ?? ''),
            'title_suffix' => (string) ($row['gelar_belakang'] ?? ''),
            'jenis_kelamin' => (string) ($row['jenis_kelamin'] ?? ''),
            'tempat_lahir' => (string) ($row['tempat_lahir'] ?? ''),
            'tanggal_lahir' => $this->toDateString($row['tanggal_lahir'] ?? ''),
            'agama' => (string) ($row['agama'] ?? ''),
            'status_pernikahan' => (string) ($row['status_pernikahan'] ?? ''),
            'alamat' => (string) ($row['alamat'] ?? ''),
            'telepon' => (string) ($row['telepon'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'kode_satker' => (string) ($row['kode_satker'] ?? ''),
            'kode_jabatan' => (string) ($row['kode_jabatan'] ?? ''),
            'kode_status' => (string) ($row['kode_status'] ?? ''),
            'tmt_yayasan' => $this->toDateString($row['tmt_yayasan'] ?? ''),
            'tmt_satker' => $this->toDateString($row['tmt_satker'] ?? ''),
            'mapel' => (string) ($row['mapel'] ?? ''),
        ];
    }

    /** @param  array<string, mixed>  $data
     * @return array<string, mixed> */
    protected function map(array $data): array
    {
        $workUnit = WorkUnit::where('code', $data['kode_satker'])->value('id');
        $position = Position::where('code', $data['kode_jabatan'])->value('id');
        $status = EmploymentStatus::where('code', $data['kode_status'])->value('id');

        $gender = $data['jenis_kelamin'] === 'L' ? Gender::Male : Gender::Female;

        $religion = $this->parseReligion($data['agama']);

        return [
            'nik' => $data['nik'] ?: null,
            'nuptk' => $data['nuptk'] ?: null,
            'nip' => $data['nip'] ?: null,
            'title_prefix' => $data['title_prefix'] ?: null,
            'name' => $data['nama'],
            'title_suffix' => $data['title_suffix'] ?: null,
            'gender' => $gender->value,
            'birth_place' => $data['tempat_lahir'] ?: null,
            'birth_date' => $data['tanggal_lahir'] ?: null,
            'religion' => $religion,
            'marital_status' => $data['status_pernikahan'] ?: null,
            'address' => $data['alamat'] ?: null,
            'phone' => $data['telepon'] ?: null,
            'email' => $data['email'] ?: null,
            'work_unit_id' => $workUnit,
            'position_id' => $position,
            'employment_status_id' => $status,
            'foundation_start_date' => $data['tmt_yayasan'],
            'unit_start_date' => $data['tmt_satker'] ?: null,
            'subject' => $data['mapel'] ?: null,
            'is_active' => true,
        ];
    }

    protected function parseReligion(string $value): ?string
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return null;
        }

        $matches = [
            'islam' => 'islam', 'kristen' => 'kristen', 'katolik' => 'katolik',
            'hindu' => 'hindu', 'buddha' => 'buddha', 'konghucu' => 'konghucu',
            'protestan' => 'kristen',
        ];

        return $matches[$value] ?? null;
    }

    protected function toDateString(mixed $value): string
    {
        if (is_numeric($value) && $value > 20000000) {
            // Excel tanggal serial
            return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return '';
        }

        // d/m/Y atau d-m-Y dari input manual
        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $trimmed, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        return $trimmed;
    }

    /** @param  array<string, mixed>  $row */
    protected function isEmptyRow(array $row): bool
    {
        return collect($row)->filter(fn ($value) => ! in_array(trim((string) $value), ['', null], true))->isEmpty();
    }
}
