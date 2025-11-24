<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Master Pertanyaan (Revisi: Ada 2 Versi Kalimat)
        Schema::create('master_questions', function (Blueprint $table) {
            $table->id();
            $table->string('dimension'); 
            $table->text('statement_self');   // Kalimat untuk Self Assessment
            $table->text('statement_other');  // Kalimat untuk Atasan/Sejawat
            $table->timestamps();
        });

        // 2. Tabel Periode Penilaian (Misal: Periode 2025)
        Schema::create('assessment_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Tabel Portofolio (Bukti Fisik)
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('category'); // Pendidikan, Inovasi, dll
            $table->string('file_path');
            $table->text('description')->nullable();
            $table->float('score')->default(0); // Skor 0-10
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        // 4. Tabel Request Penilaian (Undangan untuk Atasan/Peer)
        Schema::create('assessment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Siapa yang dinilai
            $table->foreignId('assessment_period_id')->constrained()->onDelete('cascade');
            
            $table->string('assessor_name');  // Nama Penilai
            $table->string('assessor_email'); // Email Penilai
            $table->enum('relationship', ['self', 'superior', 'peer']); // Jenis Relasi
            
            $table->string('access_token', 64)->unique(); // Token untuk link tanpa login
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });

        // 5. Tabel Jawaban Kuesioner
        Schema::create('assessment_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_request_id')->constrained('assessment_requests')->onDelete('cascade');
            $table->foreignId('master_question_id')->constrained('master_questions'); // Link ke pertanyaan
            $table->integer('score'); // Nilai 1-5
            $table->timestamps();
        });

        // 6. Tabel Hasil Analisis AI
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('assessment_period_id')->constrained()->onDelete('cascade');
            
            $table->float('score_behavior'); // Hasil 360 (Bobot 70%)
            $table->float('score_portfolio'); // Hasil Portofolio (Bobot 30%)
            $table->float('final_score');     // Total Akhir
            $table->string('star_rating');    // 1-7 Bintang
            
            $table->text('ai_narrative')->nullable(); // Narasi dari AI
            $table->timestamps();
        });
        
        // Tambahan kolom ke tabel users jika belum ada (opsional, cek user table Anda)
        if (!Schema::hasColumn('users', 'jabatan')) {
             Schema::table('users', function (Blueprint $table) {
                $table->string('jabatan')->nullable();
                $table->string('unit_kerja')->nullable();
             });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analyses');
        Schema::dropIfExists('assessment_responses');
        Schema::dropIfExists('assessment_requests');
        Schema::dropIfExists('portfolios');
        Schema::dropIfExists('assessment_periods');
        Schema::dropIfExists('master_questions');
        
        if (Schema::hasColumn('users', 'jabatan')) {
            Schema::table('users', function (Blueprint $table) {
               $table->dropColumn(['jabatan', 'unit_kerja']);
            });
       }
    }
};