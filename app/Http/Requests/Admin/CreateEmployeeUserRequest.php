<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmployeeUserRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
