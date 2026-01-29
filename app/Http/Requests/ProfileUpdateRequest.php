<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'has_bayah' => ['sometimes', 'boolean'],
            'level' => ['nullable', Rule::in(['beginner', 'intermediate', 'expert'])],
            'notification_email_enabled' => ['sometimes', 'boolean'],
            'notification_whatsapp_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
