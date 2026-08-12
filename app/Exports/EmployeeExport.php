<?php

namespace App\Exports;

use App\Enums\Gender;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** @implements WithMapping<Employee> */
class EmployeeExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        private readonly Request $request,
        private readonly bool $templateOnly = false,
    ) {}

    /** @return Builder<Employee> */
    public function query(): Builder
    {
        if ($this->templateOnly) {
            return Employee::query()->whereRaw('1 = 0');
        }

        return Employee::query()
            ->with(['workUnit', 'position', 'employmentStatus'])
            ->when($this->request->filled('q'), function ($query) {
                $q = $this->request->string('q')->trim();

                $query->where(fn ($query) => $query
                    ->where('nigy', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%")
                    ->orWhere('nuptk', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%"));
            })
            ->when($this->request->filled('work_unit_id'), fn ($query) => $query->where('work_unit_id', $this->request->integer('work_unit_id')))
            ->when($this->request->filled('position_id'), fn ($query) => $query->where('position_id', $this->request->integer('position_id')))
            ->when($this->request->filled('employment_status_id'), fn ($query) => $query->where('employment_status_id', $this->request->integer('employment_status_id')))
            ->when($this->request->filled('is_active'), fn ($query) => $query->where('is_active', $this->request->boolean('is_active')))
            ->orderBy('name');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'nigy', 'nik', 'nuptk', 'nip', 'gelar_depan', 'nama', 'gelar_belakang',
            'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'status_pernikahan',
            'alamat', 'telepon', 'email', 'kode_satker', 'kode_jabatan', 'kode_status',
            'tmt_yayasan', 'tmt_satker', 'mapel',
        ];
    }

    /** @param  Employee  $employee
     * @return array<int, mixed> */
    public function map($employee): array
    {
        return [
            $employee->nigy,
            $employee->nik,
            $employee->nuptk,
            $employee->nip,
            $employee->title_prefix,
            $employee->name,
            $employee->title_suffix,
            $employee->gender === Gender::Male ? 'L' : 'P',
            $employee->birth_place,
            $employee->birth_date?->format('Y-m-d'),
            $employee->religion,
            $employee->marital_status,
            $employee->address,
            $employee->phone,
            $employee->email,
            $employee->workUnit?->code,
            $employee->position?->code,
            $employee->employmentStatus?->code,
            $employee->foundation_start_date?->format('Y-m-d'),
            $employee->unit_start_date?->format('Y-m-d'),
            $employee->subject,
        ];
    }
}
