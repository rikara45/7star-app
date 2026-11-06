<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Portofolio dikaitkan ke periode agar nilai periode lama tidak terbawa.
 * Baris lama di-backfill ke periode aktif pertama; baris tanpa periode tetap
 * dihitung lewat fallback orWhereNull sampai data produksi selesai dimigrasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->foreignId('assessment_period_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();
        });

        $activePeriodId = DB::table('assessment_periods')
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        if ($activePeriodId !== null) {
            DB::table('portfolios')
                ->whereNull('assessment_period_id')
                ->update(['assessment_period_id' => $activePeriodId]);
        }
    }

    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_period_id');
        });
    }
};
