<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tambah User Baru') }}
            </h2>
            {{-- UBAH DISINI: Arahkan ke admin.users.index --}}
            <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                &larr; Kembali ke Daftar Pengguna
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl">
                <div class="p-8 bg-white border-b border-gray-200">
                    
                    <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
                        @csrf
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                            <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Alamat Email</label>
                            <input type="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jabatan (Opsional)</label>
                            <input type="text" name="jabatan" placeholder="Contoh: Kaprodi Teknik, Dosen Tetap" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Role (Peran)</label>
                                <select name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-50">
                                    <option value="dosen">Dosen (Peserta Penilaian)</option>
                                    <option value="jurusan">Jurusan (Verifikator)</option>
                                    <option value="admin">Admin Sistem</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Tentukan hak akses user ini.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Password</label>
                                <input type="text" name="password" value="password123" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100 text-gray-500 cursor-not-allowed" readonly>
                                <p class="text-xs text-gray-500 mt-1">Password default diset: <b>password123</b></p>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
                            {{-- UBAH DISINI JUGA: Tombol Batal arahkan ke index users --}}
                            <a href="{{ route('admin.users.index') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50 transition">Batal</a>
                            
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md text-sm font-bold hover:bg-blue-700 shadow transition">
                                Simpan Data
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>