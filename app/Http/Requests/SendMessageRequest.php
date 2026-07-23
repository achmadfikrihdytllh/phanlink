<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:text,image,file'],
            'body' => ['required_if:type,text', 'nullable', 'string'],
            'reply_to_id' => ['nullable', 'integer', 'exists:messages,id'],
            'attachment' => ['required_if:type,image,file', 'nullable', 'file', 'max:10240'], // max 10MB
        ];
    }
}