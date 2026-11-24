<?php

namespace App\Models;

use App\Enums\Role; // BARU
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'sekolah_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // DIHAPUS: konstanta ROLE_ADMIN, ROLE_DINAS, ROLE_SEKOLAH
    // (diganti enum App\Enums\Role)

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role' => Role::class, // BARU
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isDinas(): bool
    {
        return $this->role === Role::Dinas;
    }

    public function isSekolah(): bool
    {
        return $this->role === Role::Sekolah;
    }

    public function roleLabel(): string
    {
        return ($this->role ?? Role::Sekolah)->label();
    }
}