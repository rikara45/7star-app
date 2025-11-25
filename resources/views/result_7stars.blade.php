<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan Kepemimpinan Transformasional') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border-t-8 border-yellow-400 relative mb-8">
                <div class="p-8 text-center">
                    <h3 class="text-gray-500 font-bold tracking-widest uppercase text-sm">Hasil Klasifikasi 7 Bintang</h3>
                    
                    <div class="my-6">
                        <h1 class="text-6xl font-extrabold text-gray-900">{{ $analysis->star_rating }}</h1>
                        <div class="flex justify-center mt-2 space-x-1 text-yellow-400 text-4xl">
                            @php
                                $starCount = (int) filter_var($analysis->star_rating, FILTER_SANITIZE_NUMBER_INT);
                            @endphp
                            @for($i=1; $i<=7; $i++)
                                @if($i <= $starCount) <span>★</span>
                                @else <span class="text-gray-200">★</span>
                                @endif
                            @endfor
                        </div>
                    </div>

                    <div class="inline-block bg-gray-100 rounded-full px-6 py-2 text-xl font-bold text-gray-700">
                        Skor Akhir: {{ $analysis->final_score }} / 100
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 border-t border-gray-200 bg-gray-50">
                    
                    <div class="p-6 text-center border-b md:border-b-0 md:border-r border-gray-200 relative group">
                        <span class="block text-gray-500 text-xs uppercase font-bold tracking-wider mb-1">Skor Perilaku (360°)</span>
                        <span class="block text-4xl font-extrabold text-indigo-600 mb-1">{{ number_format($analysis->score_behavior, 1) }}</span>
                        <span class="text-xs text-gray-400 bg-white px-2 py-1 rounded border border-gray-200">Bobot 70%</span>

                        <div class="mt-6 text-left" x-data="{ open: false }">
                            <button @click="open = !open" class="w-full flex justify-between items-center text-xs font-bold text-gray-500 uppercase bg-white border border-gray-200 px-3 py-2 rounded hover:bg-gray-50 transition">
                                <span>Lihat Rincian Aspek</span>
                                <svg class="w-4 h-4 transform transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <div x-show="open" x-collapse class="mt-2 space-y-1 bg-white border border-gray-200 rounded p-3 shadow-sm">
                                @if(!empty($dimensionDetails))
                                    @foreach($dimensionDetails as $dim => $score)
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600 text-xs">{{ $dim }}</span>
                                            <span class="font-bold {{ $score >= 4 ? 'text-green-600' : ($score >= 3 ? 'text-yellow-600' : 'text-red-600') }}">
                                                {{ number_format($score, 2) }}
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-1.5 mb-2">
                                            <div class="h-1.5 rounded-full {{ $score >= 4 ? 'bg-green-500' : ($score >= 3 ? 'bg-yellow-400' : 'bg-red-500') }}" style="width: {{ ($score/5)*100 }}%"></div>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-xs text-gray-400 italic text-center">Detail belum tersedia.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="p-6 text-center flex flex-col justify-center">
                        <span class="block text-gray-500 text-xs uppercase font-bold tracking-wider mb-1">Skor Portofolio</span>
                        <span class="block text-4xl font-extrabold text-green-600 mb-1">{{ $analysis->score_portfolio }}</span>
                        <span class="text-xs text-gray-400 bg-white px-2 py-1 rounded border border-gray-200 inline-block mx-auto">Bobot 30%</span>
                        
                        <div class="mt-6">
                            <a href="{{ route('dashboard.index') }}" class="text-xs font-bold text-green-600 hover:underline flex justify-center items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                Cek Bukti di Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <div class="flex items-center mb-4">
                        <div class="bg-indigo-100 p-2 rounded-lg mr-4">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <h3 class="font-bold text-xl text-gray-800">Analisis & Rekomendasi AI</h3>
                    </div>

                    <div class="mb-6 p-6 rounded-lg bg-gray-50 border border-gray-200">
                        
                        <div class="text-gray-700 leading-relaxed text-justify [&>ul]:list-disc [&>ul]:ml-5 [&>ol]:list-decimal [&>ol]:ml-5 [&>p]:mb-4">
                            @php
                                // AMBIL TEKS ASLI
                                $rawText = $analysis->ai_narrative;
                                $fixedText = preg_replace('/(\s+)([1-9]\.\s)/', "\n\n$2", $rawText);
                            @endphp

                            {!! Str::markdown($fixedText) !!}
                        </div>

                    </div>

                    <div class="pt-4 border-t">
                        <a href="{{ route('dashboard.index') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Kembali ke Dashboard
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>