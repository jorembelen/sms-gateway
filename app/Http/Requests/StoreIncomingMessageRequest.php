<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomingMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sender' => trim((string) ($this->input('sender') ?? '')),
            'body'   => trim((string) ($this->input('body') ?? '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'device_public_id' => ['required', 'string', 'exists:devices,public_id'],
            'sender'           => ['required', 'string', 'max:30'],
            'body'             => ['required', 'string'],
            'received_at'      => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'device_public_id.exists' => 'The specified device is not registered.',
        ];
    }
}
