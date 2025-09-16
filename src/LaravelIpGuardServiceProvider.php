<?php

namespace Ahs\LaravelIpGuard;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Ahs\LaravelIpGuard\Commands\LaravelIpGuardCommand;
use Ahs\LaravelIpGuard\Middleware\IpGuard;
use Illuminate\Support\Facades\Route;

class LaravelIpGuardServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ip-guard')
            ->hasConfigFile()
            ->hasCommand(LaravelIpGuardCommand::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Register alias so users can apply middleware by name
        Route::aliasMiddleware('ip.guard', IpGuard::class);
    }
}
