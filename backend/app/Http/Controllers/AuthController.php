<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'identity' => 'required',
            'password' => 'required',
        ]);

        // Login dengan NIP atau email
        $user = \App\Models\User::where('nip', $request->identity)
            ->orWhere('email', $request->identity)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'identity' => ['NIP atau password salah.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'identity' => ['Akun Anda tidak aktif.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'nip' => $user->nip,
                'role' => $user->role?->slug,
                'role_name' => $user->role?->name,
                'unit_kerja' => $user->unitKerja?->nama,
                'pangkat_gol' => $user->pangkat_gol,
                'jabatan' => $user->jabatan,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['role', 'unitKerja']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'nip' => $user->nip,
            'role' => $user->role?->slug,
            'role_name' => $user->role?->name,
            'unit_kerja_id' => $user->unit_kerja_id,
            'unit_kerja' => $user->unitKerja?->nama,
            'pangkat_gol' => $user->pangkat_gol,
            'jabatan' => $user->jabatan,
            'no_hp' => $user->no_hp,
            'alamat' => $user->alamat,
        ]);
    }
}
