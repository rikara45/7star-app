<?php

namespace App\Support;

use App\Models\MasterQuestion;

/**
 * Aturan jawaban penilaian dipakai controller setelah token berhasil
 * diresolusikan, supaya token tak dikenal tetap 404 dan bukan error validasi.
 */
final class AssessmentAnswerRules
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'answers' => ['required', 'array', self::knownQuestionIds()],
            'answers.*' => ['required', 'integer', 'between:1,5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'answers.required' => 'Belum ada jawaban yang dikirim.',
            'answers.array' => 'Format jawaban tidak dikenali.',
            'answers.*.required' => 'Setiap pertanyaan wajib diberi skor.',
            'answers.*.integer' => 'Skor harus berupa angka bulat.',
            'answers.*.between' => 'Skor harus antara 1 sampai 5.',
        ];
    }

    /**
     * Tolak ID pertanyaan yang tidak ada di master_questions dengan 422,
     * bukan 500 dari pelanggaran foreign key.
     *
     * Key non-digit ("1.5", "+1", " 1") juga ditolak: key itu lolos bila hanya
     * di-intval(), lalu meledak saat dipakai sebagai master_question_id.
     */
    private static function knownQuestionIds(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $known = array_map('intval', MasterQuestion::pluck('id')->all());

            $unknown = array_filter(
                array_keys((array) $value),
                fn ($id) => ! ctype_digit((string) $id) || ! in_array((int) $id, $known, true)
            );

            if ($unknown !== []) {
                $fail('Ada pertanyaan yang tidak dikenal dalam jawaban.');
            }
        };
    }
}
