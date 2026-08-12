<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('employee'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $employee = $this->route('employee');

        $rules = [
            'nik' => ['nullable', 'string', 'max:16', Rule::unique('employees', 'nik')->ignore($employee)],
            'nuptk' => ['nullable', 'string', 'max:16'],
            'nip' => ['nullable', 'string', 'max:18'],
            'title_prefix' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'title_suffix' => ['nullable', 'string', 'max:30'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'religion' => ['nullable', Rule::enum(Religion::class)],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'rt' => ['nullable', 'string', 'max:3'],
            'rw' => ['nullable', 'string', 'max:3'],
            'village' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'regency' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:5'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:20'],
            'bank_account_number' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'blood_type' => ['nullable', 'string', 'max:5'],
            'work_unit_id' => ['required', 'integer', 'exists:work_units,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'employment_status_id' => ['required', 'integer', 'exists:employment_statuses,id'],
            'foundation_start_date' => ['required', 'date'],
            'unit_start_date' => ['nullable', 'date'],
            'subject' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];

        // NIGY: hanya foundation_admin dapat menimpa (F3.2d), dan terkunci
        // bila sudah tercetak pada SK issued (F3.2f) — ditegakkan di
        // EmployeeController agar pesan blokir dapat memuat nomor SK.
        if ($this->user()->can('updateNigy', $employee)) {
            $rules['nigy'] = ['nullable', 'string', 'max:30', Rule::unique('employees', 'nigy')->ignore($employee)];
            $rules['nigy_reason'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
