<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MessageCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message_id' => ['required', 'integer', 'exists:messages,id'],
            'status' => ['required', 'string', 'in:sent,delivered,failed'],
            'failure_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
