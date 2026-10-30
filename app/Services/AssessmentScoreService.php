<?php

namespace App\Services;

use App\Models\AssessmentPeriod;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya sumber perhitungan skor penilaian 7 Star:
 * rata-rata 360 per dimensi, bobot 70% perilaku + 30% portofolio, dan pemetaan bintang 1-7.
 */
class AssessmentScoreService
{
    private const BOBOT_PERILAKU = 0.70;

    private const BOBOT_PORTOFOLIO = 0.30;

    private const SKALA_MAKSIMAL = 5;

    private const AMBANG_BINTANG = [
        91 => '7 Bintang',
        86 => '6 Bintang',
        80 => '5 Bintang',
        72 => '4 Bintang',
        65 => '3 Bintang',
        55 => '2 Bintang',
    ];

    /**
     * Rata-rata skor per dimensi, sudah diagregasi per hubungan penilai.
     *
     * @return array<string, float>
     */
    public function dimensionScores(int $userId, AssessmentPeriod $period): array
    {
        // Data lama bisa punya lebih dari satu baris untuk pertanyaan yang sama.
        // Ambil baris terbaru per pertanyaan agar satu jawaban tidak berbobot ganda.
        $latestResponseIds = DB::table('assessment_responses')
            ->selectRaw('MAX(id) as id')
            ->groupBy('assessment_request_id', 'master_question_id');

        $rawScores = DB::table('assessment_responses')
            ->joinSub($latestResponseIds, 'latest_responses', 'assessment_responses.id', '=', 'latest_responses.id')
            ->join('assessment_requests', 'assessment_responses.assessment_request_id', '=', 'assessment_requests.id')
            ->join('master_questions', 'assessment_responses.master_question_id', '=', 'master_questions.id')
            ->where('assessment_requests.user_id', $userId)
            ->where('assessment_requests.assessment_period_id', $period->id)
            ->where('assessment_requests.is_completed', true)
            ->select('master_questions.dimension', 'assessment_requests.relationship', DB::raw('AVG(assessment_responses.score) as avg_score'))
            ->groupBy('master_questions.dimension', 'assessment_requests.relationship')
            ->get();

        $dimensionScores = [];

        foreach ($rawScores->groupBy('dimension') as $dimension => $items) {
            $dimensionScores[$dimension] = $items->avg('avg_score');
        }

        return $dimensionScores;
    }

    /**
     * @return array{
     *     behaviorScore100: float,
     *     portfolioScore: float|int,
     *     finalScore: string,
     *     stars: string,
     *     dimensionString: string,
     *     portfolios: \Illuminate\Support\Collection,
     *     dimensionDetails: array<string, float>
     * }
     */
    public function calculate(User $user, AssessmentPeriod $period): array
    {
        $dimensionDetails = $this->dimensionScores($user->id, $period);

        $behaviorScore100 = 0;
        $dimensionString = 'Data penilaian 360 belum lengkap.';

        if ($dimensionDetails !== []) {
            $averageBehavior = collect($dimensionDetails)->avg();
            $behaviorScore100 = ($averageBehavior / self::SKALA_MAKSIMAL) * 100;

            $dimensionString = collect($dimensionDetails)->map(function ($score, $dimName) {
                return "- {$dimName}: ".number_format($score, 2).'/5.0';
            })->implode("\n");
        }

        $portfolios = Portfolio::where('user_id', $user->id)->get();
        $portfolioScore = min($portfolios->sum('score'), 100);
        $finalScore = number_format(
            ($behaviorScore100 * self::BOBOT_PERILAKU) + ($portfolioScore * self::BOBOT_PORTOFOLIO),
            2
        );

        return [
            'behaviorScore100' => $behaviorScore100,
            'portfolioScore' => $portfolioScore,
            'finalScore' => $finalScore,
            'stars' => $this->stars((float) $finalScore),
            'dimensionString' => $dimensionString,
            'portfolios' => $portfolios,
            'dimensionDetails' => $dimensionDetails,
        ];
    }

    public function stars(float $finalScore): string
    {
        foreach (self::AMBANG_BINTANG as $minimum => $label) {
            if ($finalScore >= $minimum) {
                return $label;
            }
        }

        return '1 Bintang';
    }
}
