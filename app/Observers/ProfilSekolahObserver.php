<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\ProfilSekolah;

class ProfilSekolahObserver
{
    public function created(ProfilSekolah $profil): void
    {
        if ($profil->sekolah) {
            ActivityLog::catat('tambah', 'Profil Sekolah', $profil->sekolah);
        }
    }

    public function updated(ProfilSekolah $profil): void
    {
        $perubahan = ActivityLog::selisih($profil);

        if ($perubahan && $profil->sekolah) {
            ActivityLog::catat('ubah', 'Profil Sekolah', $profil->sekolah, $perubahan);
        }
    }
}