<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\AuthRepository\AuthInterface;
use App\Repositories\AuthRepository\AuthRepository;
use App\Repositories\ProfileRepository\ProfileInterface;
use App\Repositories\ProfileRepository\ProfileRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthInterface::class, AuthRepository::class);
        $this->app->bind(ProfileInterface::class, ProfileRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
