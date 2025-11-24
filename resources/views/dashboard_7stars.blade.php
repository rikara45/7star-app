<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Penilaian 7 Bintang') }}
        </h2>
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
            <svg class="w-5 h-5 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            <span x-text="message" class="font-bold text-sm"></span>
        </div>
    </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border border-indigo-100 relative">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-indigo-50 rounded-full blur-xl opacity-50"></div>
                
                <div class="p-8 relative z-10">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                <span class="bg-indigo-100 text-indigo-600 w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm font-bold">1</span>
                                Penilaian Diri Sendiri (Self Assessment)
                            </h3>
                            <p class="text-gray-500 mt-2 text-sm max-w-2xl">
                                Langkah pertama adalah menilai diri sendiri secara jujur. Skor ini akan digabungkan dengan penilaian atasan dan sejawat.
                            </p>
                        </div>

                        <div>
                            @if($selfRequest)
                                @if($selfRequest->is_completed)
                                    <div class="flex items-center bg-green-50 border border-green-200 text-green-700 px-5 py-3 rounded-lg shadow-sm">
                                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <div>
                                            <p class="font-bold text-sm">Sudah Mengisi</p>
                                            <p class="text-xs opacity-80">Terima kasih atas partisipasi Anda.</p>
                                        </div>
                                    </div>
                                @else
                                    <a href="{{ route('assessment.show', $selfRequest->access_token) }}" target="_blank" class="group inline-flex items-center bg-indigo-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-indigo-700 transition shadow-md transform hover:-translate-y-0.5">
                                        Isi Kuesioner Sekarang
                                        <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                    </a>
                                @endif
                            @else
                                <form action="{{ route('dashboard.invite') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="name" value="{{ Auth::user()->name }}">
                                    <input type="hidden" name="email" value="{{ Auth::user()->email }}">
                                    <input type="hidden" name="relationship" value="self">
                                    <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-indigo-700 transition shadow-md">
                                        Mulai Self Assessment
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-1">
                    <div class="bg-white shadow-lg sm:rounded-xl border border-gray-100 h-full">
                        <div class="p-6 border-b border-gray-100 bg-gray-50 rounded-t-xl">
                            
                            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                                <span class="bg-purple-100 text-purple-600 w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm font-bold">2</span>
                                Undang Penilai
                            </h3>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-gray-500 mb-6">Masukkan data Atasan atau Teman Sejawat yang akan menilai Anda.</p>
                            
                            @if(session('success'))
                                <div class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-700 p-3 text-xs rounded-r font-bold">{{ session('success') }}</div>
                            @endif
                            @if(session('error'))
                                <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-3 text-xs rounded-r font-bold">{{ session('error') }}</div>
                            @endif

                            <form action="{{ route('dashboard.invite') }}" method="POST" class="space-y-5">
                                @csrf
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1">Nama Penilai</label>
                                    <input type="text" name="name" required class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1">Email Penilai</label>
                                    <input type="email" name="email" required class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1">Hubungan</label>
                                    
                                    <select name="relationship" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        {{-- Opsi Atasan (Disabled jika penuh) --}}
                                        <option value="superior" {{ $countSuperior >= 2 ? 'disabled' : '' }}>
                                            Atasan (Terisi: {{ $countSuperior }}/2) {{ $countSuperior >= 2 ? '- Penuh' : '' }}
                                        </option>
                                        
                                        {{-- Opsi Sejawat (Disabled jika penuh) --}}
                                        <option value="peer" {{ $countPeer >= 5 ? 'disabled' : '' }}>
                                            Teman Sejawat (Terisi: {{ $countPeer }}/5) {{ $countPeer >= 5 ? '- Penuh' : '' }}
                                        </option>
                                    </select>
                                    
                                    {{-- Pesan Bantuan Kecil --}}
                                    <p class="text-[10px] text-gray-400 mt-1">
                                        *Maksimal 2 Atasan dan 5 Teman Sejawat.
                                    </p>
                                </div>
                                <button type="submit" class="w-full bg-gray-900 hover:bg-gray-800 text-white font-bold py-3 rounded-lg shadow transition transform hover:-translate-y-0.5">
                                    Generate Undangan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="bg-white shadow-lg sm:rounded-xl border border-gray-100 h-full flex flex-col">
                        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-gray-800">Daftar Penilai (360°)</h3>
                            <span class="text-xs bg-gray-100 text-gray-500 px-3 py-1 rounded-full font-bold">Terundang: {{ $requests->where('relationship', '!=', 'self')->count() }}</span>
                        </div>
                        
                        <div class="flex-1 overflow-x-auto p-2">
                            <table class="min-w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 rounded-lg">
                                    <tr>
                                        <th class="px-6 py-3 rounded-l-lg">Nama Penilai</th>
                                        <th class="px-6 py-3">Role</th>
                                        <th class="px-6 py-3">Status</th>
                                        <th class="px-6 py-3 rounded-r-lg text-center">Link Manual</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($requests as $req)
                                        @if($req->relationship != 'self')
                                        <tr class="hover:bg-gray-50 transition duration-150 group">
                                            <td class="px-6 py-4 font-medium text-gray-900">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-9 w-9">
                                                        <div class="h-9 w-9 rounded-full flex items-center justify-center text-white font-bold text-xs shadow-sm uppercase
                                                            {{ $req->relationship == 'superior' ? 'bg-purple-500' : 'bg-orange-400' }}">
                                                            {{ substr($req->assessor_name, 0, 1) }}
                                                        </div>
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="font-bold text-sm">{{ $req->assessor_name }}</div>
                                                        <div class="text-xs text-gray-400">{{ $req->assessor_email }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($req->relationship == 'superior') 
                                                    <span class="bg-purple-100 text-purple-700 text-xs px-2.5 py-0.5 rounded-full font-bold border border-purple-200">Atasan</span>
                                                @else 
                                                    <span class="bg-orange-100 text-orange-700 text-xs px-2.5 py-0.5 rounded-full font-bold border border-orange-200">Sejawat</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($req->is_completed)
                                                    <span class="inline-flex items-center text-green-600 font-bold text-xs bg-green-50 px-2 py-1 rounded-full border border-green-100">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                        Selesai
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center text-gray-500 font-medium text-xs bg-gray-100 px-2 py-1 rounded-full">
                                                        <svg class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        Pending
                                                    </span>
                                                @endif
                                            </td>
                                            
                                            <td class="px-6 py-4 text-center">
                                                <div class="relative flex justify-center group/copy">
                                                    <input type="text" readonly value="{{ route('assessment.show', $req->access_token) }}" 
                                                           class="text-xs border border-gray-300 p-1.5 w-24 rounded text-gray-500 focus:w-48 transition-all duration-300 cursor-pointer text-center focus:ring-indigo-500 focus:border-indigo-500"
                                                           onclick="copyToClipboard(this)">
                                                    <span class="absolute -top-8 bg-gray-900 text-white text-[10px] py-1 px-2 rounded opacity-0 group-hover/copy:opacity-100 transition z-10 pointer-events-none whitespace-nowrap">Klik utk Copy</span>
                                                </div>
                                            </td>
                                        </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-6 py-10 text-center text-gray-400">
                                                <div class="flex flex-col items-center">
                                                    <svg class="w-12 h-12 text-gray-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                                    Belum ada penilai diundang.
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 border-b border-gray-100 pb-4">
                    <div class="mb-4 md:mb-0">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center">
                            <span class="bg-green-100 text-green-600 w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm font-bold">3</span>
                            Portofolio (Evidence)
                        </h3>
                        <p class="text-sm text-gray-500 mt-1 ml-11">Unggah bukti untuk 10 kategori. Bobot total: 30%.</p>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 px-6 py-3 rounded-xl text-right shadow-sm">
                        <span class="text-xs block text-green-600 font-bold uppercase tracking-wide">Skor Portofolio</span>
                        <span class="text-3xl font-extrabold text-green-700">{{ $currentPortfolioScore }} <span class="text-base font-medium text-gray-400">/ 100</span></span>
                    </div>
                </div>

                @if(session('success_portfolio'))
                    <div class="mb-6 bg-green-50 text-green-700 px-4 py-3 rounded-lg border border-green-200 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        {{ session('success_portfolio') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    @foreach($categories as $catName => $catHint)
                        @php
                            $uploaded = $myPortfolios->has($catName);
                            $data = $uploaded ? $myPortfolios[$catName] : null;
                            $boxClass = 'bg-white border-gray-200 hover:border-indigo-300';
                            $iconColor = 'text-gray-300';
                            if($uploaded) {
                                if($data->status == 'verified') {
                                    $boxClass = 'bg-green-50 border-green-200';
                                    $iconColor = 'text-green-500';
                                } else {
                                    $boxClass = 'bg-blue-50 border-blue-200';
                                    $iconColor = 'text-blue-500';
                                }
                            }
                        @endphp
                        
                        <div class="border rounded-xl p-5 transition duration-200 shadow-sm {{ $boxClass }} relative group">
                            <div class="absolute top-4 right-4">
                                @if($uploaded)
                                    @if($data->status == 'verified')
                                        <span class="bg-green-200 text-green-800 text-[10px] font-bold px-2 py-1 rounded-full flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Verified ({{ $data->score }})
                                        </span>
                                    @else
                                        <span class="bg-blue-200 text-blue-800 text-[10px] font-bold px-2 py-1 rounded-full flex items-center animate-pulse">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Pending ({{ $data->score }})
                                        </span>
                                    @endif
                                @else
                                    <span class="bg-gray-100 text-gray-400 text-[10px] font-bold px-2 py-1 rounded-full">Belum Upload</span>
                                @endif
                            </div>

                            <div class="flex items-start mb-3 pr-20">
                                <div class="mr-3 mt-0.5 {{ $iconColor }}">
                                    @if($uploaded)
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    @else
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    @endif
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm">{{ $catName }}</h4>
                                    <p class="text-xs text-gray-500 italic mt-1 leading-tight">{{ $catHint }}</p>
                                </div>
                            </div>

                            <form action="{{ route('portfolio.store') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                                @csrf
                                <input type="hidden" name="category" value="{{ $catName }}">
                                
                                @if(!$uploaded)
                                    <div class="space-y-2">
                                        <input type="file" name="file" required class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition">
                                        <input type="text" name="description" placeholder="Keterangan (Opsional)" class="w-full text-xs border-gray-200 rounded-md focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <button type="submit" class="w-full bg-white border border-indigo-200 text-indigo-600 text-xs font-bold py-1.5 rounded-md hover:bg-indigo-50 transition">
                                            Upload Bukti
                                        </button>
                                    </div>
                                @else
                                    <div class="text-xs bg-white bg-opacity-60 p-2 rounded border border-gray-100 mb-2">
                                        <a href="{{ Storage::url($data->file_path) }}" target="_blank" class="flex items-center text-indigo-600 font-bold hover:underline mb-1">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            Lihat File
                                        </a>
                                        <p class="text-gray-500 truncate">{{ $data->description ?? 'Tanpa keterangan' }}</p>
                                    </div>
                                    <details class="group/edit relative">
                                        <summary class="list-none text-[10px] text-gray-400 cursor-pointer hover:text-indigo-600 text-right flex justify-end items-center">
                                            <span>Ganti File?</span>
                                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </summary>
                                        <div class="mt-2 pt-2 border-t border-gray-200 absolute bottom-full right-0 bg-white p-3 rounded shadow-lg border w-64 z-10 mb-1">
                                            <input type="file" name="file" required class="block w-full text-xs text-gray-500 mb-2">
                                            <button type="submit" class="w-full bg-gray-800 text-white text-xs py-1 rounded font-bold">Update</button>
                                        </div>
                                    </details>
                                @endif
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 text-center mb-12 pb-10" 
                 x-data="{ 
                    isLoading: false, 
                    showConfirm: false,
                    missingCount: {{ $missingCount }} 
                 }">
                
                {{-- KONDISI 1: PORTOFOLIO PENDING (TERKUNCI) --}}
                @if($pendingCount > 0)
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 max-w-2xl mx-auto mb-4 text-left shadow-sm">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700"><strong>Menunggu Jurusan:</strong> {{ $pendingCount }} dokumen belum diverifikasi.</p>
                            </div>
                        </div>
                    </div>
                    <button disabled class="bg-gray-300 text-gray-500 font-bold py-3 px-8 rounded-lg cursor-not-allowed flex items-center justify-center mx-auto space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Menunggu Verifikasi Jurusan</span>
                    </button>

                {{-- KONDISI 2: SELF ASSESSMENT BELUM SELESAI --}}
                @elseif(!$isSelfDone)
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 max-w-2xl mx-auto mb-4 text-left shadow-sm">
                        <p class="text-sm text-red-700"><strong>Belum Lengkap:</strong> Selesaikan Penilaian Diri Sendiri dulu.</p>
                    </div>
                    <button disabled class="bg-gray-300 text-gray-500 font-bold py-3 px-8 rounded-lg cursor-not-allowed flex items-center justify-center mx-auto space-x-2">
                        <span>Selesaikan Self Assessment</span>
                    </button>

                {{-- KONDISI 3: PENILAI LAIN BELUM SELESAI --}}
                @elseif($pendingAssessorsCount > 0)
                    <div class="bg-orange-50 border-l-4 border-orange-400 p-4 max-w-2xl mx-auto mb-4 text-left shadow-sm">
                        <p class="text-sm text-orange-700"><strong>Menunggu Penilai:</strong> {{ $pendingAssessorsCount }} orang belum mengisi.</p>
                    </div>
                    <button disabled class="bg-gray-300 text-gray-500 font-bold py-3 px-8 rounded-lg cursor-not-allowed flex items-center justify-center mx-auto space-x-2">
                        <span>Menunggu Penilai Lain</span>
                    </button>

                {{-- KONDISI 4: DATA TIDAK BERUBAH (LIHAT HASIL) --}}
                @elseif(!$hasDataChanged)
                    <a href="{{ route('result.show') }}" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition duration-200 ease-in-out flex items-center justify-center mx-auto space-x-2 transform hover:scale-105 w-fit">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        <span>Lihat Hasil Analisis (Data Terbaru)</span>
                    </a>
                    <p class="text-gray-400 text-xs mt-3">
                        Data Anda belum berubah sejak analisis terakhir. Klik tombol di atas untuk melihat laporan.
                    </p>

                {{-- KONDISI 5: DATA BARU / BELUM PERNAH PROSES (TOMBOL PROSES MUNCUL) --}}
                @else
                    <form id="processForm" action="{{ route('result.generate') }}" method="POST" 
                          @submit.prevent="
                            if(missingCount > 0) { 
                                showConfirm = true; 
                            } else { 
                                isLoading = true; $el.submit(); 
                            }
                          ">
                        @csrf
                        <button type="submit" 
                                :disabled="isLoading"
                                :class="{ 'opacity-50 cursor-not-allowed transform-none': isLoading }"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition duration-200 ease-in-out flex items-center justify-center mx-auto space-x-2 transform hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            <span>Proses Hasil & Analisis AI</span>
                        </button>
                        <p class="text-gray-500 text-xs mt-3" x-show="!isLoading">
                            @if($missingCount > 0)
                                <span class="text-orange-500 font-bold">Info:</span> Ada {{ $missingCount }} kategori kosong (Nilai 0).
                            @else
                                Data baru terdeteksi. Siap dianalisis.
                            @endif
                        </p>
                    </form>
                @endif

                <div x-show="showConfirm" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 backdrop-blur-sm" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="bg-white rounded-lg shadow-xl p-6 max-w-md text-left mx-4 transform transition-all" @click.away="showConfirm = false">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Data Belum Lengkap</h3>
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
                            <div class="absolute inset-0 flex items-center justify-center"><svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg></div>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Sedang Menganalisis...</h3>
                        <p class="text-gray-500 text-sm">
                            Sistem sedang menghitung skor 360° & Portofolio, serta meminta rekomendasi AI.
                        </p>
                        <p class="text-indigo-600 text-xs font-semibold mt-4 animate-pulse">Mohon tunggu sebentar...</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(inputElement) {
            // Select text
            inputElement.select();
            inputElement.setSelectionRange(0, 99999); // Untuk mobile

            // Copy ke clipboard
            document.execCommand("copy");

            // Trigger Notifikasi Popup (Sama seperti Jurusan)
            window.dispatchEvent(new CustomEvent('notify', { detail: 'Link berhasil disalin ke clipboard!' }));
        }
    </script>
</x-app-layout>

