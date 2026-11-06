<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Membersihkan baris duplikat sebelum unique index dipasang. Dipisah dari
 * migrasi agar operasi destruktif ini bisa diuji tanpa menjalankan migrasi.
 */
final class DuplicateRowCleaner
{
    public static function responses(): int
    {
        return self::keepLatest('assessment_responses', ['assessment_request_id', 'master_question_id']);
    }

    public static function aiAnalyses(): int
    {
        return self::keepLatest('ai_analyses', ['user_id', 'assessment_period_id']);
    }

    /**
     * @param  list<string>  $columns
     */
    private static function keepLatest(string $table, array $columns): int
    {
        $duplicateIds = DB::table($table)
            ->whereNotIn('id', function ($query) use ($table, $columns) {
                $query->selectRaw('MAX(id)')->from($table)->groupBy($columns);
            })
            ->pluck('id');

        $deleted = 0;

        foreach ($duplicateIds->chunk(500) as $chunk) {
            $deleted += DB::table($table)->whereIn('id', $chunk->all())->delete();
        }

        return $deleted;
    }
}
