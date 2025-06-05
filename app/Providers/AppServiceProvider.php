<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FirebaseNotificationService;
use App\Services\PostNotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Firebase Notification Service
        $this->app->singleton(FirebaseNotificationService::class, function ($app) {
            return new FirebaseNotificationService();
        });

        // Register Post Notification Service
        $this->app->singleton(PostNotificationService::class, function ($app) {
            return new PostNotificationService($app->make(FirebaseNotificationService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
       
    }
}
