<?php

use App\Support\DuplicateRowCleaner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baris duplikat tidak dikembalikan oleh down(): dedupe bersifat destruktif.
 * Backup database sebelum menjalankan migrasi ini di produksi.
 *
 * Portofolio belum diberi unique index: kuncinya butuh assessment_period_id
 * (Task 7). Tanpa kolom itu, (user_id, category) memblokir portofolio sah dari
 * periode berbeda.
 */
return new class extends Migration
{
    public function up(): void
    {
        DuplicateRowCleaner::responses();
        DuplicateRowCleaner::aiAnalyses();

        Schema::table('assessment_responses', function (Blueprint $table) {
            $table->unique(
                ['assessment_request_id', 'master_question_id'],
                'assessment_responses_request_question_unique'
            );
        });

        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->unique(['user_id', 'assessment_period_id'], 'ai_analyses_user_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->dropUnique('ai_analyses_user_period_unique');
        });

        Schema::table('assessment_responses', function (Blueprint $table) {
            $table->dropUnique('assessment_responses_request_question_unique');
        });
    }
};
