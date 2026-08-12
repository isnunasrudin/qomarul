<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Employee::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nigy' => ['nullable', 'string', 'max:30', 'unique:employees,nigy'],
            'nik' => ['nullable', 'string', 'max:16', 'unique:employees,nik'],
            'nuptk' => ['nullable', 'string', 'max:16'],
            'nip' => ['nullable', 'string', 'max:18'],
            'title_prefix' => ['nullable', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:255'],
            'title_suffix' => ['nullable', 'string', 'max:100'],
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
    }
}
