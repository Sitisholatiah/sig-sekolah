<?php

namespace Tests\Feature;

use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;          // BARU
use Illuminate\Support\Facades\Storage;    // BARU
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function sekolah(string $npsn): Sekolah
    {
        return Sekolah::create([
            'npsn' => $npsn, 'nama' => "Sekolah $npsn",
            'jenjang' => 'SD', 'status' => 'Negeri',
            'alamat' => 'Jl. Uji', 'kecamatan' => 'Uji',
            'latitude' => -3.3, 'longitude' => 114.59,
        ]);
    }

    private function user(string $role, ?Sekolah $sekolah = null): User
    {
        return User::factory()->create([
            'role' => $role,
            'sekolah_id' => $sekolah?->id,
            'is_active' => true,
        ]);
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_gagal_dan_berhasil(): void
    {
        $u = $this->user('dinas');

        $this->post('/login', ['email' => $u->email, 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->post('/login', ['email' => $u->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($u);
    }

    public function test_admin_hanya_mengelola_pengguna(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/sekolah')->assertForbidden();
    }

    public function test_admin_menambah_pengguna_sekolah(): void
    {
        $sekolah = $this->sekolah('11111111');

        $this->actingAs($this->user('admin'))->post('/admin/users', [
            'name' => 'Operator', 'email' => 'op@uji.test',
            'role' => 'sekolah', 'sekolah_id' => $sekolah->id,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'is_active' => '1',
        ])->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'email' => 'op@uji.test', 'role' => 'sekolah',
            'sekolah_id' => $sekolah->id,
        ]);
    }

    public function test_role_sekolah_wajib_memilih_sekolah(): void
    {
        $this->actingAs($this->user('admin'))->post('/admin/users', [
            'name' => 'Operator', 'email' => 'op@uji.test',
            'role' => 'sekolah',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'is_active' => '1',
        ])->assertSessionHasErrors('sekolah_id');
    }

    public function test_dinas_mengelola_semua_sekolah(): void
    {
        $a = $this->sekolah('11111111');
        $dinas = $this->user('dinas');

        $this->actingAs($dinas)->get('/admin/sekolah')->assertOk();
        $this->actingAs($dinas)->get("/sekolah/{$a->id}/edit")->assertOk();
        $this->actingAs($dinas)->get("/sekolah/{$a->id}/profil")->assertOk();
        $this->actingAs($dinas)->get('/admin/users')->assertForbidden();
    }

    public function test_sekolah_hanya_mengakses_sekolah_sendiri(): void
    {
        $a = $this->sekolah('11111111');
        $b = $this->sekolah('22222222');
        $op = $this->user('sekolah', $a);

        $this->actingAs($op)->get("/sekolah/{$a->id}/edit")->assertOk();
        $this->actingAs($op)->get("/sekolah/{$b->id}/edit")->assertForbidden();
        $this->actingAs($op)->get("/sekolah/{$b->id}/profil")->assertForbidden();
        $this->actingAs($op)->get('/admin/sekolah')->assertForbidden();
        $this->actingAs($op)->get('/admin/users')->assertForbidden();
    }

    // UBAH: koordinat digeser kecil (±50 m) agar lolos batas jarak
    public function test_sekolah_tidak_dapat_mengubah_npsn(): void
    {
        $a = $this->sekolah('11111111');
        $op = $this->user('sekolah', $a);

        $this->actingAs($op)->put("/sekolah/{$a->id}", [
            'npsn' => '99999999', 'nama' => 'Nama Baru',
            'jenjang' => 'SD', 'status' => 'Negeri',
            'alamat' => 'Jl. Baru', 'kecamatan' => 'Uji',
            'latitude' => -3.30045, 'longitude' => 114.59,
        ])->assertRedirect();

        $a->refresh();
        $this->assertSame('11111111', $a->npsn);
        $this->assertSame('Nama Baru', $a->nama);
    }

    public function test_sekolah_menyimpan_profil_sendiri(): void
    {
        $a = $this->sekolah('11111111');
        $op = $this->user('sekolah', $a);

        $this->actingAs($op)->put("/sekolah/{$a->id}/profil", [
            'kepala_sekolah' => 'Bapak Uji', 'akreditasi' => 'A',
        ])->assertRedirect();

        $this->assertDatabaseHas('profil_sekolahs', [
            'sekolah_id' => $a->id, 'kepala_sekolah' => 'Bapak Uji',
        ]);
    }

    public function test_sekolah_tidak_dapat_menghapus_sekolah(): void
    {
        $a = $this->sekolah('11111111');
        $op = $this->user('sekolah', $a);

        $this->actingAs($op)->delete("/admin/sekolah/{$a->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('sekolahs', ['id' => $a->id]);
    }

    // ===== Pengujian fitur is_active =====

    public function test_akun_nonaktif_tidak_dapat_login(): void
    {
        $u = $this->user('dinas');
        $u->update(['is_active' => false]);

        $this->post('/login', ['email' => $u->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_menonaktifkan_pengguna_lewat_form(): void
    {
        $admin = $this->user('admin');
        $target = $this->user('dinas');

        $this->actingAs($admin)->put("/admin/users/{$target->id}", [
            'name' => $target->name, 'email' => $target->email,
            'role' => 'dinas', 'is_active' => '0',
        ])->assertRedirect('/admin/users');

        $this->assertFalse($target->fresh()->is_active);
    }

    public function test_admin_tidak_dapat_menonaktifkan_diri_sendiri(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email,
            'role' => 'admin', 'is_active' => '0',
        ])->assertSessionHasErrors('is_active');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_toggle_status_pengguna(): void
    {
        $admin = $this->user('admin');
        $target = $this->user('dinas');

        $this->actingAs($admin)
            ->patch("/admin/users/{$target->id}/aktif")
            ->assertRedirect();
        $this->assertFalse($target->fresh()->is_active);

        $this->actingAs($admin)
            ->patch("/admin/users/{$target->id}/aktif")
            ->assertRedirect();
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_admin_tidak_dapat_toggle_akun_sendiri(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->patch("/admin/users/{$admin->id}/aktif")
            ->assertStatus(422);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_non_admin_tidak_dapat_toggle_pengguna(): void
    {
        $dinas = $this->user('dinas');
        $target = $this->user('sekolah');

        $this->actingAs($dinas)
            ->patch("/admin/users/{$target->id}/aktif")
            ->assertForbidden();

        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_akun_dinonaktifkan_saat_login_langsung_dikeluarkan(): void
    {
        $u = $this->user('dinas');
        $this->actingAs($u)->get('/dashboard')->assertOk();

        $u->update(['is_active' => false]);

        $this->actingAs($u->fresh())->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    // ===== Pengujian log aktivitas =====

    public function test_perubahan_data_sekolah_tercatat_di_log(): void
    {
        $a = $this->sekolah('11111111');
        $op = $this->user('sekolah', $a);

        $this->actingAs($op)->put("/sekolah/{$a->id}", [
            'npsn' => '11111111', 'nama' => 'Nama Baru',
            'jenjang' => 'SD', 'status' => 'Negeri',
            'alamat' => 'Jl. Uji', 'kecamatan' => 'Uji',
            'latitude' => -3.3, 'longitude' => 114.59,
        ])->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $op->id,
            'aksi' => 'ubah',
            'sekolah_id' => $a->id,
        ]);
    }

    public function test_hanya_admin_melihat_log_aktivitas(): void
    {
        $this->actingAs($this->user('admin'))
            ->get('/admin/log-aktivitas')->assertOk();
        $this->actingAs($this->user('dinas'))
            ->get('/admin/log-aktivitas')->assertForbidden();
    }

    // ===== Pengujian batas geser koordinat =====

    public function test_sekolah_tidak_dapat_menggeser_koordinat_terlalu_jauh(): void
    {
        $a = $this->sekolah('11111111'); // -3.3, 114.59
        $op = $this->user('sekolah', $a);
        $payload = [
            'npsn' => '11111111', 'nama' => $a->nama, 'jenjang' => 'SD',
            'status' => 'Negeri', 'alamat' => 'Jl. Uji', 'kecamatan' => 'Uji',
        ];

        // ±5 km: ditolak
        $this->actingAs($op)->put("/sekolah/{$a->id}", $payload + [
            'latitude' => -3.35, 'longitude' => 114.59,
        ])->assertSessionHasErrors('latitude');

        $this->assertEquals(-3.3, $a->fresh()->latitude);

        // ±50 m: diterima
        $this->actingAs($op)->put("/sekolah/{$a->id}", $payload + [
            'latitude' => -3.30045, 'longitude' => 114.59,
        ])->assertSessionDoesntHaveErrors();

        $this->assertEquals(-3.30045, $a->fresh()->latitude);
    }

    public function test_dinas_bebas_memindahkan_koordinat(): void
    {
        $a = $this->sekolah('11111111');

        $this->actingAs($this->user('dinas'))->put("/sekolah/{$a->id}", [
            'npsn' => '11111111', 'nama' => $a->nama, 'jenjang' => 'SD',
            'status' => 'Negeri', 'alamat' => 'Jl. Uji', 'kecamatan' => 'Uji',
            'latitude' => -3.5, 'longitude' => 114.7,
        ])->assertSessionDoesntHaveErrors();

        $this->assertEquals(-3.5, $a->fresh()->latitude);
    }

    // ===== BARU: Pengujian unggah logo =====

    public function test_sekolah_mengunggah_logo(): void
    {
        Storage::fake('public');
        $a = $this->sekolah('11111111');
        $op = $this->user('sekolah', $a);

        $this->actingAs($op)->put("/sekolah/{$a->id}/profil", [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertRedirect();

        $path = $a->fresh()->profil->logo_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_logo_bukan_gambar_ditolak(): void
    {
        Storage::fake('public');
        $a = $this->sekolah('11111111');
        $op = $this->user('sekolah', $a);

        $this->actingAs($op)->put("/sekolah/{$a->id}/profil", [
            'logo' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('logo');
    }
}