<?php

namespace Tests\Feature;

use App\Models\AiAnalysis;
use App\Models\AssessmentPeriod;
use App\Models\UniversitySetting;
use App\Models\User;
use App\Support\NarrativeMarkdown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NarrativeRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_helper_membuang_html_berbahaya_tetapi_mempertahankan_markdown(): void
    {
        $html = NarrativeMarkdown::toHtml("<script>alert(1)</script>\n\n**tebal**");

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('<strong>tebal</strong>', $html);
    }

    public function test_halaman_hasil_dosen_tidak_merender_script_dari_narasi(): void
    {
        [$dosen] = $this->analysisDenganNarasi();

        $this->actingAs($dosen)
            ->get(route('result.show'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('<strong>tebal</strong>', false);
    }

    public function test_halaman_hasil_jurusan_tidak_merender_script_dari_narasi(): void
    {
        [$dosen] = $this->analysisDenganNarasi();
        $jurusan = User::factory()->create(['role' => 'jurusan']);

        $this->actingAs($jurusan)
            ->get(route('jurusan.result', $dosen->id))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('<strong>tebal</strong>', false);
    }

    public function test_template_pdf_tidak_merender_script_dari_narasi(): void
    {
        [$dosen, $analysis] = $this->analysisDenganNarasi();

        $html = view('pdf.report', [
            'user' => $dosen,
            'analysis' => $analysis,
            'setting' => UniversitySetting::current(),
            'dimensionDetails' => ['Intellectual Stimulation' => 4.5],
        ])->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('<strong>tebal</strong>', $html);
    }

    /**
     * @return array{0: User, 1: AiAnalysis}
     */
    private function analysisDenganNarasi(): array
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::create(['name' => 'Periode Uji', 'is_active' => true]);

        $analysis = AiAnalysis::create([
            'user_id' => $dosen->id,
            'assessment_period_id' => $period->id,
            'score_behavior' => 80,
            'score_portfolio' => 20,
            'final_score' => 62,
            'star_rating' => '2 Bintang',
            'ai_narrative' => "<script>alert(1)</script>\n\n**tebal**",
        ]);

        return [$dosen, $analysis];
    }
}
