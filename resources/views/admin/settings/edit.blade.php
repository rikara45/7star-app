<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Pengaturan Universitas') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6">
                <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Logo Universitas</label>
                        @if($setting->uni_logo_path)
                            <div class="mt-2 mb-2">
                                <img src="{{ Storage::url($setting->uni_logo_path) }}" class="h-20 w-auto object-contain border p-1 rounded">
                            </div>
                        @endif
                        <input type="file" name="uni_logo" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="text-xs text-gray-500 mt-1">Format: PNG/JPG. Transparan lebih baik.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Universitas</label>
                        <input type="text" name="uni_name" value="{{ $setting->uni_name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Alamat Lengkap</label>
                        <textarea name="uni_address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ $setting->uni_address }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Website / Kontak</label>
                        <input type="text" name="uni_website" value="{{ $setting->uni_website }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700">Simpan Pengaturan</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>