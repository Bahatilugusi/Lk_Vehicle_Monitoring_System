<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'driver_id'       => ['required', 'integer', 'exists:drivers,id'],
            'vehicle_id'      => ['required', 'integer', 'exists:vehicles,id'],
            'route_id'        => ['nullable', 'integer', 'exists:routes,id'],
            'origin'          => ['required_without:route_id', 'string', 'max:255'],
            'destination'     => ['required_without:route_id', 'string', 'max:255'],
            'scheduled_start' => ['required', 'date', 'after:now'],
            'passengers_count'=> ['nullable', 'integer', 'min:1'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'driver_id.exists'        => 'The selected driver does not exist.',
            'vehicle_id.exists'       => 'The selected vehicle does not exist.',
            'route_id.exists'         => 'The selected route does not exist.',
            'scheduled_start.after'   => 'The scheduled time must be in the future.',
            'origin.required_without' => 'Origin is required when no route is selected.',
            'destination.required_without' => 'Destination is required when no route is selected.',
        ];
    }
}