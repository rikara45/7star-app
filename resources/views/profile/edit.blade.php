<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- BAGIAN 1: EDIT INFORMASI PROFIL (NAMA & EMAIL) --}}
            {{-- Logic: Hanya Admin yang boleh edit. Dosen/Jurusan hanya lihat (Read Only). --}}
            
            @if(Auth::user()->role === 'admin')
                
                {{-- JIKA ADMIN: Tampilkan Form Edit --}}
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

            @else
                
                {{-- JIKA BUKAN ADMIN: Tampilkan Pesan Terkunci --}}
                <div class="p-4 sm:p-8 bg-blue-50 border border-blue-100 shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <h2 class="text-lg font-medium text-blue-900">Informasi Profil Terkunci</h2>
                        <p class="mt-1 text-sm text-blue-700">
                            Demi keamanan dan validitas data, perubahan <strong>Nama</strong> dan <strong>Email</strong> hanya dapat dilakukan oleh Administrator. 
                        </p>
                        
                        <div class="mt-4 bg-white p-4 rounded-md border border-blue-100 flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>
                                <p class="text-sm text-gray-600">
                                    Jika terdapat kesalahan data pada akun Anda, silakan hubungi Admin.
                                </p>
                            
                            </div>
                        </div>

                        {{-- Tampilkan Data Read-Only --}}
                        <div class="mt-6 space-y-4 opacity-75">
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Nama Lengkap</label>
                                <input type="text" value="{{ Auth::user()->name }}" disabled class="mt-1 block w-full bg-gray-100 border-gray-300 rounded-md shadow-sm cursor-not-allowed text-gray-500">
                            </div>
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Email</label>
                                <input type="text" value="{{ Auth::user()->email }}" disabled class="mt-1 block w-full bg-gray-100 border-gray-300 rounded-md shadow-sm cursor-not-allowed text-gray-500">
                            </div>
                        </div>
                    </div>
                </div>

            @endif

            {{-- BAGIAN 2: GANTI PASSWORD (SEMUA USER BOLEH) --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- BAGIAN 3: HAPUS AKUN (DIHILANGKAN TOTAL) --}}
            {{-- Kode untuk delete-user-form sudah dihapus dari sini agar tidak ada yang bisa hapus akun sendiri. --}}
            
        </div>
    </div>
</x-app-layout>