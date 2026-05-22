<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:100', 'unique:routes,name'],
            'origin'         => ['required', 'string', 'max:150'],
            'destination'    => ['required', 'string', 'max:150'],
            'distance_km'    => ['nullable', 'numeric', 'min:0'],
            'estimated_time' => ['nullable', 'integer', 'min:1'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'status'         => ['sometimes', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'         => 'A route with this name already exists.',
            'estimated_time.min'  => 'Estimated time must be at least 1 minute.',
            'distance_km.min'     => 'Distance cannot be a negative number.',
        ];
    }
}