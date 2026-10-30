<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sudah Diisi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg text-center max-w-md">
        <div class="text-yellow-500 text-6xl mb-4">!</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Link Kadaluarsa</h1>
        <p class="text-gray-600">Anda sudah mengisi penilaian ini sebelumnya. Data tidak dapat diubah kembali.</p>
        @if(session('error'))
            <p class="mt-4 bg-red-50 border border-red-200 text-red-700 text-sm font-bold px-4 py-3 rounded-lg">
                {{ session('error') }}
            </p>
        @endif
    </div>
</body>
</html>