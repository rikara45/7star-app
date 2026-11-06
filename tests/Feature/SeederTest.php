<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\AssessmentResponse;
use App\Models\MasterQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_idempoten_dan_tidak_merusak_jawaban(): void
    {
        config([
            'assessment.seed.admin_email' => 'admin@uji.local',
            'assessment.seed.admin_password' => 'sandi-uji-kuat',
        ]);

        $this->seed();

        $questionCount = MasterQuestion::count();

        $this->assertGreaterThan(0, $questionCount);
        $this->assertSame(1, User::where('role', 'admin')->count());

        $user = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::where('is_active', true)->firstOrFail();
        $assessmentRequest = AssessmentRequest::create([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => 'Penilai',
            'assessor_email' => 'penilai@uji.local',
            'relationship' => 'peer',
            'access_token' => Str::random(32),
        ]);
        $response = AssessmentResponse::create([
            'assessment_request_id' => $assessmentRequest->id,
            'master_question_id' => MasterQuestion::firstOrFail()->id,
            'score' => 4,
        ]);

        $this->seed();

        $this->assertSame($questionCount, MasterQuestion::count());
        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertDatabaseHas('assessment_responses', [
            'id' => $response->id,
            'master_question_id' => $response->master_question_id,
            'score' => 4,
        ]);
    }
}
