<?php

namespace App\Http\Requests;

use App\Models\DiscussionReply;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiscussionReplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $discussion = $this->route('discussion');
        
        // Check if discussion is open
        if ($discussion && $discussion->status === 'closed') {
            return false;
        }
        
        return $this->user()->can('create', DiscussionReply::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => 'required|string|max:5000',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Please provide content for your reply.',
            'body.max' => 'The reply content cannot exceed 5000 characters.',
        ];
    }
}
