{{-- Section 1: penilaian diri sendiri (self assessment). --}}
<div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border border-indigo-100 relative">
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-indigo-50 rounded-full blur-xl opacity-50"></div>
    <div class="p-8 relative z-10">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                    <span class="bg-indigo-100 text-indigo-600 w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm font-bold">1</span>
                    Penilaian Diri Sendiri (Self Assessment)
                </h3>
                <p class="text-gray-500 mt-2 text-sm max-w-2xl">Langkah pertama adalah menilai diri sendiri secara jujur. Skor ini akan digabungkan dengan penilaian atasan dan sejawat.</p>
            </div>
            <div>
                @if($selfRequest)
                    @if($selfRequest->is_completed)
                        <div class="flex items-center bg-green-50 border border-green-200 text-green-700 px-5 py-3 rounded-lg shadow-sm">
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div><p class="font-bold text-sm">Sudah Mengisi</p><p class="text-xs opacity-80">Terima kasih.</p></div>
                        </div>
                    @else
                        <a href="{{ route('assessment.show', $selfRequest->access_token) }}" target="_blank" class="group inline-flex items-center bg-indigo-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-indigo-700 transition shadow-md transform hover:-translate-y-0.5">
                            Isi Kuesioner Sekarang <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    @endif
                @else
                    <form action="{{ route('dashboard.invite') }}" method="POST">
                        @csrf
                        <input type="hidden" name="name" value="{{ $user->name }}">
                        <input type="hidden" name="email" value="{{ $user->email }}">
                        <input type="hidden" name="relationship" value="self">
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-indigo-700 transition shadow-md">Mulai Self Assessment</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
