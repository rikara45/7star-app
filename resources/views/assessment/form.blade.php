<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Kepemimpinan 7 Bintang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 font-sans text-gray-900">

<div class="max-w-4xl mx-auto py-10 px-4" x-data="{ showConfirm: false }">
    
    <div class="bg-white shadow-lg rounded-xl p-8 mb-8 border-t-4 border-indigo-600">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Instrumen Penilaian 360°</h1>
        <p class="text-gray-600 mb-6">Klasifikasi Penilaian 7 Bintang – Transformational Leadership Rating</p>
        
        <div class="bg-indigo-50 p-5 rounded-lg border border-indigo-100">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="text-xs font-bold text-indigo-500 uppercase tracking-wide">Target Penilaian</span>
                    <p class="text-lg font-semibold text-gray-800">{{ $assessmentRequest->user->name }}</p>
                    <p class="text-sm text-gray-600">{{ $assessmentRequest->user->jabatan ?? 'Dosen' }}</p>
                </div>
                <div>
                    <span class="text-xs font-bold text-indigo-500 uppercase tracking-wide">Penilai</span>
                    <p class="text-lg font-semibold text-gray-800">{{ $assessmentRequest->assessor_name }}</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 mt-1">
                        Peran: {{ ucfirst($assessmentRequest->relationship) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <form id="assessmentForm" action="{{ route('assessment.store', $token) }}" method="POST">
        @csrf

        @foreach($groupedQuestions as $dimension => $questions)
            <div class="bg-white shadow-md rounded-xl p-8 mb-8">
                <h2 class="text-xl font-bold text-indigo-700 mb-6 border-b border-gray-100 pb-3 flex items-center">
                    <span class="bg-indigo-100 text-indigo-600 w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm font-bold">{{ $loop->iteration }}</span>
                    {{ $dimension }}
                </h2>
                
                <div class="space-y-8">
                    @foreach($questions as $q)
                        <div class="p-4 rounded-lg hover:bg-gray-50 transition duration-150">
                            @php
                                if ($assessmentRequest->relationship === 'self') {
                                    $displayText = $q->statement_self;
                                } else {
                                    $displayText = $q->statement_other;
                                }
                            @endphp

                            <p class="text-gray-800 font-medium mb-4 text-base leading-relaxed">
                                <span class="font-bold mr-1">{{ $loop->iteration }}.</span> {{ $displayText }}
                            </p>
                            
                            <div class="flex flex-col sm:flex-row justify-between items-center max-w-xl mx-auto gap-2 sm:gap-0">
                                <span class="text-xs text-gray-400 font-semibold uppercase hidden sm:block w-20 text-right mr-4">Tidak Pernah</span>
                                
                                <div class="flex justify-between items-center w-full sm:w-auto gap-2 bg-gray-100 p-2 rounded-full px-6">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <label class="cursor-pointer group relative">
                                            <input type="radio" name="answers[{{ $q->id }}]" value="{{ $i }}" class="peer sr-only" required>
                                            
                                            <div class="w-10 h-10 flex items-center justify-center rounded-full bg-white border-2 border-gray-300 text-gray-400 font-bold transition-all duration-200 
                                                        peer-checked:bg-indigo-600 peer-checked:border-indigo-600 peer-checked:text-white peer-checked:scale-110
                                                        group-hover:border-indigo-400 group-hover:text-indigo-500">
                                                {{ $i }}
                                            </div>
                                            
                                            {{-- <span class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 text-[10px] text-gray-400 opacity-0 peer-checked:opacity-100 transition">
                                                Skor {{ $i }}
                                            </span> --}}
                                        </label>
                                    @endfor
                                </div>

                                <span class="text-xs text-gray-400 font-semibold uppercase hidden sm:block w-20 text-left ml-4">Selalu</span>
                            </div>
                            
                            <div class="flex justify-between text-xs text-gray-400 mt-2 sm:hidden px-2">
                                <span>Tidak Pernah</span>
                                <span>Selalu</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="flex justify-end pb-10">
            <button type="button" @click="showConfirm = true" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-10 rounded-xl shadow-lg transition transform hover:-translate-y-1 flex items-center text-lg">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Kirim Penilaian
            </button>
        </div>
    </form>

    <div x-show="showConfirm" 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4 transform transition-all scale-100"
             @click.away="showConfirm = false">
            
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 mb-4">
                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <div class="text-center">
                <h3 class="text-xl font-bold text-gray-900 mb-2">Konfirmasi Pengiriman</h3>
                <p class="text-sm text-gray-500">
                    Apakah Anda yakin ingin mengirim penilaian ini? 
                    <br>
                    <span class="text-red-500 font-semibold">Data tidak dapat diubah setelah dikirim.</span>
                </p>
            </div>

            <div class="mt-8 flex justify-center space-x-4">
                <button @click="showConfirm = false" class="px-5 py-2.5 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    Batal, Cek Lagi
                </button>
                
                <button @click="document.getElementById('assessmentForm').submit()" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-lg transition transform hover:-translate-y-0.5">
                    Ya, Kirim Sekarang
                </button>
            </div>
        </div>
    </div>

</div>

</body>
</html>