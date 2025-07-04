<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LettaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Letta\Client::class, function ($app) {
            $apiToken = config('services.letta.api_token');
            $apiUrl = config('services.letta.api_url');

            if (!$apiToken || !$apiUrl) {
                throw new \InvalidArgumentException('Letta API token or URL not configured.');
            }

            return new \Letta\Client($apiToken, $apiUrl);
        });
    }

    public function boot(): void
    {
        //
    }
}
