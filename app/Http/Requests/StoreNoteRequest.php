<?php

namespace App\Http\Requests;

use App\Models\Note;
use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Note::class);
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
            'content' => 'required|string|max:10000',
            'noteable_type' => 'nullable|in:App\\Models\\Lesson,App\\Models\\Course',
            'noteable_id' => 'nullable|integer|required_with:noteable_type',
            'pinned' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for your note.',
            'title.max' => 'The note title cannot exceed 255 characters.',
            'content.required' => 'Please provide content for your note.',
            'content.max' => 'The note content cannot exceed 10000 characters.',
            'noteable_id.required_with' => 'Please specify which item this note is related to.',
        ];
    }
}
