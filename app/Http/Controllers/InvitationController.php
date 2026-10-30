<?php

namespace App\Http\Controllers;

use App\Exceptions\InvitationException;
use App\Http\Requests\StoreInvitationRequest;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Services\InvitationService;

class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitations) {}

    public function store(StoreInvitationRequest $request)
    {
        $period = AssessmentPeriod::where('is_active', true)->firstOrFail();

        try {
            $result = $this->invitations->invite($request->user(), $period, $request->validated());
        } catch (InvitationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            $result['email_sent']
                ? 'Undangan berhasil dibuat & email telah dikirim!'
                : 'Undangan dibuat, tapi Email gagal terkirim. Silakan bagikan Link Manual.'
        );
    }

    public function destroy(AssessmentRequest $assessmentRequest)
    {
        $this->authorize('delete', $assessmentRequest);

        if ($assessmentRequest->is_completed) {
            return back()->with('error', 'Tidak bisa membatalkan penilaian yang sudah selesai.');
        }

        $assessmentRequest->delete();

        return back()->with('success', 'Undangan berhasil dibatalkan.');
    }
}
