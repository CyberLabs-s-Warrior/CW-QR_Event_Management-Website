<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected function canCreateSuperAdmin(Request $request)
    {
        $currentUser = auth()->user();
        $selectedOption = $request->input('role');

        if ($currentUser->role === 'super_admin' && $selectedOption === 'super_admin') {
            if ($currentUser->id === 1) {
                // Super Admin pertama boleh membuat super_admin baru
                return true;
            } else {
                // Super Admin biasa tidak boleh membuat super_admin baru
                return false;
            }
        }
        // Untuk role lain, tidak ada pembatasan khusus
        return true;
    }

    public function index()
    {
        $this->authorize('isSuperOrAdmin');

        $currentUser = auth()->user();

        if ($currentUser->role === 'super_admin') {
            if ($currentUser->id === 1) {
                // Super Admin pertama: lihat semua user kecuali dirinya sendiri
                $users = User::where('id', '!=', $currentUser->id)
                    ->orderBy('created_at', 'asc')
                    ->get();
            } else {
                // Super Admin biasa: lihat admin saja (tanpa dirinya sendiri)
                $users = User::where('role', 'admin')
                    ->where('id', '!=', $currentUser->id)
                    ->orderBy('created_at', 'asc')
                    ->get();
            }
        } else {
            // Admin biasa atau user lain (tidak punya akses)
            abort(403, 'Unauthorized');
        }

        return view('admin.user.index', compact('users'));
    }

    public function create()
    {
        return view('admin.user.form', [
            'user' => new User(), // kalo create, kita buat instance baru
            'isEdit' => false // menandakan ini adalah form untuk membuat user baru
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'phone_number' => 'required',
            'role' => 'required|in:super_admin,admin',
            'password' => 'required|min:6|confirmed',
        ]);

        if (!$this->canCreateSuperAdmin($request)) {
            return back()->withErrors(['create_role' => 'Hanya super admin pertama (pemilik sistem) yang dapat membuat super admin lain.'])->withInput();
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.user.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $currentUser = auth()->user();
        $user = User::findOrFail($id);



        // Cek permission super admin
        if ($currentUser->role === 'super_admin' && $currentUser->id !== 1) {
            if ($user->role === 'super_admin' && $user->id !== $currentUser->id) {
                abort(403, 'Unauthorized');
            }
        }

        return view('admin.user.form', [
            'user' => $user,
            'isEdit' => true
        ]);
    }

    public function update(Request $request, $id)
    {
        $currentUser = auth()->user();
        $user = User::findOrFail($id);

        if (!$this->canCreateSuperAdmin($request)) {
            return back()->withErrors(['create_role' => 'Hanya super admin pertama (pemilik sistem) yang dapat membuat super admin lain.'])->withInput();
        }

        // Cek permission super admin
        if ($currentUser->role === 'super_admin' && $currentUser->id !== 1) {
            if ($user->role === 'super_admin' && $user->id !== $currentUser->id) {
                abort(403, 'Unauthorized');
            }
        }

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'required',
            'role' => 'required|in:super_admin,admin',
            'password' => 'nullable|min:6|confirmed',
        ]);

        $data = $request->only(['name', 'email', 'phone_number', 'role']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.user.index')->with('success', 'User berhasil diupdate.');
    }

    public function destroy($id)
    {
        $currentUser = auth()->user();
        $user = User::findOrFail($id);

        // Cek permission super admin
        if ($currentUser->role === 'super_admin' && $currentUser->id !== 1 && $currentUser->id !== $id) {
            if ($user->role === 'super_admin' && $user->id !== $currentUser->id) {
                abort(403, 'Unauthorized');
            }
        }

        $user->delete();

        return redirect()->route('admin.user.index')->with('success', 'User berhasil dihapus.');
    }
}
