<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Verifikasi: {{ $dosen->name }}
            </h2>
            <a href="{{ route('jurusan.verifikasi.index') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium flex items-center">
                &larr; Kembali ke Daftar
            </a>
        </div>
    </x-slot>

    <div x-data="{ show: false, message: '' }" 
         @notify.window="show = true; message = $event.detail; setTimeout(() => show = false, 3000)"
         class="fixed bottom-5 right-5 z-50"
         style="display: none;" 
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2">
        <div class="bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg flex items-center border border-gray-700">
            <svg class="w-5 h-5 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span x-text="message" class="font-bold text-sm"></span>
        </div>
    </div>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6 shadow-sm flex items-start">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <strong class="font-bold">Berhasil!</strong>
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $dosen->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $dosen->email }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-gray-400 uppercase">Jabatan</span>
                        <p class="text-sm font-semibold text-gray-700">{{ $dosen->jabatan ?? '-' }}</p>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <p class="text-gray-600 mb-4 text-sm bg-blue-50 p-3 rounded border border-blue-100">
                        <span class="font-bold">Panduan:</span> Kotak yang menyala menandakan dokumen belum dinilai. Klik link "Lihat Dokumen" lalu berikan nilai (0-10).
                    </p>

                    @foreach($categories as $catName => $hint)
                        @php
                            $p = $portfolios->has($catName) ? $portfolios[$catName] : null;
                            $isPending = $p && $p->status != 'verified'; // Cek apakah ini item yang harus dinilai
                        @endphp

                        {{-- LOGIKA STYLE BARU --}}
                        <div class="border rounded-lg transition duration-500 scroll-mt-24 
                            {{-- Jika Pending: Beri Efek Glow Kuning & Shadow Besar --}}
                            {{ $isPending ? 'border-yellow-400 ring-4 ring-yellow-100 shadow-lg bg-yellow-50 pending-target' : ($p ? 'bg-white border-gray-200' : 'bg-gray-50 border-gray-100 opacity-60') }}">
                            
                            <div class="p-4 flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold text-gray-800 text-base">{{ $loop->iteration }}. {{ $catName }}</h4>
                                    <p class="text-xs text-gray-500 italic mt-1">Jenis Bukti: {{ $hint }}</p>
                                </div>
                                
                                @if($p)
                                    <div class="text-right" id="badge-container-{{ $p->id }}">
                                        @if($p->status == 'verified')
                                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-bold border border-green-200 flex items-center ml-auto w-max">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Verified (Skor: <span class="score-val">{{ $p->score }}</span>)
                                            </span>
                                        @else
                                            <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full font-bold border border-yellow-200 animate-pulse block shadow-sm">
                                                🔔 Perlu Dinilai
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="bg-gray-200 text-gray-500 text-xs px-2 py-1 rounded-full">Kosong</span>
                                @endif
                            </div>

                            @if($p)
                                <div class="px-4 pb-4">
                                    <div class="bg-white rounded-md p-4 border {{ $isPending ? 'border-yellow-200' : 'border-blue-100' }}">
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-4 gap-4">
                                            <div class="flex-1">
                                                <p class="text-xs text-gray-400 uppercase font-bold mb-1">Keterangan Dosen:</p>
                                                <p class="text-sm text-gray-800 italic">"{{ $p->description ?? '-' }}"</p>
                                            </div>
                                            <div>
                                                <a href="{{ Storage::url($p->file_path) }}" target="_blank" 
                                                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition transform hover:-translate-y-0.5">
                                                    <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    Lihat Dokumen
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <form onsubmit="submitScore(event, {{ $p->id }})" class="border-t border-gray-100 pt-3 flex items-center gap-4">
                                            @csrf
                                            <div class="flex-1">
                                                <label class="block text-xs font-bold text-gray-700 mb-1">Input Nilai (0-10):</label>
                                                <input type="number" id="input-score-{{ $p->id }}" name="score" value="{{ $p->score }}" min="0" max="10" step="0.1" required 
                                                       class="w-24 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold text-center">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-transparent mb-1">.</label>
                                                
                                                @if($p->status == 'verified')
                                                    <button type="submit" id="btn-submit-{{ $p->id }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2 px-6 rounded shadow transition flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                        Perbarui Nilai
                                                    </button>
                                                @else
                                                    <button type="submit" id="btn-submit-{{ $p->id }}" class="bg-green-600 hover:bg-green-700 text-white text-sm font-bold py-2 px-6 rounded shadow transition flex items-center transform hover:scale-105">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                        Simpan Nilai
                                                    </button>
                                                @endif
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <div class="px-4 pb-4">
                                    <div class="p-3 rounded border border-dashed border-gray-300 text-center">
                                        <span class="text-sm text-gray-400">Dosen belum mengunggah bukti.</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
        // 1. SCROLL OTOMATIS KE ITEM PENDING PERTAMA
        document.addEventListener("DOMContentLoaded", function() {
            // Cari elemen dengan class 'pending-target'
            const firstPending = document.querySelector('.pending-target');
            
            if (firstPending) {
                // Scroll halus ke elemen tersebut (di tengah layar)
                setTimeout(() => {
                    firstPending.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                    
                    // Efek kedip tambahan (Visual Cue)
                    firstPending.classList.add('ring-offset-2', 'ring-offset-blue-50');
                }, 500); // Delay sedikit agar halaman load sempurna dulu
            }
        });

        // 2. FUNGSI SUBMIT AJAX (Sama seperti sebelumnya)
        function submitScore(event, portfolioId) {
            event.preventDefault();
            const form = event.target;
            const scoreInput = document.getElementById('input-score-' + portfolioId);
            const scoreVal = scoreInput.value;
            const btn = document.getElementById('btn-submit-' + portfolioId);
            const badgeContainer = document.getElementById('badge-container-' + portfolioId);
            const cardBox = btn.closest('.border.rounded-lg'); // Ambil kotak pembungkus

            const originalBtnContent = btn.innerHTML;
            btn.innerHTML = '<span class="animate-spin mr-2">↻</span> Menyimpan...';
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');

            const url = "{{ url('/jurusan/nilai') }}/" + portfolioId;
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ score: scoreVal })
            })
            .then(response => {
                if (!response.ok) throw new Error('Gagal menyimpan');
                return response.json();
            })
            .then(data => {
                window.dispatchEvent(new CustomEvent('notify', { detail: 'Nilai berhasil disimpan!' }));

                // Update Tampilan Tombol jadi "Perbarui"
                btn.className = "bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2 px-6 rounded shadow transition flex items-center";
                btn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> <span>Perbarui Nilai</span>';

                // Update Badge Status jadi Hijau
                badgeContainer.innerHTML = `
                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-bold border border-green-200 flex items-center ml-auto w-max">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Verified (Skor: ${scoreVal})
                    </span>
                `;

                // HILANGKAN EFEK GLOW/KUNING SECARA REALTIME
                if(cardBox.classList.contains('pending-target')) {
                    cardBox.classList.remove('border-yellow-400', 'ring-4', 'ring-yellow-100', 'shadow-lg', 'bg-yellow-50', 'pending-target');
                    cardBox.classList.add('bg-white', 'border-gray-200');
                }
            })
            .catch(error => {
                alert('Terjadi kesalahan. Coba lagi.');
                console.error(error);
            })
            .finally(() => {
                if(!btn.innerHTML.includes('Perbarui')) { btn.innerHTML = originalBtnContent; }
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            });
        }
    </script>
</x-app-layout>