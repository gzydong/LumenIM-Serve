<?php

namespace App\Providers;

use SwooleTW\Http\LumenServiceProvider;
use App\Console\Commands\LumenImCommand;

class LumenIMServiceProvider extends LumenServiceProvider
{

    /**
     * Register commands.
     */
    protected function registerCommands()
    {
        $this->commands([
            LumenImCommand::class,
        ]);
    }
}
