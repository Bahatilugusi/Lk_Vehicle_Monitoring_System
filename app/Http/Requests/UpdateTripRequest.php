<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id'        => ['sometimes', 'nullable', 'integer', 'exists:routes,id'],
            'origin'          => ['sometimes', 'string', 'max:255'],
            'destination'     => ['sometimes', 'string', 'max:255'],
            'scheduled_start' => ['sometimes', 'date', 'after:now'],
            'passengers_count'=> ['sometimes', 'nullable', 'integer', 'min:1'],
            'notes'           => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
