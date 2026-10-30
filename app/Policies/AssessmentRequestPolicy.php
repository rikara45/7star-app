<?php

namespace App\Policies;

use App\Models\AssessmentRequest;
use App\Models\User;

class AssessmentRequestPolicy
{
    public function delete(User $user, AssessmentRequest $assessmentRequest): bool
    {
        return $assessmentRequest->user_id === $user->id;
    }
}
