<?php

namespace App\Services;

use App\Models\AiAnalysis;
use App\Models\AssessmentPeriod;
use App\Models\User;
use App\Support\PortfolioCategories;

/**
 * Satu-satunya jalur penyusunan hasil penilaian dosen: data tampilan (analisis
 * tersimpan + rincian dimensi realtime), orkestrasi narasi AI, dan penyimpanan.
 */
class AssessmentResultService
{
    public function __construct(
        private AssessmentScoreService $scoreService,
        private AiNarrativeService $narrativeService,
    ) {}

    /**
     * Analisis tersimpan terbaru lintas periode, sesuai perilaku halaman hasil.
     */
    public function latestAnalysis(User $user): ?AiAnalysis
    {
        return AiAnalysis::where('user_id', $user->id)->latest()->first();
    }

    /**
     * Data yang dipakai halaman hasil dan PDF, supaya keduanya tidak bisa berbeda.
     *
     * @return array{analysis: AiAnalysis, dimensionDetails: array<string, float>}|null
     */
    public function viewDataFor(User $user, AssessmentPeriod $period): ?array
    {
        $analysis = $this->latestAnalysis($user);

        if ($analysis === null) {
            return null;
        }

        return [
            'analysis' => $analysis,
            'dimensionDetails' => $this->scoreService->calculate($user, $period)['dimensionDetails'],
        ];
    }

    /**
     * Hitung skor, minta narasi AI bila data berubah, lalu simpan analisisnya.
     */
    public function generateFor(User $user, AssessmentPeriod $period): void
    {
        $data = $this->scoreService->calculate($user, $period);

        $lastAnalysis = AiAnalysis::where('user_id', $user->id)
            ->where('assessment_period_id', $period->id)
            ->latest()
            ->first();

        if ($lastAnalysis &&
            abs($lastAnalysis->score_behavior - $data['behaviorScore100']) < 0.01 &&
            abs($lastAnalysis->score_portfolio - $data['portfolioScore']) < 0.01 &&
            ! empty($lastAnalysis->ai_narrative)) {

            return;
        }

        $portfolioDetailString = $this->portfolioDetail($data['portfolios']);

        $prompt = "Bertindaklah sebagai Konsultan SDM Universitas profesional. 
        DATA PROFIL:
        Nama: {$user->name}
        Total Skor Akhir: {$data['finalScore']} (Level {$data['stars']})
        
        RINCIAN SKOR 360 (PERILAKU):
        {$data['dimensionString']}

        RINCIAN PORTOFOLIO (BUKTI FISIK):
        {$portfolioDetailString}

        TUGAS:
        Berikan analisis naratif dalam Bahasa Indonesia yang mencakup:
        1. **Analisis Kekuatan**: Apa keunggulan utama dari perilaku dan portofolio yang sudah ada?
        2. **Celah Kinerja (Gap)**: Soroti portofolio apa yang BELUM dikumpulkan atau nilai 360 yang rendah.
        3. **Rekomendasi**: 3 langkah konkret untuk melengkapi kekurangan tersebut.";

        $aiNarrative = $this->narrativeService->generate($prompt);

        AiAnalysis::updateOrCreate(
            ['user_id' => $user->id, 'assessment_period_id' => $period->id],
            [
                'score_behavior' => $data['behaviorScore100'],
                'score_portfolio' => $data['portfolioScore'],
                'final_score' => $data['finalScore'],
                'star_rating' => $data['stars'],
                'ai_narrative' => $aiNarrative,
            ]
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Portfolio>  $portfolios
     */
    private function portfolioDetail($portfolios): string
    {
        $lines = [];

        foreach (PortfolioCategories::all() as $catName => $catHint) {
            $portfolio = $portfolios->firstWhere('category', $catName);

            if ($portfolio) {
                $status = $portfolio->status == 'verified' ? 'Terverifikasi' : 'Menunggu Verifikasi';
                $lines[] = "- {$catName}: SUDAH ADA ({$status}, Poin: {$portfolio->score})";
            } else {
                $lines[] = "- {$catName}: BELUM DIKUMPULKAN (Poin: 0)";
            }
        }

        return implode("\n", $lines);
    }
}
