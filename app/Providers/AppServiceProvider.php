<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
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
        // Deliberately a composer (evaluated lazily, per view render), not
        // View::share() (evaluated eagerly at boot time) - this provider
        // boots for every artisan command too, including `migrate` on a
        // fresh install, before the settings table exists. A composer only
        // ever queries it when a view is actually being rendered.
        View::composer('*', function ($view) {
            $logoPath = Setting::get('facility_logo');

            $view->with('brandName', Setting::get('facility_name', config('app.name')));
            $view->with('brandLogoUrl', $logoPath ? Storage::url($logoPath) : null);
        });
    }
}
