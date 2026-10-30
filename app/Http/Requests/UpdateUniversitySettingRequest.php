<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUniversitySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route sudah di balik middleware auth + role:admin, policy menjaga aksi langsung
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'uni_name' => ['required', 'string', 'max:255'],
            'uni_address' => ['nullable', 'string', 'max:5000'],
            // Sengaja bukan `url`: nilai lama seperti "www.kampus.ac.id" tidak boleh memblokir simpan.
            'uni_website' => ['nullable', 'string', 'max:255'],
            'uni_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'uni_name.required' => 'Nama universitas wajib diisi.',
            'uni_name.max' => 'Nama universitas maksimal 255 karakter.',
            'uni_address.max' => 'Alamat maksimal 5000 karakter.',
            'uni_website.max' => 'Website maksimal 255 karakter.',
            'uni_logo.image' => 'Logo harus berupa gambar.',
            'uni_logo.mimes' => 'Logo harus berformat png, jpg, atau jpeg.',
            'uni_logo.max' => 'Ukuran logo maksimal 2 MB.',
        ];
    }
}
