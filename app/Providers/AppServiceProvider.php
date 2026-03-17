<?php

namespace App\Providers;

use Illuminate\Console\Command as ArtisanCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Some Artisan commands may be lazily resolved via the container command loader.
        // Ensure the Laravel application instance is set on the Command so output helpers work.
        $this->app->resolving(ArtisanCommand::class, function (ArtisanCommand $command, $app) {
            $command->setLaravel($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ensure generated URLs (route(), storage URL, signed routes) use the live domain & HTTPS in production
        if ($this->app->environment(['local', 'testing'])) {
            return;
        }

        $appUrl = config('app.url');
        if (!empty($appUrl)) {
            URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
