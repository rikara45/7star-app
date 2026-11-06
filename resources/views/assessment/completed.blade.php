@extends('layouts.public')

@section('title', 'Penilaian Sudah Diisi')

@section('content')
    <main class="min-h-screen flex items-center justify-center px-4">
        <div class="bg-white p-8 rounded-xl shadow-lg text-center max-w-md w-full">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                <svg class="h-9 w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-8.2 14.14A1 1 0 003 19.5h18a1 1 0 00.91-1.5L13.71 3.86a1 1 0 00-1.42 0z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Penilaian Sudah Diisi</h1>
            <p class="text-gray-600">Anda sudah mengisi penilaian ini sebelumnya. Data tidak dapat diubah kembali.</p>
            @if (session('error'))
                <p class="mt-4 bg-red-50 border border-red-200 text-red-700 text-sm font-bold px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </p>
            @endif
        </div>
    </main>
@endsection
