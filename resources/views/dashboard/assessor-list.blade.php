{{-- Section 2b: daftar penilai yang sudah diundang. --}}
<div class="bg-white shadow-lg sm:rounded-xl border border-gray-100 h-full flex flex-col">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-800">Daftar Penilai</h3>
        <span class="text-xs bg-gray-100 text-gray-500 px-3 py-1 rounded-full font-bold">Total: {{ $assessorCount }}</span>
    </div>
    <div class="flex-1 overflow-x-auto p-2">
        <table class="min-w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 rounded-lg">
                <tr>
                    <th class="px-6 py-3 rounded-l-lg">Nama</th>
                    <th class="px-6 py-3">Role</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 rounded-r-lg text-center">Link / Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($requests as $req)
                    @if($req->relationship != 'self')
                    <tr class="hover:bg-gray-50 transition duration-150 group">
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <div class="flex items-center">
                                <div class="h-9 w-9 rounded-full flex items-center justify-center text-white font-bold text-xs shadow-sm uppercase {{ $req->relationship == 'superior' ? 'bg-purple-500' : 'bg-orange-400' }}">{{ substr($req->assessor_name, 0, 1) }}</div>
                                <div class="ml-3"><div class="font-bold text-sm">{{ $req->assessor_name }}</div><div class="text-xs text-gray-400">{{ $req->assessor_email }}</div></div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($req->relationship == 'superior') <span class="bg-purple-100 text-purple-700 text-xs px-2.5 py-0.5 rounded-full font-bold border border-purple-200">Atasan</span>
                            @else <span class="bg-orange-100 text-orange-700 text-xs px-2.5 py-0.5 rounded-full font-bold border border-orange-200">Sejawat</span> @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($req->is_completed)
                                <x-status-badge tone="green" subtle>
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Selesai
                                </x-status-badge>
                            @else
                                <x-status-badge tone="gray" subtle>
                                    <svg class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Pending
                                </x-status-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($req->is_completed)
                                <span class="text-xs text-gray-400 italic">Data Terkunci</span>
                            @else
                                <div class="flex items-center justify-center space-x-3">
                                    <div class="relative flex justify-center group/copy">
                                        <button onclick="copyToClipboard('{{ route('assessment.show', $req->access_token) }}')" class="p-1.5 rounded-md text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-500 transition" title="Salin Link" aria-label="Salin link penilaian {{ $req->assessor_name }}"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg></button>
                                    </div>
                                    <form action="{{ route('invite.destroy', $req->id) }}" method="POST" onsubmit="return confirm('Batalkan undangan ini?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-md text-red-400 hover:text-red-600 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-500 transition" title="Hapus Undangan" aria-label="Batalkan undangan {{ $req->assessor_name }}"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr><td colspan="4" class="px-6 py-10 text-center text-gray-400">Belum ada penilai diundang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
