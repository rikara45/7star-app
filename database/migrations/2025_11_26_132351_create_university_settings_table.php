<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str; // Import the Str facade
use Illuminate\Support\Facades\DB; // Import the DB facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('university_settings', function (Blueprint $table) {
        $table->id();
        $table->string('uni_name')->default('Universitas Teknologi Indonesia');
        $table->text('uni_address')->nullable();
        $table->string('uni_website')->nullable();
        $table->string('uni_logo_path')->nullable(); // Simpan path gambar logo
        $table->timestamps();
    });

    // Insert 1 data default agar tidak error saat pertama kali
    DB::table('university_settings')->insert([
        'uni_name' => 'Universitas Default',
        'uni_address' => 'Jl. Pendidikan No. 1, Jakarta',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('university_settings');
    }
};
