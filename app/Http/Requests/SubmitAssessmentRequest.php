<?php

namespace App\Http\Requests;

use App\Models\MasterQuestion;
use Illuminate\Foundation\Http\FormRequest;

class SubmitAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route publik dengan token
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1', $this->knownQuestionIds()],
            'answers.*' => ['required', 'integer', 'between:1,5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.required' => 'Belum ada jawaban yang dikirim.',
            'answers.array' => 'Format jawaban tidak dikenali.',
            'answers.min' => 'Isi minimal satu jawaban.',
            'answers.*.required' => 'Setiap pertanyaan wajib diberi skor.',
            'answers.*.integer' => 'Skor harus berupa angka bulat.',
            'answers.*.between' => 'Skor harus antara 1 sampai 5.',
        ];
    }

    /**
     * Tolak ID pertanyaan yang tidak ada di master_questions dengan 422,
     * bukan 500 dari pelanggaran foreign key.
     */
    private function knownQuestionIds(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $known = MasterQuestion::pluck('id')->all();
            $unknown = array_diff(array_map('intval', array_keys((array) $value)), $known);

            if ($unknown !== []) {
                $fail('Ada pertanyaan yang tidak dikenal dalam jawaban.');
            }
        };
    }
}
