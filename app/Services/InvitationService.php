<?php

namespace App\Services;

use App\Exceptions\InvitationException;
use App\Mail\AssessmentInvitation;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Aturan undangan penilai: kuota per hubungan, larangan email ganda,
 * pembuatan token, dan pengiriman email yang kegagalannya hanya dicatat.
 */
class InvitationService
{
    /**
     * @param  array{name: string, email: string, relationship: string}  $data
     * @return array{request: AssessmentRequest, email_sent: bool}
     *
     * @throws InvitationException
     */
    public function invite(User $user, AssessmentPeriod $period, array $data): array
    {
        $relationship = $data['relationship'];

        $this->ensureWithinQuota($user, $period, $relationship);
        $this->ensureNotInvited($user, $period, $data);

        $invitation = AssessmentRequest::create([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => $data['name'],
            'assessor_email' => $data['email'],
            'relationship' => $relationship,
            'access_token' => Str::random($this->tokenLength()),
        ]);

        return [
            'request' => $invitation,
            'email_sent' => $this->sendInvitation($invitation),
        ];
    }

    private function ensureWithinQuota(User $user, AssessmentPeriod $period, string $relationship): void
    {
        $quota = match ($relationship) {
            'superior' => [
                'max' => $this->quotaMax('max_superior', 2),
                'label' => 'Atasan',
            ],
            'peer' => [
                'max' => $this->quotaMax('max_peer', 5),
                'label' => 'Teman Sejawat',
            ],
            default => null, // self tidak dibatasi, sama seperti sebelumnya
        };

        if ($quota === null) {
            return;
        }

        $current = AssessmentRequest::where('user_id', $user->id)
            ->where('assessment_period_id', $period->id)
            ->where('relationship', $relationship)
            ->count();

        if ($current >= $quota['max']) {
            throw new InvitationException(
                "Batas Tercapai: Anda hanya boleh mengundang maksimal {$quota['max']} {$quota['label']}."
            );
        }
    }

    /**
     * @param  array{name: string, email: string, relationship: string}  $data
     */
    private function ensureNotInvited(User $user, AssessmentPeriod $period, array $data): void
    {
        $exists = AssessmentRequest::where('user_id', $user->id)
            ->where('assessment_period_id', $period->id)
            ->where('relationship', $data['relationship'])
            ->where('assessor_email', $data['email'])
            ->exists();

        if ($exists) {
            throw new InvitationException('Email orang ini sudah Anda undang sebelumnya.');
        }
    }

    private function sendInvitation(AssessmentRequest $invitation): bool
    {
        try {
            Mail::to($invitation->assessor_email)->send(new AssessmentInvitation($invitation));

            return true;
        } catch (\Throwable $e) {
            // Undangan tetap sah walau SMTP bermasalah; link bisa dibagikan manual.
            Log::error('Gagal kirim email undangan: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Config cache yang belum diperbarui setelah deploy bisa tidak punya key ini.
     * Tanpa batas bawah, panjang 0 menghasilkan token kosong dan undangan kedua
     * gagal karena unique index access_token.
     */
    private function tokenLength(): int
    {
        return max(16, (int) config('assessment.invitation.token_length', 32));
    }

    private function quotaMax(string $key, int $default): int
    {
        return max(1, (int) config('assessment.invitation.'.$key, $default));
    }
}
