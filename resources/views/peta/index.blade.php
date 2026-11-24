@extends('layouts.public')
@section('title', 'Peta Sekolah')
 
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
    {{-- TAMBAHAN: dropdown filter status --}}
    <label>Status
        <select id="filter-status">
            <option value="">Semua status</option>
            <option value="Negeri">Negeri</option>
            <option value="Swasta">Swasta</option>
        </select>
    </label>
    <span id="jumlah"></span>
    {{-- TAMBAHAN: tombol Unduh GeoJSON --}}
    <button type="button" id="unduh-geojson">Unduh GeoJSON</button>
    <span id="pesan-unduh" style="color: #6b7280;"></span>
</div>
 
<div id="peta" class="peta"></div>
@endsection
 
@push('scripts')
<script>
    const map = Peta.buat('peta');
    const lapisan = L.layerGroup().addTo(map);

    // TAMBAHAN (bounding box): marker disimpan per NPSN agar tidak digambar ulang
    // seluruhnya setiap peta digeser (popup yang terbuka tidak tertutup)
    const markerAktif = new Map();
    const BATAS = 5000;   // samakan dengan limit() pada controller
    let nomorMuat = 0;    // untuk mengabaikan respons lama
 
    async function muat(jenjang = '') {
        const saya = ++nomorMuat;

        try {
            // TAMBAHAN: baca pilihan status
            const status = document.getElementById('filter-status').value;

            // TAMBAHAN (bounding box): hanya area peta yang sedang terlihat
            const b = map.getBounds().pad(0.1);
            const bbox = [b.getWest(), b.getSouth(), b.getEast(), b.getNorth()].join(',');

            // DIUBAH: ditambah argumen status dan bbox
            const fitur = await Peta.ambilSekolah(jenjang, status, bbox);

            // Permintaan yang lebih baru sudah berjalan: abaikan hasil ini
            if (saya !== nomorMuat) return;

            // TAMBAHAN (bounding box): buang marker di luar area, tambah yang baru
            const npsnBaru = new Set(fitur.map((f) => f.properties.npsn));

            markerAktif.forEach((marker, npsn) => {
                if (!npsnBaru.has(npsn)) {
                    lapisan.removeLayer(marker);
                    markerAktif.delete(npsn);
                }
            });

            fitur.forEach((f) => {
                if (markerAktif.has(f.properties.npsn)) return;

                const marker = L.marker(Peta.latlng(f))
                    .bindPopup(Peta.popup(f))
                    .addTo(lapisan);
                markerAktif.set(f.properties.npsn, marker);
            });
 
            document.getElementById('jumlah').textContent =
                fitur.length + ' sekolah di area ini'
                + (fitur.length >= BATAS ? ' (dibatasi, perbesar peta)' : '');

            // DIHAPUS: fitBounds ke seluruh data. Dengan bounding box, memperbesar
            // peta ke hasil akan memicu muat ulang tanpa henti.
        } catch (e) {
            document.getElementById('jumlah').textContent =
                'Gagal memuat data';
        }
    }
 
    document.getElementById('filter-jenjang')
        .addEventListener('change', (e) => muat(e.target.value));

    // TAMBAHAN: muat ulang saat status diganti (jenjang tetap dipakai)
    document.getElementById('filter-status')
        .addEventListener('change', () =>
            muat(document.getElementById('filter-jenjang').value));

    // TAMBAHAN (bounding box): muat ulang setelah peta digeser atau di-zoom
    let tundaGeser = null;
    map.on('moveend', () => {
        clearTimeout(tundaGeser);
        tundaGeser = setTimeout(
            () => muat(document.getElementById('filter-jenjang').value), 300);
    });
 
    muat();

    // TAMBAHAN: tombol Unduh GeoJSON (sesuai filter yang sedang dipilih)
    document.getElementById('unduh-geojson').addEventListener('click', async () => {
        const pesan = document.getElementById('pesan-unduh');
        const jenjang = document.getElementById('filter-jenjang').value;
        const status = document.getElementById('filter-status').value;

        try {
            // Tanpa bbox: yang diunduh semua sekolah sesuai filter, bukan hanya area terlihat
            const fitur = await Peta.ambilSekolah(jenjang, status);

            if (fitur.length === 0) {
                pesan.textContent = 'Tidak ada data untuk diunduh.';
                return;
            }

            const data = { type: 'FeatureCollection', features: fitur };
            const blob = new Blob([JSON.stringify(data, null, 2)],
                                  { type: 'application/geo+json' });

            const nama = ['sekolah', jenjang, status,
                          new Date().toISOString().slice(0, 10)]
                .filter(Boolean).join('-').toLowerCase() + '.geojson';

            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = nama;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            pesan.textContent = fitur.length + ' sekolah diunduh (' + nama + ').';
        } catch (err) {
            console.error(err);
            pesan.textContent = 'Gagal mengunduh data.';
        }
    });
</script>
@endpush