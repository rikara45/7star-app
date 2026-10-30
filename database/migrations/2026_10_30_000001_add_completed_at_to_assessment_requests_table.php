<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assessment_requests', 'completed_at')) {
            Schema::table('assessment_requests', function (Blueprint $table) {
                $table->timestamp('completed_at')->nullable()->after('is_completed');
            });
        }

        // Baris yang selesai sebelum kolom ini ada memakai updated_at sebagai perkiraan.
        DB::table('assessment_requests')
            ->where('is_completed', true)
            ->whereNull('completed_at')
            ->update(['completed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('assessment_requests', 'completed_at')) {
            Schema::table('assessment_requests', function (Blueprint $table) {
                $table->dropColumn('completed_at');
            });
        }
    }
};
