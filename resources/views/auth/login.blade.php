<x-guest-layout>
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-white mb-2">Selamat Datang</h2>
        <p class="text-gray-400 text-sm">Masuk untuk melanjutkan</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
            <input id="email" type="email" name="email" :value="old('email')" required autofocus
                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:bg-white/10 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
                placeholder="nama@email.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Kata Sandi</label>
            <input id="password" type="password" name="password" required
                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:bg-white/10 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
                placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between text-sm mt-4">
            <label for="remember_me" class="inline-flex items-center cursor-pointer group">
                <input id="remember_me" type="checkbox" class="rounded bg-white/10 border-white/20 text-blue-500 focus:ring-blue-500" name="remember">
                <span class="ml-2 text-gray-400 group-hover:text-white transition">Ingat Saya</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-blue-400 hover:text-blue-300 transition" href="{{ route('password.request') }}">
                    Lupa Kata Sandi?
                </a>
            @endif
        </div>

        <button type="submit" class="w-full mt-6 py-3 px-4 bg-gradient-to-r from-[#D93025] to-[#b91c1c] hover:to-[#991b1b] text-white font-bold rounded-xl shadow-lg shadow-red-900/40 transform hover:-translate-y-0.5 transition-all duration-300 uppercase tracking-widest text-sm">
            Masuk
        </button>

        <div class="relative flex py-4 items-center">
            <div class="flex-grow border-t border-gray-700"></div>
            <span class="flex-shrink-0 mx-4 text-gray-500 text-xs uppercase">Belum punya akun?</span>
            <div class="flex-grow border-t border-gray-700"></div>
        </div>

        <a href="{{ route('register') }}" class="block w-full text-center py-3 px-4 border border-white/20 hover:bg-white/5 text-white font-semibold rounded-xl transition-all duration-300 text-sm">
            Buat Akun Baru
        </a>
    </form>
</x-guest-layout>