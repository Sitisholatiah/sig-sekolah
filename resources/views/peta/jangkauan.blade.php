@extends('layouts.public')
@section('title', 'Analisis Jangkauan')

@section('content')
<div class="toolbar">
    <label>Radius
        <select id="radius">
            <option value="0.5">500 m</option>
            <option value="1" selected>1 km</option>
            <option value="2">2 km</option>
            <option value="5">5 km</option>
        </select>
    </label>
    <label>Jenjang
        <select id="jenjang">
            <option value="">Semua</option>
            <option>SD</option>
            <option>SMP</option>
            <option>SMA</option>
            <option>SMK</option>
        </select>
    </label>
    <label>Status
        <select id="status">
            <option value="">Semua</option>
            <option>Negeri</option>
            <option>Swasta</option>
        </select>
    </label>
    <span>Klik pada peta untuk menganalisis jangkauan.</span>
</div>

<div id="peta" class="peta"></div>

<div id="hasil" class="panel"
     style="top: 64px; right: 12px; width: 320px; max-height: 70%;
            overflow: auto; display: none;"></div>
@endsection

@push('scripts')
<script>
    const map = Peta.buat('peta');
    const lapisan = L.layerGroup().addTo(map);   // lingkaran dan titik klik
    const marker = L.layerGroup().addTo(map);    // marker sekolah dalam radius
    const panel = document.getElementById('hasil');
    const selRadius = document.getElementById('radius');
    const selJenjang = document.getElementById('jenjang');
    const selStatus = document.getElementById('status');

    let titik = null;          // titik terakhir yang diklik [lat, lng]
    let hasilTerakhir = [];    // BARU: fitur sekolah dalam radius (untuk klik daftar)
    let permintaanKe = 0;      // BARU: penanda agar respons lama tidak menimpa yang baru

    // DIHAPUS: variabel "semua" dan fungsi muatData().
    // Penyaringan radius, jenjang, dan status kini dilakukan database (PostGIS).

    async function analisis() {
        if (!titik) return;

        const radiusKm = parseFloat(selRadius.value);
        const nomor = ++permintaanKe;

        // BARU: minta sekolah dalam radius ke server (ST_DWithin), sudah terurut dari terdekat
        const param = new URLSearchParams({
            lat: titik[0],
            lng: titik[1],
            radius: radiusKm * 1000,   // server memakai meter
        });
        if (selJenjang.value) param.set('jenjang', selJenjang.value);
        if (selStatus.value) param.set('status', selStatus.value);

        let fitur;
        try {
            const res = await fetch('/data/sekolah/terdekat?' + param.toString());
            if (!res.ok) throw new Error('HTTP ' + res.status);
            fitur = (await res.json()).features;
        } catch (err) {
            console.error(err);
            panel.style.display = 'block';
            panel.textContent = 'Data sekolah tidak dapat dimuat.';
            return;
        }

        // Abaikan bila sudah ada klik atau filter yang lebih baru
        if (nomor !== permintaanKe) return;

        hasilTerakhir = fitur;

        lapisan.clearLayers();
        marker.clearLayers();

        // L.circle memakai radius dalam METER (berbeda dengan circleMarker: piksel)
        L.circle(titik, {
            radius: radiusKm * 1000,
            color: '#2563eb', weight: 2,
            fillColor: '#3b82f6', fillOpacity: 0.15,
        }).addTo(lapisan);

        L.circleMarker(titik, {
            radius: 8, color: '#fff', weight: 3,
            fillColor: '#ef4444', fillOpacity: 1,
        }).addTo(lapisan);

        // BARU: jarak sudah dihitung PostGIS (meter), diubah ke km untuk tampilan
        const dalam = fitur.map((f) => ({
            fitur: f,
            jarak: f.properties.jarak_m / 1000,
        }));

        dalam.forEach((d) => {
            L.circleMarker(Peta.latlng(d.fitur), {
                radius: 7, color: '#fff', weight: 2,
                fillColor: '#16a34a', fillOpacity: 0.95,
            }).bindPopup(Peta.popup(d.fitur)).addTo(marker);
        });

        // Rekap per jenjang
        const perJenjang = {};
        dalam.forEach((d) => {
            const j = d.fitur.properties.jenjang || '-';
            perJenjang[j] = (perJenjang[j] || 0) + 1;
        });
        const rekap = Object.entries(perJenjang)
            .map(([j, n]) => `<li>${Peta.esc(j)}: <strong>${n}</strong></li>`)
            .join('');

        const daftar = dalam.map((d) => `
            <li data-npsn="${Peta.esc(d.fitur.properties.npsn)}"
                style="padding: 6px 0; border-top: 1px solid #e5e7eb; cursor: pointer;">
                <strong>${Peta.esc(d.fitur.properties.nama)}</strong><br>
                <small>${Peta.esc(d.fitur.properties.jenjang)}
                    - ${d.jarak.toFixed(2)} km</small>
            </li>`).join('');

        panel.style.display = 'block';
        panel.innerHTML = `
            <strong>Titik</strong>
            <div style="font-family: monospace;">
                ${titik[0].toFixed(6)}, ${titik[1].toFixed(6)}
            </div>
            <div style="margin-top: 8px; font-size: 18px;">
                <strong>${dalam.length}</strong> sekolah dalam radius
                ${radiusKm} km
            </div>
            ${dalam.length === 0
                ? '<p><em>Tidak ada sekolah dalam jangkauan.</em></p>'
                : `<ul style="margin: 6px 0; padding-left: 18px;">${rekap}</ul>
                   <ul style="list-style: none; margin: 4px 0 0; padding: 0;">${daftar}</ul>`}`;

        map.fitBounds(L.circle(titik, { radius: radiusKm * 1000 }).getBounds(),
                      { padding: [30, 30] });
    }

    map.on('click', (e) => {
        titik = [e.latlng.lat, e.latlng.lng];
        analisis();
    });

    selRadius.addEventListener('change', analisis);

    // BARU: ganti filter cukup memanggil analisis() lagi, tanpa muat ulang semua data
    [selJenjang, selStatus].forEach((el) => {
        el.addEventListener('change', analisis);
    });

    // Klik pada daftar: buka popup sekolah (event delegation)
    panel.addEventListener('click', (e) => {
        const li = e.target.closest('[data-npsn]');
        if (!li) return;

        const f = hasilTerakhir.find((x) => x.properties.npsn === li.dataset.npsn);
        if (f) {
            L.popup().setLatLng(Peta.latlng(f)).setContent(Peta.popup(f)).openOn(map);
        }
    });

    // DIHAPUS: muatData() di akhir skrip tidak diperlukan lagi
</script>
@endpush