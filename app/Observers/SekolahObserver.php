<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Sekolah;

class SekolahObserver
{
    public function created(Sekolah $sekolah): void
    {
        ActivityLog::catat('tambah', 'Sekolah', $sekolah);
    }

    public function updated(Sekolah $sekolah): void
    {
        $perubahan = ActivityLog::selisih($sekolah);

        if ($perubahan) {
            ActivityLog::catat('ubah', 'Sekolah', $sekolah, $perubahan);
        }
    }

    public function deleted(Sekolah $sekolah): void
    {
        ActivityLog::catat('hapus', 'Sekolah', $sekolah);
    }
}