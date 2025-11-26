<?php
namespace App\Http\Controllers;

use App\Models\UniversitySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UniversitySettingController extends Controller
{
    public function edit()
    {
        // Ambil data baris pertama (karena cuma ada 1 kampus)
        $setting = UniversitySetting::first();
        return view('admin.settings.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'uni_name' => 'required|string|max:255',
            'uni_logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048', // Max 2MB
        ]);

        $setting = UniversitySetting::first();

        $data = [
            'uni_name' => $request->uni_name,
            'uni_address' => $request->uni_address,
            'uni_website' => $request->uni_website,
        ];

        // Logic Upload Logo
        if ($request->hasFile('uni_logo')) {
            // Hapus logo lama jika ada
            if ($setting->uni_logo_path && Storage::disk('public')->exists($setting->uni_logo_path)) {
                Storage::disk('public')->delete($setting->uni_logo_path);
            }
            
            // Upload baru
            $path = $request->file('uni_logo')->store('settings', 'public');
            $data['uni_logo_path'] = $path;
        }

        $setting->update($data);

        return back()->with('success', 'Profil Universitas berhasil diperbarui!');
    }
}