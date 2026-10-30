{{-- Section 2a: form undang penilai (atasan / sejawat). --}}
<div class="bg-white shadow-lg sm:rounded-xl border border-gray-100 h-full">
    <div class="p-6 border-b border-gray-100 bg-gray-50 rounded-t-xl">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <span class="bg-purple-100 text-purple-600 w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm font-bold">2</span> Undang Penilai
        </h3>
    </div>
    <div class="p-6">
        <p class="text-sm text-gray-500 mb-6">Masukkan data Atasan atau Teman Sejawat.</p>
        @if(session('success')) <div class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-700 p-3 text-xs rounded-r font-bold">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-3 text-xs rounded-r font-bold">{{ session('error') }}</div> @endif

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
                    <option value="superior" {{ $countSuperior >= $maxSuperior ? 'disabled' : '' }}>Atasan ({{ $countSuperior }}/{{ $maxSuperior }}) {{ $countSuperior >= $maxSuperior ? '- Penuh' : '' }}</option>
                    <option value="peer" {{ $countPeer >= $maxPeer ? 'disabled' : '' }}>Sejawat ({{ $countPeer }}/{{ $maxPeer }}) {{ $countPeer >= $maxPeer ? '- Penuh' : '' }}</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-gray-900 hover:bg-gray-800 text-white font-bold py-3 rounded-lg shadow transition transform hover:-translate-y-0.5">Generate Undangan</button>
        </form>
    </div>
</div>
