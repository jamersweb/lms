<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHabitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|min:1',
            'description' => 'nullable|string|max:1000',
            'frequency_type' => 'required|in:daily,weekly,custom',
            'reminder_time' => 'nullable|date_format:H:i',
            'target_per_day' => 'nullable|integer|min:1|max:10',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure title is trimmed
        if ($this->has('title')) {
            $this->merge([
                'title' => trim($this->input('title')),
            ]);
        }
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for your habit.',
            'title.max' => 'The habit title cannot exceed 255 characters.',
            'frequency_type.required' => 'Please select how often you want to track this habit.',
            'frequency_type.in' => 'Please select a valid frequency type.',
            'target_per_day.min' => 'Target must be at least 1.',
            'target_per_day.max' => 'Target cannot exceed 10 per day.',
        ];
    }
}
