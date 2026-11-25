<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use Illuminate\Http\Request;
use App\Support\Geometri;

class PetaController extends Controller
{
    public function index()
    {
        return view('peta.index');
    }

    public function cari()
    {
        return view('peta.cari');
    }

    public function klaster()
    {
        return view('peta.klaster');
    }

    public function panas()
    {
        return view('peta.panas');
    }

    public function wilayah()
    {
        return view('peta.wilayah');
    }

    public function rute()
    {
        return view('peta.rute');
    }

    public function alamat()
    {
        return view('peta.alamat');
    }

    // TAMBAHAN: menu Gabungan (marker, klaster, dan peta panas dalam satu peta)
    public function gabungan()
    {
        return view('peta.gabungan');
    }

    public function jangkauan()
    {
        return view('peta.jangkauan');
    }

    // DIPERBARUI: sekolah dalam radius tertentu memakai PostGIS (pengganti Haversine di browser)
    // Kini mendukung filter jenjang dan status, serta properti lengkap untuk Peta.popup()
    public function terdekat(Request $request)
    {
        $data = $request->validate([
            'lat'     => ['required', 'numeric', 'between:-90,90'],
            'lng'     => ['required', 'numeric', 'between:-180,180'],
            'radius'  => ['nullable', 'numeric', 'min:1', 'max:5000'], // meter
            'jenjang' => ['nullable', 'string'],
            'status'  => ['nullable', 'string'],
        ]);

        $radius = $data['radius'] ?? 1000;
        $titik  = 'extensions.ST_SetSRID(extensions.ST_MakePoint(CAST(? AS float8), CAST(? AS float8)), 4326)::extensions.geography';

        $features = Sekolah::with('profil')
            ->selectRaw(
                "sekolahs.*, extensions.ST_Distance(lokasi, $titik) AS jarak_m",
                [$data['lng'], $data['lat']]
            )
            ->whereRaw(
                "extensions.ST_DWithin(lokasi, $titik, CAST(? AS float8))",
                [$data['lng'], $data['lat'], $radius]
            )
            ->when($data['jenjang'] ?? null, fn ($q, $j) => $q->where('jenjang', $j))
            ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('jarak_m')
            ->get()
            ->map(fn ($s) => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$s->longitude, $s->latitude],
                ],
                'properties' => [
                    'npsn'           => $s->npsn,
                    'nama'           => $s->nama,
                    'jenjang'        => $s->jenjang,
                    'status'         => $s->status,
                    'alamat'         => $s->alamat,
                    'kecamatan'      => $s->kecamatan,
                    'telepon'        => $s->telepon,
                    'kepala_sekolah' => $s->profil?->kepala_sekolah,
                    'akreditasi'     => $s->profil?->akreditasi,
                    'jumlah_siswa'   => $s->profil?->jumlah_siswa,
                    'jumlah_guru'    => $s->profil?->jumlah_guru,
                    'logo_url'       => $s->profil?->logoUrl(),
                    'url'            => route('publik.sekolah.show', ['sekolah' => $s->npsn]),
                    'jarak_m'        => round($s->jarak_m),
                ],
            ]);

        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function geojson(Request $request)
    {
        $features = Sekolah::with('profil')
            ->when($request->jenjang, fn ($q, $j) => $q->where('jenjang', $j))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            // TAMBAHAN: bounding box, format bbox=barat,selatan,timur,utara
            ->when($request->bbox, function ($q, $bbox) {
                $p = array_map('floatval', explode(',', $bbox));

                if (count($p) === 4) {
                    [$barat, $selatan, $timur, $utara] = $p;

                    $q->whereBetween('longitude', [$barat, $timur])
                      ->whereBetween('latitude', [$selatan, $utara])
                      ->limit(5000); // pengaman bila area terlalu luas
                }
            })
            ->get()
            ->map(fn ($s) => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$s->longitude, $s->latitude],
                ],
                'properties' => [
                    'npsn'           => $s->npsn,
                    'nama'           => $s->nama,
                    'jenjang'        => $s->jenjang,
                    'status'         => $s->status,
                    'alamat'         => $s->alamat,
                    'kecamatan'      => $s->kecamatan,
                    'telepon'        => $s->telepon,
                    'kepala_sekolah' => $s->profil?->kepala_sekolah,
                    'akreditasi'     => $s->profil?->akreditasi,
                    'jumlah_siswa'   => $s->profil?->jumlah_siswa,
                    'jumlah_guru'    => $s->profil?->jumlah_guru,
                    'logo_url'       => $s->profil?->logoUrl(),
                    'url'            => route('publik.sekolah.show', ['sekolah' => $s->npsn]),
                ],
            ]);

        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function geojsonWilayah(Request $request)
    {
        $features = Sekolah::with('profil')
            ->when($request->jenjang, fn ($q, $j) => $q->where('jenjang', $j))
            ->get()
            ->groupBy('kecamatan')
            ->map(function ($kelompok, $kecamatan) {
                $titik = $kelompok
                    ->map(fn ($s) => [$s->longitude, $s->latitude])
                    ->all();

                $hull = Geometri::convexHull($titik);

                // Poligon memerlukan minimal 3 titik yang tidak segaris
                if (count($hull) < 3) {
                    return null;
                }

                $hull[] = $hull[0]; // GeoJSON: cincin poligon harus tertutup

                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [$hull],
                    ],
                    'properties' => [
                        'kecamatan'      => $kecamatan,
                        'jumlah_sekolah' => $kelompok->count(),
                        'jumlah_siswa'   => (int) $kelompok->sum(
                            fn ($s) => $s->profil?->jumlah_siswa ?? 0
                        ),
                    ],
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}