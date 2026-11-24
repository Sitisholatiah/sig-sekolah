<?php

namespace Tests\Feature;

use App\Models\Sekolah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilSekolahPublikTest extends TestCase
{
    use RefreshDatabase;

    private function buatSekolah(array $attr = []): Sekolah
    {
        return Sekolah::forceCreate(array_merge([
            'npsn'      => '20123456',
            'nama'      => 'SDN Uji Satu',
            'jenjang'   => 'SD',
            'status'    => 'Negeri',
            'alamat'    => 'Jl. Uji No. 1',
            'kecamatan' => 'Kecamatan Uji',
            'telepon'   => '0511123456',
            'latitude'  => -3.3194,
            'longitude' => 114.5908,
        ], $attr));
    }

    public function test_tamu_bisa_membuka_profil_sekolah(): void
    {
        $sekolah = $this->buatSekolah();

        $this->get(route('publik.sekolah.show', ['sekolah' => $sekolah->npsn]))
            ->assertOk()
            ->assertSee('SDN Uji Satu');
    }

    public function test_sekolah_tidak_ada_menghasilkan_404(): void
    {
        $this->get('/profil-sekolah/00000000')->assertNotFound();
    }
}