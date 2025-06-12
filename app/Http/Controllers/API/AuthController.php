<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravolt\Avatar\Facade as Avatar;
use Illuminate\Support\Str;
use App\Notifications\VerifyEmailApi;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'alamat'   => 'nullable|string',
            'nohp'     => 'nullable|string',
        ]);

        $name = $validated['name'];
        $image = Avatar::create($name)->getImageObject()->encode(new \Intervention\Image\Encoders\PngEncoder());
        $filename = 'profile_images/' . Str::slug($name) . '-' . time() . '.png';
        Storage::disk('public')->put($filename, $image);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'alamat'   => $validated['alamat'],
            'nohp'     => $validated['nohp'],
            'profile_img' => $filename
        ]);

        // $token = $user->createToken('user_token')->plainTextToken;

        $user->notify(new VerifyEmailApi());

        return response()->json([
            'message' => 'Registrasi berhasil',
            'user' => $user,
            // 'token' => $token,
        ]);
    }

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken('user_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }

    // Ambil semua user
    public function index()
    {
        return response()->json(User::latest()->get());
    }

    // Detail user
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    // Hapus user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus']);
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'name'     => 'nullable|string|max:255',
            'email'    => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'alamat'   => 'nullable|string',
            'nohp'     => 'nullable|string',
        ]);

        if ($request->filled('name')) {
            $user->name = $request->name;
        }
        if ($request->filled('email')) {
            $user->email = $request->email;
        }
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($request->filled('alamat')) {
            $user->alamat = $request->alamat;
        }
        if ($request->filled('nohp')) {
            $user->nohp = $request->nohp;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile berhasil diperbarui',
            'user' => $user
        ]);
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Link reset terkirim.'])
            : response()->json(['message' => 'Gagal mengirim link.'], 400);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'token'    => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->password = Hash::make($request->password);
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password berhasil direset'])
            : response()->json(['message' => 'Reset gagal'], 400);
    }
}
