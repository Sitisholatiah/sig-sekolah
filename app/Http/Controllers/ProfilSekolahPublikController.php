<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;

class ProfilSekolahPublikController extends Controller
{
    public function show(Sekolah $sekolah)
    {
        return view('publik.sekolah.show', compact('sekolah'));
    }
}