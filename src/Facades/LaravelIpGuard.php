<?php

namespace Ahs\LaravelIpGuard\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ahs\LaravelIpGuard\LaravelIpGuard
 */
class LaravelIpGuard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Ahs\LaravelIpGuard\LaravelIpGuard::class;
    }
}
