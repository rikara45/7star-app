<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Laporan Hasil: {{ $dosen->name }}
            </h2>
            <a href="{{ route('jurusan.verifikasi.index') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium flex items-center">
                &larr; Kembali ke Daftar
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border-t-8 border-teal-400 relative mb-8">
                <div class="p-8 text-center">
                    <h3 class="text-gray-500 font-bold tracking-widest uppercase text-sm">Klasifikasi Akhir</h3>
                    
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
                        Total Skor: {{ $analysis->final_score }} / 100
                    </div>
                </div>

                <div class="grid grid-cols-2 border-t border-gray-200 bg-gray-50">
                    <div class="p-6 text-center border-r border-gray-200">
                        <span class="block text-gray-500 text-xs uppercase">Skor Perilaku (360°)</span>
                        <span class="block text-3xl font-bold text-blue-600">{{ number_format($analysis->score_behavior, 1) }}</span>
                    </div>
                    <div class="p-6 text-center">
                        <span class="block text-gray-500 text-xs uppercase">Skor Portofolio</span>
                        <span class="block text-3xl font-bold text-green-600">{{ $analysis->score_portfolio }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <div class="flex items-center mb-4">
                        <div class="bg-teal-100 p-2 rounded-lg mr-4">
                            <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <h3 class="font-bold text-xl text-gray-800">Analisis & Rekomendasi AI</h3>
                    </div>

                    <div class="mb-6 p-6 rounded-lg bg-gray-50 border border-gray-200">
                        <div class="text-gray-700 leading-relaxed text-justify [&>ul]:list-disc [&>ul]:ml-5 [&>ol]:list-decimal [&>ol]:ml-5 [&>p]:mb-4">
                            @php
                                $rawText = $analysis->ai_narrative;
                                $rawText = preg_replace('/(\s+)([1-9]\.\s)/', "\n\n$2", $rawText);
                                $rawText = preg_replace('/(\n\n)([1-9]\..+?:)/', "$1**$2**", $rawText);
                            @endphp
                            {!! Str::markdown($rawText) !!}
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>