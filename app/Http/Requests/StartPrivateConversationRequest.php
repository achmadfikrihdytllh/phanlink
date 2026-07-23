<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartPrivateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id', 'different:' . $this->user()->id],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.different' => 'Tidak bisa memulai percakapan dengan diri sendiri.',
        ];
    }
}