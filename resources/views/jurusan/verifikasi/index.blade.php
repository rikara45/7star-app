<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Verifikasi Portofolio') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
                
                <div class="flex items-center space-x-3 w-full md:w-auto bg-gray-50 p-1.5 rounded-lg border border-gray-200">
                    <span class="text-xs font-bold text-gray-500 uppercase ml-2 tracking-wide">Filter:</span>
                    <a href="{{ route('jurusan.verifikasi.index') }}" 
                       class="px-4 py-1.5 text-xs font-bold rounded-md transition shadow-sm {{ !request('status') ? 'bg-white text-indigo-600 border border-gray-200' : 'text-gray-500 hover:bg-gray-200' }}">
                        Semua
                    </a>
                    <a href="{{ route('jurusan.verifikasi.index', ['status' => 'pending']) }}" 
                       class="px-4 py-1.5 text-xs font-bold rounded-md transition shadow-sm {{ request('status') == 'pending' ? 'bg-red-500 text-white' : 'text-gray-500 hover:bg-gray-200' }}">
                        Perlu Verifikasi
                    </a>
                </div>

                <form method="GET" action="{{ route('jurusan.verifikasi.index') }}" class="w-full md:w-1/3 relative group">
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama Dosen..." 
                           class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm transition shadow-sm group-hover:border-indigo-300">
                    <div class="absolute top-3 left-3 text-gray-400 group-hover:text-indigo-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-500 divide-y divide-gray-200">
                        <thead class="bg-gray-50 text-gray-700 uppercase font-bold text-xs">
                            <tr>
                                <th class="px-6 py-4 tracking-wider">Profil Dosen</th>
                                <th class="px-6 py-4 tracking-wider">Jabatan / Unit</th>
                                <th class="px-6 py-4 tracking-wider text-center">Status Dokumen</th>
                                <th class="px-6 py-4 tracking-wider text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($dosens as $dosen)
                            <tr class="hover:bg-indigo-50 transition duration-150 ease-in-out group">
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold shadow-sm border border-indigo-200 text-sm">
                                                {{ substr($dosen->name, 0, 1) }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-gray-900 group-hover:text-indigo-700 transition">
                                                {{ $dosen->name }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $dosen->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-medium">{{ $dosen->jabatan ?? '-' }}</div>
                                    <div class="text-xs text-gray-400">{{ $dosen->unit_kerja ?? 'Fakultas' }}</div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($dosen->pending_count > 0)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200 animate-pulse">
                                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                            {{ $dosen->pending_count }} Menunggu
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Selesai
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="{{ route('jurusan.verifikasi.show', $dosen->id) }}" 
                                           class="group/btn inline-flex items-center justify-center px-3 py-2 bg-white border border-indigo-200 rounded-lg shadow-sm text-xs font-bold text-indigo-600 uppercase tracking-wide hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition transform hover:-translate-y-0.5" title="Periksa Dokumen">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            Periksa
                                        </a>

                                        @php
                                            $hasResult = $dosen->aiAnalyses->isNotEmpty();
                                        @endphp

                                        @if($hasResult)
                                            <a href="{{ route('jurusan.result', $dosen->id) }}" 
                                               class="group/res inline-flex items-center justify-center px-3 py-2 bg-white border border-teal-200 rounded-lg shadow-sm text-xs font-bold text-teal-600 uppercase tracking-wide hover:bg-teal-600 hover:text-white hover:border-teal-600 transition transform hover:-translate-y-0.5" title="Lihat Hasil & AI">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                                Hasil
                                            </a>
                                        @else
                                            <span class="inline-flex items-center justify-center px-3 py-2 bg-gray-50 border border-gray-100 rounded-lg text-xs font-bold text-gray-400 uppercase cursor-not-allowed" title="Dosen belum memproses hasil">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                Belum Ada
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-400 bg-gray-50">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <p class="font-medium">Tidak ada data dosen ditemukan.</p>
                                        <p class="text-xs mt-1">Coba ubah filter atau kata kunci pencarian.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-200 bg-gray-50">
                    {{ $dosens->withQueryString()->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>