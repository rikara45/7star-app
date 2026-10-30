<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Penilaian 7 Bintang') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-lg sm:rounded-xl border border-gray-100 p-8 text-center">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 text-yellow-600 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
                <h3 class="text-xl font-bold text-gray-900">Periode penilaian belum aktif</h3>
                <p class="text-gray-600 mt-3 text-sm">
                    Saat ini belum ada periode penilaian yang dibuka. Silakan hubungi admin atau bagian jurusan
                    untuk informasi jadwal penilaian berikutnya.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
