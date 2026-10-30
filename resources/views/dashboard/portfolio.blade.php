{{-- Section 3: unggah dan status portofolio. --}}
<div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border border-gray-100 p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 border-b border-gray-100 pb-4">
        <div class="mb-4 md:mb-0">
            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                <span class="bg-green-100 text-green-600 w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm font-bold">3</span> Portofolio (Evidence)
            </h3>
            <p class="text-sm text-gray-500 mt-1 ml-11">Unggah bukti untuk 10 kategori. Bobot total: 30%.</p>
        </div>
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 px-6 py-3 rounded-xl text-right shadow-sm">
            <span class="text-xs block text-green-600 font-bold uppercase tracking-wide">Skor Portofolio</span>
            <span class="text-3xl font-extrabold text-green-700">{{ $currentPortfolioScore }} <span class="text-base font-medium text-gray-400">/ 100</span></span>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 bg-red-50 text-red-700 px-4 py-3 rounded-lg border border-red-200">
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success_portfolio')) <div class="mb-6 bg-green-50 text-green-700 px-4 py-3 rounded-lg border border-green-200 flex items-center"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>{{ session('success_portfolio') }}</div> @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach($categories as $catName => $catHint)
            @php
                $uploaded = $myPortfolios->has($catName);
                $data = $uploaded ? $myPortfolios[$catName] : null;
                $boxClass = $uploaded ? ($data->status == 'verified' ? 'bg-green-50 border-green-200' : 'bg-blue-50 border-blue-200') : 'bg-white border-gray-200 hover:border-indigo-300';
                $iconColor = $uploaded ? ($data->status == 'verified' ? 'text-green-500' : 'text-blue-500') : 'text-gray-300';
            @endphp

            <div class="border rounded-xl p-5 transition duration-200 shadow-sm {{ $boxClass }} relative group">
                <div class="absolute top-4 right-4">
                    @if($uploaded)
                        @if($data->status == 'verified')
                            <x-status-badge tone="green" size="xs">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Verified ({{ $data->score }})
                            </x-status-badge>
                        @else
                            <x-status-badge tone="blue" size="xs" pulse>
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Pending ({{ $data->score }})
                            </x-status-badge>
                        @endif
                    @else
                        <x-status-badge tone="gray" size="xs">Belum Upload</x-status-badge>
                    @endif
                </div>

                <div class="flex items-start mb-3 pr-20">
                    <div class="mr-3 mt-0.5 {{ $iconColor }}"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $uploaded ? 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' : 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z' }}"></path></svg></div>
                    <div><h4 class="font-bold text-gray-800 text-sm">{{ $catName }}</h4><p class="text-xs text-gray-500 italic mt-1 leading-tight">{{ $catHint }}</p></div>
                </div>

                <form action="{{ route('portfolio.store') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                    @csrf
                    <input type="hidden" name="category" value="{{ $catName }}">
                    @if(!$uploaded)
                        <div class="space-y-2">
                            <input type="file" name="file" required class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition">
                            <input type="text" name="description" placeholder="Keterangan (Opsional)" class="w-full text-xs border-gray-200 rounded-md focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <button type="submit" class="w-full bg-white border border-indigo-200 text-indigo-600 text-xs font-bold py-1.5 rounded-md hover:bg-indigo-50 transition">Upload Bukti</button>
                        </div>
                    @else
                        <div class="text-xs bg-white bg-opacity-60 p-2 rounded border border-gray-100 mb-2">
                            <a href="{{ route('portfolio.file', $data) }}" target="_blank" class="flex items-center text-indigo-600 font-bold hover:underline mb-1"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>Lihat File</a>
                            <p class="text-gray-500 truncate">{{ $data->description ?? 'Tanpa keterangan' }}</p>
                        </div>
                        <details class="group/edit relative">
                            <summary class="list-none text-[10px] text-gray-400 cursor-pointer hover:text-indigo-600 text-right flex justify-end items-center"><span>Ganti File?</span><svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></summary>
                            <div class="mt-2 pt-2 border-t border-gray-200 absolute bottom-full right-0 bg-white p-3 rounded shadow-lg border w-64 z-10 mb-1">
                                <p class="text-[10px] text-red-500 mb-2">Mengganti berkas akan mengosongkan nilai verifikasi; jurusan perlu menilai ulang.</p>
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
