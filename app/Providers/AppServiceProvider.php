<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Macro FileUpload::macro('extensions') telah dihapus
        // untuk mencegah bentrok aturan validasi pada Livewire / Filament.
    }
}
