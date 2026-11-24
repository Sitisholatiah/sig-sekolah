@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
    <a href="{{ url()->previous() === url()->current() ? route('home') : url()->previous() }}"
       class="text-blue-700 text-sm">&larr; Kembali ke peta</a>

    <div class="bg-white rounded-lg shadow p-6 mt-4">
        <div class="flex items-center gap-4">
            @if ($sekolah->logo)
                <img src="{{ asset('storage/' . $sekolah->logo) }}"
                     alt="Logo {{ $sekolah->nama }}" class="h-20 w-20 object-contain">
            @endif
            <div>
                <h1 class="text-2xl font-bold">{{ $sekolah->nama }}</h1>
                <p class="text-gray-600">NPSN: {{ $sekolah->npsn }}</p>
            </div>
        </div>

        <dl class="mt-6 space-y-2">
            <div><dt class="font-semibold">Alamat</dt><dd>{{ $sekolah->alamat }}</dd></div>
            <div><dt class="font-semibold">Koordinat</dt>
                 <dd>{{ $sekolah->latitude }}, {{ $sekolah->longitude }}</dd></div>
        </dl>

        <div id="mini-peta" class="h-64 rounded mt-6"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const pos = [{{ $sekolah->latitude }}, {{ $sekolah->longitude }}];
        const map = L.map('mini-peta').setView(pos, 15);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);
        L.marker(pos).addTo(map);
    });
</script>
@endsection