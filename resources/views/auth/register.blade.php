<x-guest-layout>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-white mb-2">Buat Akun Baru</h2>
        <p class="text-gray-400 text-sm">Bergabunglah bersama kami</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Nama Lengkap</label>
            <input type="text" name="name" :value="old('name')" required autofocus
                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Email</label>
            <input type="email" name="email" :value="old('email')" required
                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Kata Sandi</label>
            <input type="password" name="password" required
                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Konfirmasi Kata Sandi</label>
            <input type="password" name="password_confirmation" required
                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
        </div>

        <button type="submit" class="w-full mt-4 py-3 px-4 bg-gradient-to-r from-[#D93025] to-[#b91c1c] hover:to-[#991b1b] text-white font-bold rounded-xl shadow-lg shadow-red-900/40 transform hover:-translate-y-0.5 transition-all duration-300 uppercase tracking-widest text-sm">
            Daftar Sekarang
        </button>

        <div class="text-center mt-4">
            <a href="{{ route('login') }}" class="text-sm text-gray-400 hover:text-white transition underline decoration-gray-600 hover:decoration-white">
                Sudah punya akun? Masuk
            </a>
        </div>
    </form>
</x-guest-layout>