<?php

namespace App\Http\Requests;

use App\Models\Habit;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHabitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $habit = $this->route('habit');
        
        return $habit && $this->user()->can('update', $habit);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'frequency_type' => 'required|in:daily,weekly,monthly',
            'reminder_time' => 'nullable|date_format:H:i',
            'target_per_day' => 'nullable|integer|min:1|max:10',
            'is_active' => 'boolean',
        ];
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
