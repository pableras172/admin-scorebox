<?php

namespace App\Providers;

use App\Services\Firestore\FirestoreClientFactory;
use App\Services\Firestore\FirestoreUserGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FirestoreClientFactory::class, function () {
            return new FirestoreClientFactory($this->app);
        });

        $this->app->singleton(FirestoreUserGateway::class, function () {
            return new FirestoreUserGateway($this->app->make(FirestoreClientFactory::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
