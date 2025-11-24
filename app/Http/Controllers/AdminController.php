<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule; // PENTING: Untuk validasi update email

class AdminController extends Controller
{
    // 1. DASHBOARD UTAMA (Hanya Statistik)
    public function index()
    {
        // Hitung statistik saja
        $totalDosen = User::where('role', 'dosen')->count();
        $totalJurusan = User::where('role', 'jurusan')->count();
        $totalAdmin = User::where('role', 'admin')->count();

        return view('admin.dashboard', compact('totalDosen', 'totalJurusan', 'totalAdmin'));
    }

    // 2. HALAMAN MANAJEMEN PENGGUNA (Tabel User)
    public function usersIndex(Request $request)
    {
        // Mulai Query User
        $query = User::query();

        // Cek apakah ada filter 'roles' dari URL (misal: ?roles[]=dosen&roles[]=admin)
        if ($request->has('roles') && is_array($request->roles)) {
            $query->whereIn('role', $request->roles);
        }

        // Ambil data (bisa tambah latest() biar yang baru diatas)
        $users = $query->latest()->get();

        return view('admin.users.index', compact('users'));
    }

    // 3. HALAMAN CREATE (Form Tambah)
    public function create()
    {
        return view('admin.users.create');
    }

    // 4. PROSES SIMPAN (Store)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,jurusan,dosen',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'jabatan' => $request->jabatan,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan!');
    }

    // 5. HALAMAN EDIT (Form Edit) - BARU
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    // 6. PROSES UPDATE - BARU
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            // Validasi email unik, TAPI abaikan user ini sendiri (biar ga error kalau email ga diganti)
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:admin,jurusan,dosen',
        ]);

        $dataToUpdate = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'jabatan' => $request->jabatan,
        ];

        // Jika password diisi, update. Jika kosong, biarkan password lama.
        if ($request->filled('password')) {
            $dataToUpdate['password'] = Hash::make($request->password);
        }

        $user->update($dataToUpdate);

        return redirect()->route('admin.users.index')->with('success', 'Data user berhasil diperbarui!');
    }

    // 7. PROSES HAPUS
    public function destroy(User $user) {
        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }
}