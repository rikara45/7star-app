<x-guest-layout>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-white mb-2">Lupa Kata Sandi?</h2>
        <p class="text-gray-400 text-sm mb-4">
            Jangan khawatir. Masukkan email Anda dan kami akan mengirimkan tautan reset kata sandi.
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
            <input id="email" type="email" name="email" :value="old('email')" required autofocus
                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:bg-white/10 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
                placeholder="nama@email.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-[#D93025] to-[#b91c1c] hover:to-[#991b1b] text-white font-bold rounded-xl shadow-lg shadow-red-900/40 transform hover:-translate-y-0.5 transition-all duration-300 uppercase tracking-widest text-sm">
            Kirim Link Reset
        </button>

        <div class="text-center mt-4">
            <a href="{{ route('login') }}" class="text-sm text-gray-400 hover:text-white transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
            </a>
        </div>
    </form>
</x-guest-layout>