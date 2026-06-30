<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'to' => trim((string) ($this->input('to') ?? '')),
            'content' => trim((string) ($this->input('content') ?? '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Basic E.164-ish phone format: optional leading +, 7-15 digits.
            'to' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'content' => ['required', 'string', 'max:520'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to.regex' => 'The "to" field must be a valid phone number (7-15 digits, optional leading +).',
        ];
    }
}