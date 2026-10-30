<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(private UserManagementService $users) {}

    public function index()
    {
        $this->authorize('manage', User::class);

        $totalDosen = User::where('role', 'dosen')->count();
        $totalJurusan = User::where('role', 'jurusan')->count();
        $totalAdmin = User::where('role', 'admin')->count();

        return view('admin.dashboard', compact('totalDosen', 'totalJurusan', 'totalAdmin'));
    }

    public function usersIndex(Request $request)
    {
        $this->authorize('manage', User::class);

        $query = User::query();

        // Filter opsional dari URL: ?roles[]=dosen&roles[]=admin
        $roles = $request->input('roles');
        if (is_array($roles)) {
            $query->whereIn('role', $roles);
        }

        $users = $query->latest()->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('manage', User::class);

        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('manage', User::class);

        $this->users->create($request->validated());

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan!');
    }

    public function edit(User $user)
    {
        $this->authorize('manage', User::class);

        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('manage', User::class);

        $this->users->update($user, $request->validated());

        return redirect()->route('admin.users.index')->with('success', 'Data user berhasil diperbarui!');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }
}
