<?php

namespace App\Http\Controllers;

use App\Models\AssessmentPeriod;
use App\Models\UniversitySetting;
use App\Services\AssessmentResultService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class AssessmentResultController extends Controller
{
    public function __construct(private AssessmentResultService $results) {}

    public function generate()
    {
        $period = $this->activePeriod();

        if (! $period) {
            return back()->with('error', 'Tidak ada periode aktif.');
        }

        $this->results->generateFor(Auth::user(), $period);

        return redirect()->route('result.show');
    }

    public function show()
    {
        $period = $this->activePeriod();

        if (! $period) {
            return redirect()->route('dashboard.index')->with('error', 'Tidak ada periode penilaian aktif.');
        }

        $data = $this->results->viewDataFor(Auth::user(), $period);

        if ($data === null) {
            return redirect()->route('dashboard.index')->with('error', 'Belum ada hasil. Silakan proses dulu.');
        }

        return view('result_7stars', $data);
    }

    public function pdf()
    {
        $user = Auth::user();
        $period = $this->activePeriod();

        if (! $period) {
            return back()->with('error', 'Tidak ada periode penilaian aktif.');
        }

        $data = $this->results->viewDataFor($user, $period);

        if ($data === null) {
            return back()->with('error', 'Belum ada hasil untuk dicetak.');
        }

        $pdf = Pdf::loadView('pdf.report', [
            'user' => $user,
            'analysis' => $data['analysis'],
            'setting' => UniversitySetting::current(),
            'dimensionDetails' => $data['dimensionDetails'],
        ]);

        return $pdf->download('Laporan_Kepemimpinan_'.$user->name.'.pdf');
    }

    private function activePeriod(): ?AssessmentPeriod
    {
        return AssessmentPeriod::where('is_active', true)->first();
    }
}
