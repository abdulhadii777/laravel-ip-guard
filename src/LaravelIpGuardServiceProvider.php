<?php

namespace Ahs\LaravelIpGuard;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Ahs\LaravelIpGuard\Commands\LaravelIpGuardCommand;
use Ahs\LaravelIpGuard\Middleware\IpGuard;
use Ahs\LaravelIpGuard\Models\IpGuard as IpGuardModel;
use Illuminate\Support\Facades\Route;

class LaravelIpGuardServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ip-guard')
            ->hasConfigFile()
            ->hasCommand(LaravelIpGuardCommand::class)
            ->hasMigration('create_ip_guard_table');
    }

    public function boot(): void
    {
        parent::boot();

        // Register alias so users can apply middleware by name
        Route::aliasMiddleware('ip.guard', IpGuard::class);
    }

    public function register(): void
    {
        parent::register();

        // Register the main service
        $this->app->singleton(LaravelIpGuard::class, function ($app) {
            return new LaravelIpGuard();
        });
    }
}
