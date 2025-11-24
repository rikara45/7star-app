<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Pengguna') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 shadow-sm rounded-r flex items-center justify-between animate-fade-in-down">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900">&times;</button>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border border-gray-100">
                <div class="p-6 bg-white border-b border-gray-200">
                    
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Daftar Akun Sistem</h3>
                            <p class="text-sm text-gray-500">Total Pengguna: <span class="font-bold text-indigo-600">{{ $users->count() }}</span></p>
                        </div>

                        <a href="{{ route('admin.users.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2.5 px-5 rounded-lg shadow-md flex items-center transition transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Tambah User Baru
                        </a>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-sm text-left text-gray-500 divide-y divide-gray-200">
                            <thead class="bg-gray-50 text-gray-700 uppercase font-bold text-xs">
                                <tr>
                                    <th class="px-6 py-4 tracking-wider">Profil Pengguna</th>
                                    <th class="px-6 py-4 tracking-wider">Informasi Akun</th>
                                    
                                    <th class="px-6 py-4 tracking-wider relative" x-data="{ open: false }">
                                        <button @click="open = !open" type="button" class="flex items-center hover:text-indigo-600 focus:outline-none uppercase">
                                            Role
                                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            @if(request()->has('roles')) <span class="ml-1 w-2 h-2 bg-blue-500 rounded-full"></span> @endif
                                        </button>
                                        <div x-show="open" @click.away="open = false" class="absolute top-10 left-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-xl z-50" style="display: none;">
                                            <form action="{{ route('admin.users.index') }}" method="GET" class="p-3">
                                                <div class="space-y-2 mb-3">
                                                    <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-1 rounded"><input type="checkbox" name="roles[]" value="dosen" class="rounded text-indigo-600" {{ in_array('dosen', request('roles', [])) ? 'checked' : '' }}> <span class="text-gray-700 lowercase capitalize">Dosen</span></label>
                                                    <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-1 rounded"><input type="checkbox" name="roles[]" value="jurusan" class="rounded text-purple-600" {{ in_array('jurusan', request('roles', [])) ? 'checked' : '' }}> <span class="text-gray-700 lowercase capitalize">Jurusan</span></label>
                                                    <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-1 rounded"><input type="checkbox" name="roles[]" value="admin" class="rounded text-red-600" {{ in_array('admin', request('roles', [])) ? 'checked' : '' }}> <span class="text-gray-700 lowercase capitalize">Admin</span></label>
                                                </div>
                                                <div class="flex justify-between pt-2 border-t">
                                                    <a href="{{ route('admin.users.index') }}" class="text-xs text-gray-500 hover:underline mt-1">Reset</a>
                                                    <button type="submit" class="bg-indigo-600 text-white text-xs px-3 py-1 rounded hover:bg-indigo-700">Filter</button>
                                                </div>
                                            </form>
                                        </div>
                                    </th>

                                    <th class="px-6 py-4 tracking-wider text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($users as $u)
                                <tr class="hover:bg-blue-50 transition duration-150 ease-in-out group">
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm uppercase
                                                    {{ $u->role == 'admin' ? 'bg-red-500' : ($u->role == 'jurusan' ? 'bg-purple-500' : 'bg-indigo-500') }}">
                                                    {{ substr($u->name, 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-gray-900 group-hover:text-indigo-700">
                                                    {{ $u->name }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ $u->jabatan ?? 'Anggota' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $u->email }}</div>
                                        <div class="text-xs text-gray-400">Terdaftar: {{ $u->created_at->format('d M Y') }}</div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full shadow-sm
                                            {{ $u->role == 'admin' ? 'bg-red-100 text-red-800 border border-red-200' : 
                                              ($u->role == 'jurusan' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-green-100 text-green-800 border border-green-200') }}">
                                            {{ ucfirst($u->role) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($u->id != Auth::id())
                                            <div class="flex items-center justify-center space-x-3">
                                                
                                                <a href="{{ route('admin.users.edit', $u->id) }}" class="group/btn flex items-center justify-center w-8 h-8 bg-yellow-50 text-yellow-600 rounded-full hover:bg-yellow-500 hover:text-white transition shadow-sm border border-yellow-200" title="Edit User">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                </a>

                                                <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini beserta seluruh datanya?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="group/btn flex items-center justify-center w-8 h-8 bg-red-50 text-red-600 rounded-full hover:bg-red-500 hover:text-white transition shadow-sm border border-red-200" title="Hapus User">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </form>

                                            </div>
                                        @else
                                            <span class="text-xs font-bold text-gray-400 italic bg-gray-100 px-3 py-1 rounded-full border border-gray-200 cursor-default">
                                                Akun Saya
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        
                        @if($users->isEmpty())
                            <div class="text-center py-12 text-gray-400 bg-gray-50">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <p class="text-sm">Belum ada data pengguna.</p>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>