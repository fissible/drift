<?php

declare(strict_types=1);

namespace Fissible\Drift\Drivers\Laravel\Providers;

use Fissible\Accord\SpecSourceInterface;
use Fissible\Drift\ChangelogGenerator;
use Fissible\Drift\Console\CoverageCommand;
use Fissible\Drift\Console\ValidateCommand;
use Fissible\Drift\Console\VersionCommand;
use Fissible\Drift\CoverageAnalyser;
use Fissible\Drift\DriftDetector;
use Fissible\Drift\Drivers\Laravel\Checkers\LaravelImplementationChecker;
use Fissible\Drift\Drivers\Laravel\Inspectors\LaravelRouteInspector;
use Fissible\Drift\ImplementationCheckerInterface;
use Fissible\Drift\RouteInspectorInterface;
use Fissible\Drift\VersionAnalyser;
use Illuminate\Support\ServiceProvider;

class DriftServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RouteInspectorInterface::class, function () {
            return new LaravelRouteInspector($this->app->make('router'));
        });

        $this->app->singleton(DriftDetector::class, function () {
            return new DriftDetector($this->app->make(SpecSourceInterface::class));
        });

        $this->app->singleton(VersionAnalyser::class, function () {
            return new VersionAnalyser($this->app->make(SpecSourceInterface::class));
        });

        $this->app->singleton(ChangelogGenerator::class, fn () => new ChangelogGenerator);

        $this->app->singleton(ImplementationCheckerInterface::class, fn () => new LaravelImplementationChecker);

        $this->app->singleton(CoverageAnalyser::class, function () {
            return new CoverageAnalyser($this->app->make(ImplementationCheckerInterface::class));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ValidateCommand::class,
                VersionCommand::class,
                CoverageCommand::class,
            ]);
        }
    }
}
