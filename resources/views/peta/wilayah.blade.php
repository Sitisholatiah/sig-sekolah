@extends('layouts.public')
@section('title', 'Wilayah')
 
@section('content')
<div class="toolbar">
    <label>Jenjang
        <select id="filter-jenjang">
            <option value="">Semua jenjang</option>
            <option value="SD">SD</option>
            <option value="SMP">SMP</option>
            <option value="SMA">SMA</option>
            <option value="SMK">SMK</option>
        </select>
    </label>
    <span id="jumlah"></span>
</div>
 
<div id="peta" class="peta"></div>
 
<div class="panel" style="right: 12px; bottom: 24px; font-size: 13px;">
    <strong>Jumlah sekolah</strong>
    <div><span style="color: #dc2626;">&#9632;</span> Terbanyak</div>
    <div><span style="color: #ea580c;">&#9632;</span> Banyak</div>
    <div><span style="color: #f59e0b;">&#9632;</span> Sedang</div>
    <div><span style="color: #3b82f6;">&#9632;</span> Sedikit</div>
    <small style="color: #6b7280;">Relatif terhadap kecamatan terbanyak</small>
</div>
@endsection
 
@push('scripts')
<script>
    const map = Peta.buat('peta');
    let lapisan = null;

    // BARU: berkas batas kecamatan resmi dan kandidat nama kolom kecamatan
    const URL_BATAS = "{{ asset('data/kecamatan.geojson') }}";
    const KOLOM_NAMA = ['kecamatan', 'nama_kecamatan', 'NAMOBJ', 'WADMKC',
                        'nm_kecamatan', 'KECAMATAN', 'Kecamatan', 'NAME_3',
                        'nama', 'name'];
    let batas = null;

    // BARU: ambil nama kecamatan dari kolom yang tersedia
    function namaKecamatan(props) {
        for (const k of KOLOM_NAMA) {
            if (props[k]) return String(props[k]);
        }
        return 'Tanpa nama';
    }

    // BARU: uji titik di dalam poligon (ray casting), koordinat [lng, lat]
    function dalamCincin(pt, cincin) {
        const [x, y] = pt;
        let dalam = false;
        for (let i = 0, j = cincin.length - 1; i < cincin.length; j = i++) {
            const [xi, yi] = cincin[i];
            const [xj, yj] = cincin[j];
            if ((yi > y) !== (yj > y)
                && x < ((xj - xi) * (y - yi)) / (yj - yi) + xi) {
                dalam = !dalam;
            }
        }
        return dalam;
    }

    function dalamPoligon(pt, cincinCincin) {
        if (!dalamCincin(pt, cincinCincin[0])) return false;
        return !cincinCincin.slice(1).some((lubang) => dalamCincin(pt, lubang));
    }

    function dalamGeometri(pt, g) {
        if (g.type === 'Polygon') return dalamPoligon(pt, g.coordinates);
        if (g.type === 'MultiPolygon') {
            return g.coordinates.some((p) => dalamPoligon(pt, p));
        }
        return false;
    }

    // BARU: isi properties kecamatan, jumlah_sekolah, jumlah_siswa
    // sehingga bentuknya sama dengan data endpoint yang lama
    function hitung(sekolah) {
        batas.features.forEach((f) => {
            f.properties.kecamatan = namaKecamatan(f.properties);
            f.properties.jumlah_sekolah = 0;
            f.properties.jumlah_siswa = 0;
        });

        sekolah.forEach((s) => {
            const kec = batas.features.find(
                (f) => dalamGeometri(s.geometry.coordinates, f.geometry));
            if (!kec) return;

            kec.properties.jumlah_sekolah += 1;
            kec.properties.jumlah_siswa += Number(s.properties.jumlah_siswa) || 0;
        });
    }
 
    function warna(rasio) {
        if (rasio >= 0.8) return '#dc2626';
        if (rasio >= 0.6) return '#ea580c';
        if (rasio >= 0.4) return '#f59e0b';
        return '#3b82f6';
    }
 
    async function muat(jenjang = '') {
        // BARU: pengganti fetch(PETA_URL.wilayah)
        if (!batas) {
            batas = await (await fetch(URL_BATAS)).json();
        }
        const sekolah = await Peta.ambilSekolah(jenjang);
        hitung(sekolah);
        const data = batas;
 
        if (lapisan) {
            map.removeLayer(lapisan);
        }
 
        const maks = Math.max(1, ...data.features.map((f) => f.properties.jumlah_sekolah));
 
        lapisan = L.geoJSON(data, {
            style: (f) => {
                const w = warna(f.properties.jumlah_sekolah / maks);
                return { color: w, fillColor: w, fillOpacity: 0.3, weight: 3 };
            },
            onEachFeature: (f, layer) => {
                const p = f.properties;
                layer.bindTooltip(Peta.esc(p.kecamatan), { sticky: true });
                layer.bindPopup(`
                    <strong>${Peta.esc(p.kecamatan)}</strong><br>
                    Jumlah sekolah: ${p.jumlah_sekolah}<br>
                    Jumlah siswa: ${p.jumlah_siswa}
                `);
            },
        }).addTo(map);
 
        document.getElementById('jumlah').textContent =
            data.features.length + ' wilayah';
 
        if (data.features.length > 0) {
            map.fitBounds(lapisan.getBounds().pad(0.1));
        }
    }
 
    document.getElementById('filter-jenjang')
        .addEventListener('change', (e) => muat(e.target.value));
 
    muat();
</script>
@endpush