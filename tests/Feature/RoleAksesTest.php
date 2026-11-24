<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoleAksesTest extends TestCase
{
    use RefreshDatabase;

    /** Kolom status akun yang umum dipakai; yang ada di tabel users akan diisi "aktif". */
    private const KOLOM_AKTIF = [
        'is_active' => true,
        'is_aktif'  => true,
        'aktif'     => true,
        'active'    => true,
        'status'    => 'aktif',
    ];

    private function akun(Role $role): User
    {
        $data = ['role' => $role];

        foreach (self::KOLOM_AKTIF as $kolom => $nilai) {
            if (Schema::hasColumn('users', $kolom)) {
                $data[$kolom] = $nilai;
            }
        }

        return User::factory()->create($data);
    }

    /** Cari URL dari daftar route berdasarkan beberapa kata kunci dan method. */
    private function uri(array|string $kataKunci, string $method = 'GET'): string
    {
        foreach ((array) $kataKunci as $kata) {
            foreach (Route::getRoutes() as $r) {
                $uri = $r->uri();

                if (in_array($method, $r->methods(), true)
                    && str_contains($uri, $kata)
                    && ! str_contains($uri, '{')
                    && ! str_contains($uri, 'login')
                    && ! str_contains($uri, 'logout')) {
                    return '/' . ltrim($uri, '/');
                }
            }
        }

        $this->fail(
            'Route ' . $method . ' dengan kata kunci [' . implode(', ', (array) $kataKunci)
            . '] tidak ditemukan. Cek php artisan route:list'
        );
    }

    public function test_role_tersimpan_sebagai_enum(): void
    {
        $u = $this->akun(Role::Admin)->fresh();

        $this->assertSame(Role::Admin, $u->role);
        $this->assertDatabaseHas('users', ['id' => $u->id, 'role' => Role::Admin->value]);
    }

    public function test_admin_bisa_buka_halaman_admin(): void
    {
        $admin = $this->akun(Role::Admin);

        $daftar = [
            ['dashboard'],
            ['pengguna', 'users', 'user'],
            ['aktivitas', 'activity', 'log'],
        ];

        foreach ($daftar as $kata) {
            $url = $this->uri($kata);
            $res = $this->actingAs($admin)->get($url);

            $this->assertSame(
                200,
                $res->getStatusCode(),
                "URL {$url} -> status {$res->getStatusCode()}, redirect ke: "
                . ($res->headers->get('Location') ?? '-')
            );
        }
    }

    public function test_role_lain_ditolak_di_halaman_pengguna(): void
    {
        $url = $this->uri(['pengguna', 'users', 'user']);

        foreach ([Role::Dinas, Role::Sekolah] as $role) {
            $this->actingAs($this->akun($role))
                ->get($url)
                ->assertForbidden();
        }
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get($this->uri('dashboard'))->assertRedirect('/login');
    }

    public function test_validasi_role_tidak_valid(): void
    {
        $this->actingAs($this->akun(Role::Admin))
            ->post($this->uri(['pengguna', 'users', 'user'], 'POST'), ['role' => 'ngawur'])
            ->assertSessionHasErrors('role');
    }
}