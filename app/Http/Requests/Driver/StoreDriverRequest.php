<?php

// app/Http/Requests/Driver/StoreDriverRequest.php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'dispatcher']);
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:100'],
            'email'             => ['required', 'email', 'unique:users,email'],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],

            'license_number'    => ['required', 'string', 'max:50', 'unique:drivers,license_number'],
            'license_class'     => ['required', 'string', 'max:10'],
            'license_expiry'    => ['required', 'date', 'after:today'],
            'years_experience'  => ['required', 'integer', 'min:0', 'max:60'],

            'emergency_contact' => ['nullable', 'string', 'max:100'],
            'emergency_phone'   => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'          => 'An account with this email already exists.',
            'license_number.unique' => 'This license number is already registered.',
            'license_expiry.after'  => 'License expiry must be a future date.',
            'password.confirmed'    => 'Password confirmation does not match.',
        ];
    }
}