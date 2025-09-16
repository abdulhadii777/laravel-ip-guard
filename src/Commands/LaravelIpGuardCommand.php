<?php

namespace Ahs\LaravelIpGuard\Commands;

use Illuminate\Console\Command;

class LaravelIpGuardCommand extends Command
{
    public $signature = 'laravel-ip-guard';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
