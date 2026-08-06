<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DashboardPasswordController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required'        => 'Masukkan password saat ini.',
            'current_password.current_password'=> 'Password saat ini tidak sesuai.',
            'password.required'               => 'Password baru wajib diisi.',
            'password.min'                    => 'Password baru minimal 8 karakter.',
            'password.confirmed'              => 'Konfirmasi password baru tidak sama.',
        ]);

        // Optional: password baru tidak boleh sama dengan password lama
        if (Hash::check($data['password'], $user->password)) {
            return back()
                ->withErrors(['password' => 'Password baru tidak boleh sama dengan password lama.'])
                ->withInput();
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return back()->with('password_success', 'Password berhasil diubah.');
    }
}
