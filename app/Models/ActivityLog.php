<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;                      // TAMBAHAN
use Illuminate\Support\Facades\Auth;             // TAMBAHAN

class ActivityLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['perubahan' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // TAMBAHAN: dipanggil oleh observer
    public static function catat(
        string $aksi, string $objek, Sekolah $sekolah, ?array $perubahan = null
    ): void {
        static::create([
            'user_id'      => Auth::id(),
            'aksi'         => $aksi,
            'objek'        => $objek,
            'sekolah_id'   => $sekolah->id,
            'sekolah_nama' => $sekolah->nama,
            'perubahan'    => $perubahan,
        ]);
    }

    // TAMBAHAN: ambil kolom yang berubah (lama dan baru)
    public static function selisih(Model $model): array
    {
        $hasil = [];

        foreach (Arr::except($model->getChanges(), ['updated_at']) as $kolom => $nilai) {
            $hasil[$kolom] = [
                'lama' => $model->getOriginal($kolom),
                'baru' => $nilai,
            ];
        }

        return $hasil;
    }
}