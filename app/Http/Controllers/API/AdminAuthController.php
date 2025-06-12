<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username'   => 'required|string|unique:admins,username',
            'email'      => 'required|email|unique:admins,email',
            'password'   => ['required', 'confirmed', Password::min(8)],
            'nama_lengkap' => 'required|string',
            'nomor_hp'   => 'nullable|string',
            'alamat'     => 'nullable|string',
        ]);

        $admin = Admin::create([
            'username' => $validated['username'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'nama_lengkap' => $validated['nama_lengkap'], // 🔐 Use Hash::make here
            'nomor_hp' => $validated['nomor_hp'],
            'alamat'   => $validated['alamat'],
            'role' => 'admin'
        ]);

        $token = $admin->createToken('admin_token', ['admin'])->plainTextToken;
        return response()->json([
            'data'  => $admin,
            'token' => $token,
        ], 200);
    }

    public function registerAuthor(Request $request)
    {
        $validated = $request->validate([
            'username'   => 'required|string|unique:admins,username',
            'email'      => 'required|email|unique:admins,email',
            'password'   => ['required', 'confirmed', Password::min(8)],
            'nama_lengkap' => 'required|string',
            'nomor_hp'   => 'nullable|string',
            'alamat'     => 'nullable|string',
        ]);

        $admin = Admin::create([
            'username' => $validated['username'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'nama_lengkap' => $validated['nama_lengkap'], // 🔐 Use Hash::make here
            'nomor_hp' => $validated['nomor_hp'],
            'alamat'   => $validated['alamat'],
            'role' => 'author'
        ]);

        $token = $admin->createToken('author_token', ['admin'])->plainTextToken;
        return response()->json([
            'data'  => $admin,
            'token' => $token,
        ], 200);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', $credentials['email'])->first();

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Akses ditolak: bukan admin'], 403);
        }

        $token = $admin->createToken('admin_token', ['admin'])->plainTextToken;

        return response()->json([
            'admin' => $admin,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user('admin')->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }

    public function me(Request $request)
    {
        /** @var \App\Models\Admin $admin */
        $admin = auth('admin')->user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Akses ditolak: bukan admin'], 403);
        }
        return response()->json([
            'admin' => $admin
        ]);
    }

    public function updateProfileAdmin(Request $request)
    {
        /** @var \App\Models\Admin $admin */
        $admin = auth('admin')->user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Akses ditolak: bukan admin'], 403);
        }

        $request->validate([
            'nomor_hp'   => 'nullable|string',
            'alamat'     => 'nullable|string',
            // 'password' => 'nullable|string|min:6',
        ]);

        if ($request->filled('nomor_hp')) {
            $admin->nomor_hp = $request->nomor_hp;
        }

        if ($request->filled('alamat')) {
            $admin->alamat = $request->alamat;
        }

        // if ($request->filled('password')) {
        //     $admin->password = Hash::make($request->password);
        // }

        $admin->update();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'admin' => $admin
        ]);
    }

    public function changePasswordAdmin(Request $request)
    {
        /** @var \App\Models\Admin $admin */
        $admin = auth('admin')->user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Akses ditolak: bukan admin'], 403);
        }

        $credentials = $request->validate([
            'password' => 'nullable|string|min:6',
            'passwordBaru' => 'nullable|string|min:6|confirmed',
        ]);

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            throw ValidationException::withMessages([
                'error' => ['password salah.'],
            ]);
        } else {
            if ($request->filled('passwordBaru')) {
                $admin->password = Hash::make($credentials['passwordBaru']);
            }

            $admin->update();

            return response()->json([
                'message' => 'Profil berhasil diperbarui',
                'admin' => $admin->password
            ]);
        }
    }

    // Author
    public function loginAuthor(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $author = Admin::where('email', $credentials['email'])->first();

        if (! $author || ! Hash::check($credentials['password'], $author->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if ($author->role !== 'author') {
            return response()->json(['message' => 'Akses ditolak: bukan author'], 403);
        }

        $token = $author->createToken('author_token', ['admin'])->plainTextToken;

        return response()->json([
            'admin' => $author,
            'token' => $token,
        ]);
    }
    public function meAuthor(Request $request)
    {
        /** @var \App\Models\Admin $admin */
        $admin = auth('admin')->user();
        if ($admin->role !== 'author') {
            return response()->json(['message' => 'Akses ditolak: bukan author'], 403);
        }
        return response()->json([
            'author' => $admin
        ]);
    }

    public function updateProfileAuthor(Request $request)
    {
        /** @var \App\Models\Admin $admin */
        $admin = auth('admin')->user();

        if ($admin->role !== 'author') {
            return response()->json(['message' => 'Akses ditolak: bukan author'], 403);
        }

        $request->validate([
            'nomor_hp'   => 'nullable|string',
            'alamat'     => 'nullable|string',
            // 'password' => 'nullable|string|min:6',
        ]);

        if ($request->filled('nomor_hp')) {
            $admin->nomor_hp = $request->nomor_hp;
        }

        if ($request->filled('alamat')) {
            $admin->alamat = $request->alamat;
        }

        // if ($request->filled('password')) {
        //     $admin->password = Hash::make($request->password);
        // }

        $admin->update();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'author' => $admin
        ]);
    }

    public function showAuthor($id)
    {
        $author = Admin::where('id', $id)
            ->where('role', 'author')
            ->first();

        if (!$author) {
            return response()->json(['message' => 'Author tidak ditemukan'], 404);
        }

        return response()->json($author);
    }

    public function listAuthors()
    {
        $authors = Admin::where('role', 'author')->get();

        return response()->json($authors);
    }

    public function destroyAuthor($id)
    {
        $author = Admin::where('role', 'author')->findOrFail($id);

        $author->delete();

        return response()->json(['message' => 'Author berhasil dihapus'], 200);
    }
    public function changePasswordAuthor(Request $request)
    {
        /** @var \App\Models\Admin $admin */
        $admin = auth('admin')->user();

        if ($admin->role !== 'author') {
            return response()->json(['message' => 'Akses ditolak: bukan author'], 403);
        }

        $credentials = $request->validate([
            'password' => 'nullable|string|min:6',
            'passwordBaru' => 'nullable|string|min:6|confirmed',
        ]);

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            throw ValidationException::withMessages([
                'error' => ['password salah.'],
            ]);
        } else {
            if ($request->filled('passwordBaru')) {
                $admin->password = Hash::make($credentials['passwordBaru']);
            }

            $admin->update();

            return response()->json([
                'message' => 'Profil berhasil diperbarui',
                'author' => $admin->password
            ]);
        }
    }
}
