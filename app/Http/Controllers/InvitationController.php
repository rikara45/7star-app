<?php

namespace App\Http\Controllers;

use App\Exceptions\InvitationException;
use App\Http\Requests\StoreInvitationRequest;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Services\InvitationService;
use Illuminate\Support\Facades\DB;

class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitations) {}

    public function store(StoreInvitationRequest $request)
    {
        $period = AssessmentPeriod::where('is_active', true)->first();

        if (! $period) {
            return back()->with('error', 'Tidak ada periode aktif.');
        }

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

        // Kunci baris yang sama dengan yang dipakai jalur submit, supaya
        // jawaban yang masuk bersamaan tidak terhapus lewat cascade.
        return DB::transaction(function () use ($assessmentRequest) {
            $locked = AssessmentRequest::whereKey($assessmentRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->is_completed) {
                return back()->with('error', 'Tidak bisa membatalkan penilaian yang sudah selesai.');
            }

            // Menghapus undangan ikut menghapus jawabannya lewat cascade; jawaban
            // parsial tetap data penilai yang tidak boleh hilang diam-diam.
            if ($locked->responses()->exists()) {
                return back()->with('error', 'Tidak bisa membatalkan undangan yang sudah diisi.');
            }

            $locked->delete();

            return back()->with('success', 'Undangan berhasil dibatalkan.');
        });
    }
}
