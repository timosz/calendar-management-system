<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilitiesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'availabilities' => 'required|array',
            'availabilities.*.day_of_week' => 'required|integer|between:0,6',
            'availabilities.*.is_active' => 'required|boolean',
            'availabilities.*.start_time' => [
                'nullable',
                'date_format:H:i',
                'required_if:availabilities.*.is_active,true',
            ],
            'availabilities.*.end_time' => [
                'nullable',
                'date_format:H:i',
                'required_if:availabilities.*.is_active,true',
                'after:availabilities.*.start_time',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'availabilities.*.start_time.required_if' => 'Start time is required when day is active.',
            'availabilities.*.end_time.required_if' => 'End time is required when day is active.',
            'availabilities.*.end_time.after' => 'End time must be after start time.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'availabilities.*.day_of_week' => 'day of week',
            'availabilities.*.is_active' => 'active status',
            'availabilities.*.start_time' => 'start time',
            'availabilities.*.end_time' => 'end time',
        ];
    }
}
