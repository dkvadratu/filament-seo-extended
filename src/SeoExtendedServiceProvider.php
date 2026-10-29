<?php

namespace Dkvadratu\FilamentSeoExtended;

use Illuminate\Support\ServiceProvider;

class SeoExtendedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-seo-extended.php', 'filament-seo-extended');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/filament-seo-extended.php' => config_path('filament-seo-extended.php')], 'filament-seo-extended-config');
        $this->publishesMigrations([__DIR__.'/../database/migrations/add_schema_json_to_seo_table.php.stub' => database_path('migrations/2026_10_29_000000_add_schema_json_to_seo_table.php')], 'filament-seo-extended-migrations');
    }
}
