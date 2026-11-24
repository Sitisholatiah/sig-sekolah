<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ubah Kata Sandi - SIG Sekolah</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; }
        .card { max-width: 440px; margin: 60px auto; background: #fff; padding: 32px; border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        h1 { margin: 0 0 20px; color: #1d4ed8; font-size: 22px; }
        label { display: block; margin: 14px 0 6px; font-weight: 600; }
        input { width: 100%; padding: 10px; border: 1px solid #333; border-radius: 6px; box-sizing: border-box; }
        button { margin-top: 20px; width: 100%; padding: 11px; background: #1447e6; color: #fff; border: 0; border-radius: 6px; font-size: 16px; cursor: pointer; }
        .err { color: #dc2626; font-size: 13px; }
        .ok { background: #dcfce7; color: #166534; padding: 10px; border-radius: 6px; margin-bottom: 12px; }
        a { color: #1447e6; font-size: 14px; }
    </style>
</head>
<body>
<div class="card">
    <h1>Ubah Kata Sandi</h1>

    @if (session('status'))
        <div class="ok">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('kata-sandi.update') }}">
        @csrf
        @method('PUT')

        <label for="password_lama">Kata sandi lama</label>
        <input id="password_lama" type="password" name="password_lama" required autocomplete="current-password">
        @error('password_lama') <div class="err">{{ $message }}</div> @enderror

        <label for="password">Kata sandi baru</label>
        <input id="password" type="password" name="password" required autocomplete="new-password">
        @error('password') <div class="err">{{ $message }}</div> @enderror

        <label for="password_confirmation">Ulangi kata sandi baru</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

        <button type="submit">Simpan</button>
    </form>

    <p><a href="{{ route('dashboard') }}">&larr; Kembali ke dashboard</a></p>
</div>
</body>
</html>
