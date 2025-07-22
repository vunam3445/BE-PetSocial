<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\AuthRepository\AuthInterface;
use App\Repositories\AuthRepository\AuthRepository;
use App\Repositories\ProfileRepository\ProfileInterface;
use App\Repositories\ProfileRepository\ProfileRepository;
use App\Repositories\PetRepository\PetInterface;
use App\Repositories\PetRepository\PetRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthInterface::class, AuthRepository::class);
        $this->app->bind(ProfileInterface::class, ProfileRepository::class);
        $this->app->bind(PetInterface::class, PetRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
