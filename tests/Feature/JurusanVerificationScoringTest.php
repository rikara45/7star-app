<?php

namespace Tests\Feature;

use App\Models\AiAnalysis;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\AssessmentResponse;
use App\Models\MasterQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Mengunci rincian skor per dimensi yang dipakai halaman verifikasi jurusan,
 * karena query-nya dulu disalin dari UserDashboardController.
 */
class JurusanVerificationScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_rincian_dimensi_jurusan_sama_dengan_perhitungan_dosen(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        $period = AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        // Dimensi Profesionalisme: atasan rata-rata 5, sejawat rata-rata 3 → 4
        $this->responses($dosen, $period, 'superior', 'Profesionalisme', [5, 5]);
        $this->responses($dosen, $period, 'peer', 'Profesionalisme', [3, 3]);

        // Dimensi Kolaborasi: hanya atasan, rata-rata (2+4)/2 = 3
        $this->responses($dosen, $period, 'superior', 'Kolaborasi', [2, 4]);

        $this->storedAnalysis($dosen, $period);

        $this->actingAs($jurusan)
            ->get(route('jurusan.result', $dosen->id))
            ->assertOk()
            ->assertViewHas('dimensionDetails', function (array $details) {
                return $details['Profesionalisme'] == 4.0
                    && $details['Kolaborasi'] == 3.0;
            });
    }

    public function test_verifikasi_jurusan_dialihkan_saat_tidak_ada_periode_aktif(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        $period = AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        $this->responses($dosen, $period, 'superior', 'Profesionalisme', [5]);
        $this->storedAnalysis($dosen, $period);

        $period->forceFill(['is_active' => false])->save();

        $this->actingAs($jurusan)
            ->from(route('jurusan.verifikasi.index'))
            ->get(route('jurusan.result', $dosen->id))
            ->assertRedirect(route('jurusan.verifikasi.index'))
            ->assertSessionHas('error', 'Tidak ada periode penilaian aktif.');
    }

    public function test_rincian_dimensi_jurusan_identik_dengan_halaman_hasil_dosen(): void
    {
        [$dosen, $jurusan] = $this->dosenDanJurusan();

        $dosenDetails = $this->actingAs($dosen)
            ->get(route('result.show'))
            ->viewData('dimensionDetails');

        $jurusanDetails = $this->actingAs($jurusan)
            ->get(route('jurusan.result', $dosen->id))
            ->assertOk()
            ->viewData('dimensionDetails');

        $this->assertSame($dosenDetails, $jurusanDetails);
    }

    public function test_pdf_jurusan_memakai_nama_berkas_laporan(): void
    {
        [$dosen, $jurusan] = $this->dosenDanJurusan();

        $response = $this->actingAs($jurusan)->get(route('jurusan.result.pdf', $dosen->id));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'Laporan_'.$dosen->name.'.pdf',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_pdf_jurusan_mengalihkan_bila_belum_ada_analisis(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        $this->actingAs($jurusan)
            ->from(route('jurusan.verifikasi.index'))
            ->get(route('jurusan.result.pdf', $dosen->id))
            ->assertRedirect(route('jurusan.verifikasi.index'))
            ->assertSessionHas('error', 'Dosen ini belum memproses hasil analisis.');
    }

    public function test_pdf_jurusan_mengalihkan_saat_tidak_ada_periode_aktif(): void
    {
        [$dosen, $jurusan] = $this->dosenDanJurusan();

        AssessmentPeriod::where('is_active', true)->update(['is_active' => false]);

        $this->actingAs($jurusan)
            ->from(route('jurusan.verifikasi.index'))
            ->get(route('jurusan.result.pdf', $dosen->id))
            ->assertRedirect(route('jurusan.verifikasi.index'))
            ->assertSessionHas('error', 'Tidak ada periode penilaian aktif.');
    }

    public function test_hasil_jurusan_mengalihkan_bila_dosen_belum_punya_analisis(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        $this->actingAs($jurusan)
            ->from(route('jurusan.verifikasi.index'))
            ->get(route('jurusan.result', $dosen->id))
            ->assertRedirect(route('jurusan.verifikasi.index'))
            ->assertSessionHas('error', 'Dosen ini belum melakukan proses analisis hasil.');
    }

    private function dosenDanJurusan(): array
    {
        $dosen = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Uji']);
        $jurusan = User::factory()->create(['role' => 'jurusan', 'name' => 'Jurusan Uji']);
        $period = AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        $this->responses($dosen, $period, 'superior', 'Profesionalisme', [5, 5]);
        $this->responses($dosen, $period, 'peer', 'Profesionalisme', [3, 3]);
        $this->responses($dosen, $period, 'superior', 'Kolaborasi', [2, 4]);
        $this->storedAnalysis($dosen, $period);

        return [$dosen, $jurusan];
    }

    private function responses(User $user, AssessmentPeriod $period, string $relationship, string $dimension, array $scores): void
    {
        $request = AssessmentRequest::forceCreate([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => 'Penilai '.$relationship,
            'assessor_email' => $relationship.'@example.test',
            'relationship' => $relationship,
            'access_token' => Str::random(40),
            'is_completed' => true,
        ]);

        foreach ($scores as $score) {
            $question = MasterQuestion::forceCreate([
                'dimension' => $dimension,
                'statement_self' => 'Pernyataan self',
                'statement_other' => 'Pernyataan other',
            ]);

            AssessmentResponse::forceCreate([
                'assessment_request_id' => $request->id,
                'master_question_id' => $question->id,
                'score' => $score,
            ]);
        }
    }

    private function storedAnalysis(User $dosen, AssessmentPeriod $period): void
    {
        AiAnalysis::create([
            'user_id' => $dosen->id,
            'assessment_period_id' => $period->id,
            'score_behavior' => 70,
            'score_portfolio' => 100,
            'final_score' => 79,
            'star_rating' => '4 Bintang',
            'ai_narrative' => 'Narasi lama.',
        ]);
    }
}
