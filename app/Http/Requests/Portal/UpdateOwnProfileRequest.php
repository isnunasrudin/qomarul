<?php

namespace App\Http\Requests\Portal;

use App\Enums\MaritalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sunting data pribadi di portal (PRD F3.15/F3.16).
 *
 * Daftar putih ketat: field administratif (nigy, work_unit_id, position_id,
 * employment_status_id, TMT, dll.) TIDAK diterima — ditegakkan di lapisan
 * server, bukan sekadar disembunyikan di UI.
 */
class UpdateOwnProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->value === 'employee';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
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
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Hanya field dalam daftar putih yang dipertahankan; sisanya dibuang.
     *
     * @return array<string, mixed>
     */
    public function allowedData(): array
    {
        return $this->safe()->only([
            'marital_status', 'mother_name', 'address', 'rt', 'rw', 'village',
            'district', 'regency', 'province', 'postal_code', 'phone', 'email',
            'bank_name', 'bank_account_number',
        ]);
    }
}
