<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route sudah di balik middleware auth
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'relationship' => ['required', 'in:superior,peer,self'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama penilai wajib diisi.',
            'name.max' => 'Nama penilai maksimal 255 karakter.',
            'email.required' => 'Email penilai wajib diisi.',
            'email.email' => 'Format email penilai tidak valid.',
            'email.max' => 'Email penilai maksimal 255 karakter.',
            'relationship.required' => 'Hubungan penilai wajib dipilih.',
            'relationship.in' => 'Hubungan penilai harus Atasan, Sejawat, atau Diri Sendiri.',
        ];
    }
}
