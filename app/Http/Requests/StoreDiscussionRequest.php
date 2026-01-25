<?php

namespace App\Http\Requests;

use App\Models\Discussion;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiscussionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user can create discussions
        if (!$this->user()->can('create', Discussion::class)) {
            return false;
        }
        
        // Check if user is enrolled in the course
        $course = $this->route('course');
        
        return $course && $this->user()->isEnrolledIn($course->id);
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
            'body' => 'required|string|max:5000',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for your discussion.',
            'title.max' => 'The discussion title cannot exceed 255 characters.',
            'body.required' => 'Please provide content for your discussion.',
            'body.max' => 'The discussion content cannot exceed 5000 characters.',
        ];
    }
}
