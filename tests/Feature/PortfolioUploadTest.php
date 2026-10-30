<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PortfolioCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_kategori_di_luar_daftar_ditolak(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($dosen)
            ->post(route('portfolio.store'), [
                'category' => 'Kategori Karangan',
                'file' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('portfolios', 0);
        Storage::disk('local')->assertDirectoryEmpty('portfolios');
    }

    public function test_kategori_sah_diterima(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $category = array_key_first(PortfolioCategories::all());

        $this->actingAs($dosen)
            ->post(route('portfolio.store'), [
                'category' => $category,
                'file' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
                'description' => 'Ijazah S3',
            ])
            ->assertRedirect()
            ->assertSessionHas('success_portfolio');

        $this->assertDatabaseHas('portfolios', [
            'user_id' => $dosen->id,
            'category' => $category,
            'description' => 'Ijazah S3',
            'status' => 'uploaded',
            'score' => 0,
        ]);
    }
}
