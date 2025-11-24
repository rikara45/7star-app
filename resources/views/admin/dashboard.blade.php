<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Administrator') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-green-500">
                    <div class="text-gray-500 text-sm font-medium uppercase">Total Dosen</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $totalDosen }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-purple-500">
                    <div class="text-gray-500 text-sm font-medium uppercase">Verifikator (Jurusan)</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $totalJurusan }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-red-500">
                    <div class="text-gray-500 text-sm font-medium uppercase">Administrator</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $totalAdmin }}</div>
                </div>
            </div>
            
            <div class="mt-8 text-center text-gray-500">
                <p>Selamat datang di Panel Admin. Gunakan menu navigasi di atas untuk mengelola pengguna.</p>
            </div>
        </div>
    </div>
</x-app-layout>