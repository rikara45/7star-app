@extends('layouts.public')

@section('title', 'Terima Kasih')

@section('content')
    <main class="min-h-screen flex items-center justify-center px-4">
        <div class="bg-white p-8 rounded-xl shadow-lg text-center max-w-md w-full">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-50 text-green-600">
                <svg class="h-9 w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Terima Kasih!</h1>
            <p class="text-gray-600">Penilaian Anda telah berhasil disimpan ke dalam sistem.</p>
        </div>
    </main>
@endsection
