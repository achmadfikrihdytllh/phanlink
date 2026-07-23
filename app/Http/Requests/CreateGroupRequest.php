<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'string'],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['integer', 'exists:users,id', 'different:' . $this->user()->id],
        ];
    }

    public function messages(): array
    {
        return [
            'participant_ids.min' => 'Group harus punya minimal 1 anggota selain kamu.',
            'participant_ids.*.different' => 'Tidak perlu menambahkan diri sendiri sebagai anggota.',
        ];
    }
}