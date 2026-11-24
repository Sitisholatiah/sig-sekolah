const Peta = {
    // Pusat Kota Banjarmasin, urutan [latitude, longitude]
    pusat: [-3.3194, 114.5908],

    buat(idElemen, zoom = 12) {
        const map = L.map(idElemen).setView(Peta.pusat, zoom);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);

        return map;
    },

    // Mencegah teks dari database dibaca sebagai HTML (XSS)
    esc(teks) {
        const d = document.createElement('div');
        d.textContent = teks ?? '';
        return d.innerHTML;
    },

    // Mengambil fitur (Feature) sekolah dari endpoint GeoJSON.
    // jenjang, status, bbox ("barat,selatan,timur,utara") bersifat opsional.
    async ambilSekolah(jenjang = '', status = '', bbox = '') {
        const p = new URLSearchParams();
        if (jenjang) p.set('jenjang', jenjang);
        if (status) p.set('status', status);
        if (bbox) p.set('bbox', bbox);

        const respon = await fetch(`${window.PETA_URL.data}?${p.toString()}`);

        if (!respon.ok) {
            throw new Error('Gagal memuat data sekolah');
        }

        const data = await respon.json();
        return data.features;
    },

    // GeoJSON [lng, lat] menjadi Leaflet [lat, lng]
    latlng(fitur) {
        const [lng, lat] = fitur.geometry.coordinates;
        return [lat, lng];
    },

    popup(fitur) {
        const p = fitur.properties;
        const [lat, lng] = Peta.latlng(fitur);

        return `
            <strong>${Peta.esc(p.nama)}</strong><br>
            NPSN: ${Peta.esc(p.npsn)}<br>
            ${Peta.esc(p.jenjang)} - ${Peta.esc(p.status)}<br>
            ${Peta.esc(p.alamat)}, ${Peta.esc(p.kecamatan)}<br>
            Telp: ${Peta.esc(p.telepon) || '-'}<br>
            Kepala sekolah: ${Peta.esc(p.kepala_sekolah) || '-'}<br>
            Akreditasi: ${Peta.esc(p.akreditasi) || '-'}<br>
            Siswa: ${p.jumlah_siswa ?? '-'} | Guru: ${p.jumlah_guru ?? '-'}<br>
            <small>Koordinat: ${lat}, ${lng}</small>
        `;
    },

    // Jarak garis lurus (km) antara dua titik [lat, lng], rumus Haversine
    jarakKm(a, b) {
        const R = 6371;
        const rad = (x) => (x * Math.PI) / 180;
        const dLat = rad(b[0] - a[0]);
        const dLng = rad(b[1] - a[1]);
        const h = Math.sin(dLat / 2) ** 2 +
            Math.cos(rad(a[0])) * Math.cos(rad(b[0])) * Math.sin(dLng / 2) ** 2;

        return 2 * R * Math.asin(Math.sqrt(h));
    },

    // Penanda bulat berisi angka (dipakai pada menu Rute)
    ikonAngka(angka, warna) {
        return L.divIcon({
            className: '',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            html: `<div style="background:${warna};width:30px;height:30px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 4px rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;font-size:13px">${angka}</div>`,
        });
    },

    // Menunda pemanggilan fungsi sampai tidak ada panggilan baru selama `ms`
    debounce(fn, ms = 300) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), ms);
        };
    },

    // Mengunduh array fitur sebagai berkas GeoJSON
    unduhGeoJSON(fitur, nama = 'sekolah.geojson') {
        const data = { type: 'FeatureCollection', features: fitur };
        const blob = new Blob([JSON.stringify(data, null, 2)],
            { type: 'application/geo+json' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = nama;
        document.body.appendChild(a);
        a.click();
        a.remove();
        // Ditunda agar unduhan sempat dimulai sebelum tautan dicabut
        setTimeout(() => URL.revokeObjectURL(a.href), 1000);
    },
};