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
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'whatsapp_opt_in' => ['sometimes', 'boolean'],
            'email_reminders_opt_in' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // If whatsapp_opt_in is true, whatsapp_number must be present
            if ($this->boolean('whatsapp_opt_in') && empty($this->input('whatsapp_number'))) {
                $validator->errors()->add('whatsapp_opt_in', 'WhatsApp number is required when opting in to WhatsApp notifications.');
            }
        });
    }
}
