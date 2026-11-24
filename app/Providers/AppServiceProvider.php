<?php

namespace App\Providers;

use App\Models\ProfilSekolah;              // TAMBAHAN
use App\Models\Sekolah;                    // TAMBAHAN
use App\Observers\ProfilSekolahObserver;   // TAMBAHAN
use App\Observers\SekolahObserver;         // TAMBAHAN
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sekolah::observe(SekolahObserver::class);              // TAMBAHAN
        ProfilSekolah::observe(ProfilSekolahObserver::class);  // TAMBAHAN
    }
}