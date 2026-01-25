<?php

namespace App\Http\Requests;

use App\Models\JournalEntry;
use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', JournalEntry::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|max:5000',
            'mood' => 'nullable|in:great,good,neutral,bad,terrible',
            'entry_date' => 'nullable|date|before_or_equal:today',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Please write something in your journal entry.',
            'content.max' => 'The journal entry cannot exceed 5000 characters.',
            'mood.in' => 'Please select a valid mood.',
            'entry_date.before_or_equal' => 'The entry date cannot be in the future.',
        ];
    }
}
