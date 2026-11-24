@extends('layouts.public')
@section('title', 'Peta Gabungan')

@push('styles')
<link rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css">
<link rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css">
@endpush

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
    <label>Status
        <select id="filter-status">
            <option value="">Semua status</option>
            <option value="Negeri">Negeri</option>
            <option value="Swasta">Swasta</option>
        </select>
    </label>
    <span id="jumlah"></span>
    <span style="color: #6b7280;">
        Gunakan kotak di kanan atas peta untuk menghidupkan atau mematikan lapisan.
    </span>
</div>

<div id="peta" class="peta"></div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script>
    const map = Peta.buat('peta');

    // Tiga lapisan yang berdiri sendiri
    const lapMarker = L.layerGroup();
    const lapKlaster = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 60,
        spiderfyOnMaxZoom: true,
        disableClusteringAtZoom: 17,
        chunkedLoading: true,
    });
    const lapPanas = L.heatLayer([], { radius: 25, blur: 15, maxZoom: 17 });

    // Hidup saat dibuka: klaster dan peta panas. Marker dimatikan agar tidak menumpuk.
    lapKlaster.addTo(map);
    lapPanas.addTo(map);

    // Kontrol lapisan: kotak centang untuk menghidupkan atau mematikan tiap lapisan
    L.control.layers(null, {
        'Marker': lapMarker,
        'Klaster': lapKlaster,
        'Peta Panas': lapPanas,
    }, { collapsed: false }).addTo(map);

    async function muat() {
        try {
            const fitur = await Peta.ambilSekolah(
                document.getElementById('filter-jenjang').value,
                document.getElementById('filter-status').value
            );

            // Isi ulang ketiga lapisan. Status hidup/mati tiap lapisan tetap terjaga.
            lapMarker.clearLayers();
            lapKlaster.clearLayers();

            fitur.forEach((f) => {
                L.marker(Peta.latlng(f))
                    .bindPopup(Peta.popup(f))
                    .addTo(lapMarker);
            });

            lapKlaster.addLayers(fitur.map((f) =>
                L.marker(Peta.latlng(f)).bindPopup(Peta.popup(f))
            ));

            lapPanas.setLatLngs(fitur.map(Peta.latlng));

            document.getElementById('jumlah').textContent =
                fitur.length + ' sekolah';

            if (fitur.length > 0) {
                map.fitBounds(L.latLngBounds(fitur.map(Peta.latlng)).pad(0.1));
            }
        } catch (e) {
            document.getElementById('jumlah').textContent = 'Gagal memuat data';
        }
    }

    ['filter-jenjang', 'filter-status'].forEach((id) =>
        document.getElementById(id).addEventListener('change', muat));

    muat();
</script>
@endpush