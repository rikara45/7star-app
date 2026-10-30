<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUniversitySettingRequest;
use App\Models\UniversitySetting;
use Illuminate\Support\Facades\Storage;

class UniversitySettingController extends Controller
{
    public function edit()
    {
        $this->authorize('manage', UniversitySetting::class);

        $setting = UniversitySetting::firstOrCreate([], ['uni_name' => 'Universitas Teknologi Indonesia']);

        return view('admin.settings.edit', compact('setting'));
    }

    public function update(UpdateUniversitySettingRequest $request)
    {
        $this->authorize('manage', UniversitySetting::class);

        $setting = UniversitySetting::firstOrCreate([], ['uni_name' => 'Universitas Teknologi Indonesia']);

        $data = [
            'uni_name' => $request->validated('uni_name'),
            'uni_address' => $request->validated('uni_address'),
            'uni_website' => $request->validated('uni_website'),
        ];

        if ($request->hasFile('uni_logo')) {
            $this->deleteOldLogo($setting);

            $data['uni_logo_path'] = $request->file('uni_logo')->store('settings', 'public');
        }

        $setting->update($data);

        return back()->with('success', 'Profil Universitas berhasil diperbarui!');
    }

    private function deleteOldLogo(UniversitySetting $setting): void
    {
        if ($setting->uni_logo_path && Storage::disk('public')->exists($setting->uni_logo_path)) {
            Storage::disk('public')->delete($setting->uni_logo_path);
        }
    }
}
