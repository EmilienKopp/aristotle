<?php

declare(strict_types=1);

namespace Splitstack\Aristotle;

use Illuminate\Support\ServiceProvider;
use Splitstack\Aristotle\Console\Commands\MakeEntityCommand;
use Splitstack\Aristotle\Support\CastTypeResolver;

final class AristotleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/aristotle.php', 'aristotle');

        $this->app->singleton(CastTypeResolver::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/aristotle.php' => config_path('aristotle.php'),
            ], 'aristotle-config');

            $this->publishes([
                __DIR__.'/../stubs/entity.stub' => base_path('stubs/entity.stub'),
            ], 'aristotle-stubs');

            $this->commands([MakeEntityCommand::class]);
        }
    }
}
