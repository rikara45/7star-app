{{-- Section 4: validasi syarat minimal, proses analisis, dan modal konfirmasi. --}}
<div class="mt-8 text-center mb-12 pb-10" x-data="{ isLoading: false, showConfirm: false, missingCount: {{ $missingCount }} }">

    {{-- 1. CEK SYARAT MINIMAL ATAU PENDING (DIGABUNG) --}}
    @if(!$isMinRequirementsMet || $pendingCount > 0)
        <div class="bg-red-50 border-l-4 border-red-500 p-5 max-w-3xl mx-auto mb-6 text-left shadow-md rounded-r-lg">
            <h3 class="text-lg font-bold text-red-800 mb-2">Validasi Data Belum Terpenuhi / Menunggu Verifikasi</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex items-center {{ $isSelfDone ? 'text-green-700 font-semibold' : 'text-red-600' }}">@if($isSelfDone) <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> @else <svg class="w-5 h-5 mr-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> @endif Mengisi Self Assessment</li>
                <li class="flex items-center {{ $completedSuperior >= 1 ? 'text-green-700 font-semibold' : 'text-red-600' }}">@if($completedSuperior >= 1) <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> @else <svg class="w-5 h-5 mr-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> @endif Minimal 1 Atasan (Saat ini: <b>{{ $completedSuperior }}</b>)</li>
                 <li class="flex items-center {{ $completedPeer >= 3 ? 'text-green-700 font-semibold' : 'text-red-600' }}">@if($completedPeer >= 3) <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> @else <svg class="w-5 h-5 mr-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> @endif Minimal 3 Sejawat (Saat ini: <b>{{ $completedPeer }}</b>)</li>
                 <li class="flex items-center {{ $uploadedPortfolioCount >= 3 && $pendingCount == 0 ? 'text-green-700 font-semibold' : 'text-red-600' }}">
                @if($uploadedPortfolioCount >= 3 && $pendingCount == 0)
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                @else
                    <svg class="w-5 h-5 mr-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                @endif
                Minimal 3 Dokumen Portofolio (Saat ini: <b>{{ $uploadedPortfolioCount }}</b>) & Status Verifikasi Jurusan (Pending: <b>{{ $pendingCount }}</b>)
            </li>
            </ul>
        </div>
        <button disabled class="bg-gray-300 text-gray-500 font-bold py-3 px-8 rounded-lg cursor-not-allowed mx-auto flex items-center">Lengkapi / Menunggu Verifikasi</button>

    @else
        {{-- JIKA SYARAT TERPENUHI (BISA PROSES ATAU LIHAT HASIL) --}}
        <div class="flex flex-col items-center justify-center gap-4">

            {{-- A. TOMBOL UTAMA: PROSES (Muncul jika data berubah ATAU belum pernah ada hasil) --}}
            @if($hasDataChanged || !$latestAnalysis)
                <form id="processForm" action="{{ route('result.generate') }}" method="POST" @submit.prevent="if(missingCount > 0) { showConfirm = true; } else { isLoading = true; $el.submit(); }">
                    @csrf
                    <button type="submit" :disabled="isLoading" :class="{ 'opacity-50 cursor-not-allowed transform-none': isLoading }" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-indigo-200 transition duration-200 ease-in-out flex items-center justify-center transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        <span>
                            {{ $latestAnalysis ? 'Update Hasil Analisis (Data Berubah)' : 'Proses Hasil & Analisis AI' }}
                        </span>
                    </button>
                    <p class="text-gray-500 text-xs mt-3" x-show="!isLoading">
                        @if($missingCount > 0) <span class="text-orange-500 font-bold">Info:</span> Ada {{ $missingCount }} kategori kosong (Nilai 0). @else Data lengkap. Siap dianalisis. @endif
                    </p>
                </form>
            @endif

            {{-- B. TOMBOL SEKUNDER: LIHAT HASIL LAMA (Selalu muncul jika pernah ada hasil) --}}
            @if($latestAnalysis)
                <a href="{{ route('result.show') }}" class="{{ ($hasDataChanged) ? 'bg-white border-2 border-gray-200 text-gray-600 hover:border-teal-500 hover:text-teal-600' : 'bg-teal-600 text-white hover:bg-teal-700 shadow-md' }} font-bold py-3 px-8 rounded-xl transition duration-200 ease-in-out flex items-center justify-center w-fit mt-2">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span>Lihat Hasil Analisis {{ $hasDataChanged ? 'Sebelumnya' : '(Terbaru)' }}</span>
                </a>
                @if(!$hasDataChanged)
                    <p class="text-gray-400 text-xs mt-1">Data belum berubah sejak analisis terakhir.</p>
                @endif
            @endif

        </div>
    @endif

    <div x-show="showConfirm" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 backdrop-blur-sm" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md text-left mx-4 transform transition-all" @click.away="showConfirm = false">
            <h3 class="text-lg font-bold text-gray-900 mb-2">Konfirmasi Proses</h3>
            <p class="text-gray-600 text-sm mb-4">Anda belum mengunggah bukti untuk <strong class="text-red-600">{{ $missingCount }} kategori</strong>. Nilai akan dihitung 0. Yakin lanjut?</p>
            <div class="flex justify-end space-x-3">
                <button @click="showConfirm = false" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm font-bold transition">Batal</button>
                <button @click="showConfirm = false; isLoading = true; document.getElementById('processForm').submit();" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-bold transition shadow">Ya, Proses</button>
            </div>
        </div>
    </div>

    <div x-show="isLoading" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm text-center transform transition-all scale-100">
            <div class="relative w-20 h-20 mx-auto mb-4">
                <div class="absolute inset-0 border-4 border-indigo-100 rounded-full animate-ping"></div>
                <div class="absolute inset-0 border-4 border-t-indigo-600 border-r-transparent border-b-indigo-600 border-l-transparent rounded-full animate-spin"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Sedang Menganalisis...</h3>
            <p class="text-gray-500 text-sm">
                Sistem sedang menghitung skor 360° & Portofolio, serta meminta rekomendasi AI.
            </p>
            <p class="text-indigo-600 text-xs font-semibold mt-4 animate-pulse">Mohon tunggu sebentar...</p>
        </div>
    </div>
</div>
