<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Penilaian 7 Bintang') }}
        </h2>
    </x-slot>

    <div x-data="{ show: false, message: '' }" 
         @notify.window="show = true; message = $event.detail; setTimeout(() => show = false, 3000)"
         class="fixed bottom-5 right-5 z-50" style="display: none;" x-show="show"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform translate-y-2">
        <div class="bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg flex items-center border border-gray-700">
            <svg class="w-5 h-5 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            <span x-text="message" class="font-bold text-sm"></span>
        </div>
    </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @include('dashboard.self-assessment')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1">
                    @include('dashboard.invite-form')
                </div>
                <div class="lg:col-span-2">
                    @include('dashboard.assessor-list')
                </div>
            </div>

            @include('dashboard.portfolio')
            @include('dashboard.actions')

        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                window.dispatchEvent(new CustomEvent('notify', { detail: 'Link berhasil disalin!' }));
            }, (err) => {
                console.error('Gagal menyalin: ', err);
            });
        }
    </script>
</x-app-layout>
