<?php

namespace App\Http\Requests;

use App\Support\PortfolioCategories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePortfolioRequest extends FormRequest
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
            'category' => ['required', 'string', Rule::in(array_keys(PortfolioCategories::all()))],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Kategori portofolio wajib dipilih.',
            'category.in' => 'Kategori portofolio tidak dikenal.',
            'file.required' => 'Berkas bukti wajib diunggah.',
            'file.mimes' => 'Berkas harus berupa PDF, JPG, JPEG, atau PNG.',
            'file.max' => 'Ukuran berkas maksimal 2 MB.',
            'description.max' => 'Keterangan maksimal 500 karakter.',
        ];
    }
}
