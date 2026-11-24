@extends('layouts.app')
@section('title', 'Log Aktivitas')

@section('content')
<h1 class="text-2xl font-bold mb-4">Log Aktivitas</h1>

<form method="GET" class="mb-4 flex gap-2">
    <select name="aksi" class="border rounded px-3 py-2">
        <option value="">Semua aksi</option>
        @foreach (['tambah' => 'Tambah', 'ubah' => 'Ubah', 'hapus' => 'Hapus'] as $nilai => $label)
            <option value="{{ $nilai }}" @selected(request('aksi') === $nilai)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <button class="px-4 py-2 bg-gray-700 text-white rounded">Filter</button>
</form>

<div class="bg-white rounded shadow overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-gray-200 text-left">
        <tr>
            <th class="p-3">Waktu</th>
            <th class="p-3">Pengguna</th>
            <th class="p-3">Aksi</th>
            <th class="p-3">Sekolah</th>
            <th class="p-3">Perubahan</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($logs as $log)
        <tr class="border-t align-top">
            <td class="p-3 whitespace-nowrap">{{ $log->created_at->format('d-m-Y H:i') }}</td>
            <td class="p-3">
                {{ $log->user?->name ?? 'Sistem' }}
                @if ($log->user)
                    <span class="text-gray-500">({{ $log->user->roleLabel() }})</span>
                @endif
            </td>
            <td class="p-3">{{ ucfirst($log->aksi) }} {{ $log->objek }}</td>
            <td class="p-3">{{ $log->sekolah_nama }}</td>
            <td class="p-3">
                @forelse ($log->perubahan ?? [] as $kolom => $nilai)
                    <div>
                        <strong>{{ $kolom }}</strong>:
                        {{ \Illuminate\Support\Str::limit((string) ($nilai['lama'] ?? '-'), 40) }}
                        &rarr;
                        {{ \Illuminate\Support\Str::limit((string) ($nilai['baru'] ?? '-'), 40) }}
                    </div>
                @empty
                    -
                @endforelse
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="p-4 text-center">Belum ada aktivitas.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

<div class="mt-4">{{ $logs->links() }}</div>
@endsection