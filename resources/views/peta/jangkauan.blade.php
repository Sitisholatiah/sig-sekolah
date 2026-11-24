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

    let semua = [];
    let titik = null;   // titik terakhir yang diklik [lat, lng]

    // Data diambil ulang dari server setiap filter jenjang/status berubah
    async function muatData() {
        try {
            semua = await Peta.ambilSekolah(selJenjang.value, selStatus.value);
        } catch (err) {
            console.error(err);
            panel.style.display = 'block';
            panel.textContent = 'Data sekolah tidak dapat dimuat.';
            return false;
        }
        return true;
    }

    function analisis() {
        if (!titik) return;

        const radiusKm = parseFloat(selRadius.value);

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

        // Sekolah di dalam lingkaran: jarak Haversine <= radius
        const dalam = semua
            .map((f) => ({ fitur: f, jarak: Peta.jarakKm(titik, Peta.latlng(f)) }))
            .filter((d) => d.jarak <= radiusKm)
            .sort((a, b) => a.jarak - b.jarak);

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

    [selJenjang, selStatus].forEach((el) => {
        el.addEventListener('change', async () => {
            if (await muatData()) analisis();
        });
    });

    // Klik pada daftar: buka popup sekolah (event delegation)
    panel.addEventListener('click', (e) => {
        const li = e.target.closest('[data-npsn]');
        if (!li) return;

        const f = semua.find((x) => x.properties.npsn === li.dataset.npsn);
        if (f) {
            L.popup().setLatLng(Peta.latlng(f)).setContent(Peta.popup(f)).openOn(map);
        }
    });

    muatData();
</script>
@endpush