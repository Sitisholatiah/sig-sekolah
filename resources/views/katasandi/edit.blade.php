@extends('layouts.app')

@section('content')
<div class="container" style="max-width:480px">
    <h1>Ubah Kata Sandi</h1>

    @if (session('status'))
        <p style="color:green">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('kata-sandi.update') }}">
        @csrf
        @method('PUT')

        <div>
            <label for="password_lama">Kata sandi lama</label>
            <input id="password_lama" type="password" name="password_lama" required autocomplete="current-password">
            @error('password_lama') <small style="color:red">{{ $message }}</small> @enderror
        </div>

        <div>
            <label for="password">Kata sandi baru</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            @error('password') <small style="color:red">{{ $message }}</small> @enderror
        </div>

        <div>
            <label for="password_confirmation">Ulangi kata sandi baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit">Simpan</button>
    </form>
</div>
@endsection