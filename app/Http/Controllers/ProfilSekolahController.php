<?php

namespace App\Http\Controllers;

use App\Models\ProfilSekolah;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfilSekolahController extends Controller
{
    public function edit(Sekolah $sekolah)
    {
        Gate::authorize('update', $sekolah);

        $profil = $sekolah->profil ?? new ProfilSekolah();

        return view('sekolah.profil', compact('sekolah', 'profil'));
    }

    public function update(Request $request, Sekolah $sekolah)
    {
        Gate::authorize('update', $sekolah);

        $data = $request->validate([
            'kepala_sekolah' => ['nullable', 'string', 'max:150'],
            'email'          => ['nullable', 'email', 'max:150'],
            'website'        => ['nullable', 'url', 'max:255'],
            'tahun_berdiri'  => ['nullable', 'integer',
                                 'between:1800,' . date('Y')],
            'akreditasi'     => ['nullable', Rule::in(
                                 ['A', 'B', 'C', 'Belum Terakreditasi'])],
            'jumlah_siswa'   => ['nullable', 'integer', 'min:0'],
            'jumlah_guru'    => ['nullable', 'integer', 'min:0'],
            'deskripsi'      => ['nullable', 'string', 'max:2000'],
            // BARU: validasi logo
            'logo'           => ['nullable', 'image',
                                 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'hapus_logo'     => ['nullable', 'boolean'],
        ]);

        // BARU: pisahkan data logo dari data profil biasa
        $logoBaru  = $request->file('logo');
        $hapusLogo = $request->boolean('hapus_logo');
        unset($data['logo'], $data['hapus_logo']);

        $logoLama = $sekolah->profil?->logo_path;

        if ($logoBaru) {
            $data['logo_path'] = $logoBaru->store('logos', 'public');
        } elseif ($hapusLogo) {
            $data['logo_path'] = null;
        }

        $sekolah->profil()->updateOrCreate([], $data);

        // BARU: hapus file lama bila diganti atau dihapus
        if (($logoBaru || $hapusLogo) && $logoLama) {
            Storage::disk('public')->delete($logoLama);
        }

        return back()->with('success', 'Profil sekolah berhasil disimpan.');
    }
}