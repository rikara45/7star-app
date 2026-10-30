<?php

namespace App\Http\Controllers;

use App\Models\AiAnalysis;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Services\AssessmentScoreService;
use App\Support\PortfolioCategories;
use Illuminate\Support\Facades\Auth;

class UserDashboardController extends Controller
{
    public function __construct(private AssessmentScoreService $scoreService) {}

    public function index()
    {
        $user = Auth::user();
        $period = AssessmentPeriod::where('is_active', true)->first();

        if (! $period) {
            return view('dashboard.no-period');
        }

        $requests = AssessmentRequest::where('user_id', $user->id)
            ->where('assessment_period_id', $period->id)
            ->get();
        $selfRequest = $requests->where('relationship', 'self')->first();

        $countSuperior = $requests->where('relationship', 'superior')->count();
        $countPeer = $requests->where('relationship', 'peer')->count();
        $assessorCount = $requests->where('relationship', '!=', 'self')->count();
        $maxSuperior = max(1, (int) config('assessment.invitation.max_superior', 2));
        $maxPeer = max(1, (int) config('assessment.invitation.max_peer', 5));

        $completedSuperior = $requests->where('relationship', 'superior')->where('is_completed', true)->count();
        $completedPeer = $requests->where('relationship', 'peer')->where('is_completed', true)->count();

        $isSelfDone = $selfRequest && $selfRequest->is_completed;

        $categories = PortfolioCategories::all();
        $realtimeData = $this->scoreService->calculate($user, $period);

        $myPortfolios = $realtimeData['portfolios']->keyBy('category');
        $currentPortfolioScore = $realtimeData['portfolioScore'];

        $uploadedPortfolioCount = $myPortfolios->count();
        $pendingCount = $myPortfolios->where('status', 'uploaded')->count();
        $missingCount = count($categories) - $uploadedPortfolioCount;

        $isMinRequirementsMet = $isSelfDone
                                && ($completedSuperior >= 1)
                                && ($completedPeer >= 3)
                                && ($uploadedPortfolioCount >= 3);

        $latestAnalysis = AiAnalysis::where('user_id', $user->id)
            ->where('assessment_period_id', $period->id)
            ->latest()
            ->first();

        $hasDataChanged = true;

        if ($latestAnalysis &&
            abs($latestAnalysis->score_behavior - $realtimeData['behaviorScore100']) < 0.01 &&
            abs($latestAnalysis->score_portfolio - $realtimeData['portfolioScore']) < 0.01 &&
            ! empty($latestAnalysis->ai_narrative)) {

            $hasDataChanged = false;
        }

        return view('dashboard_7stars', compact(
            'requests', 'selfRequest', 'period',
            'myPortfolios', 'categories', 'currentPortfolioScore',
            'pendingCount', 'missingCount',
            'countSuperior', 'countPeer', 'assessorCount',
            'maxSuperior', 'maxPeer',
            'isSelfDone', 'hasDataChanged',
            'latestAnalysis',
            'isMinRequirementsMet', 'completedSuperior', 'completedPeer', 'uploadedPortfolioCount'
        ));
    }
}
