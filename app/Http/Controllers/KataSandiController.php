<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class KataSandiController extends Controller
{
    public function edit()
    {
        return view('kata-sandi.edit');
    }

    public function update(Request $request)
    {
        $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password'      => ['required', 'confirmed', 'different:password_lama', Password::min(8)->letters()->numbers()],
        ], [
            'password_lama.current_password' => 'Kata sandi lama tidak sesuai.',
            'password.different'             => 'Kata sandi baru harus berbeda dari yang lama.',
            'password.confirmed'             => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('status', 'Kata sandi berhasil diubah.');
    }
}