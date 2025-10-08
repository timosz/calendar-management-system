<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ToggleDayRequest extends FormRequest
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
            'day_of_week' => 'required|integer|between:0,6',
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
            'day_of_week.required' => 'Day of week is required.',
            'day_of_week.integer' => 'Day of week must be a valid number.',
            'day_of_week.between' => 'Day of week must be between 0 (Sunday) and 6 (Saturday).',
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
            'day_of_week' => 'day of week',
        ];
    }

    /**
     * Get the validated day of week.
     *
     * @return int
     */
    public function getDayOfWeek(): int
    {
        return $this->validated()['day_of_week'];
    }
}
