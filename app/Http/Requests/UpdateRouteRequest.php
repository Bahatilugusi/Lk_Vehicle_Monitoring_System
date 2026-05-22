<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Get the route ID from the URL so we can
        // ignore this route's own name during unique check
        $routeId = $this->route('route')?->id;

        return [
            'name'           => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('routes', 'name')->ignore($routeId),
            ],
            'origin'         => ['sometimes', 'string', 'max:150'],
            'destination'    => ['sometimes', 'string', 'max:150'],
            'distance_km'    => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'estimated_time' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'description'    => ['sometimes', 'nullable', 'string', 'max:1000'],
            'status'         => ['sometimes', 'in:active,inactive'],
        ];
    }
}