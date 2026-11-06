<?php

use App\Support\DuplicateRowCleaner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baris duplikat tidak dikembalikan oleh down(): dedupe bersifat destruktif.
 * Backup database sebelum menjalankan migrasi ini di produksi.
 *
 * Portofolio sengaja belum diberi unique (user_id, category). Skor portofolio
 * menjumlahkan seluruh baris per kategori (AssessmentScoreService) dan test
 * karakterisasi mengunci perilaku itu. Menambah index ini butuh keputusan
 * produk apakah satu kategori boleh memuat beberapa bukti.
 *
 * DDL MySQL melakukan commit implisit, jadi index dibuat hanya bila belum ada;
 * menjalankan ulang migrasi setelah gagal di tengah tidak membuat error
 * "Duplicate key name".
 */
return new class extends Migration
{
    public function up(): void
    {
        DuplicateRowCleaner::responses();
        DuplicateRowCleaner::aiAnalyses();

        if (! Schema::hasIndex('assessment_responses', 'assessment_responses_request_question_unique')) {
            Schema::table('assessment_responses', function (Blueprint $table) {
                $table->unique(
                    ['assessment_request_id', 'master_question_id'],
                    'assessment_responses_request_question_unique'
                );
            });
        }

        if (! Schema::hasIndex('ai_analyses', 'ai_analyses_user_period_unique')) {
            Schema::table('ai_analyses', function (Blueprint $table) {
                $table->unique(['user_id', 'assessment_period_id'], 'ai_analyses_user_period_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('ai_analyses', 'ai_analyses_user_period_unique')) {
            Schema::table('ai_analyses', function (Blueprint $table) {
                $table->dropUnique('ai_analyses_user_period_unique');
            });
        }

        if (Schema::hasIndex('assessment_responses', 'assessment_responses_request_question_unique')) {
            Schema::table('assessment_responses', function (Blueprint $table) {
                $table->dropUnique('assessment_responses_request_question_unique');
            });
        }
    }
};
