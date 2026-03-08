<?php

declare(strict_types=1);

namespace Fissible\Drift\Drivers\Laravel\Checkers;

use Fissible\Drift\CoverageStatus;
use Fissible\Drift\ImplementationCheckerInterface;
use Fissible\Drift\RouteDefinition;

final class LaravelImplementationChecker implements ImplementationCheckerInterface
{
    public function check(RouteDefinition $route): CoverageStatus
    {
        if ($route->action === null) {
            return CoverageStatus::Unknown;
        }

        [$class, $method] = $this->parseAction($route->action);

        if ($class === null) {
            return CoverageStatus::Unknown;
        }

        if (! class_exists($class) || ! method_exists($class, $method)) {
            return CoverageStatus::Missing;
        }

        return CoverageStatus::Implemented;
    }

    /** @return array{0: string|null, 1: string} */
    private function parseAction(string $action): array
    {
        if (str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);

            return [$class, $method];
        }

        if (str_contains($action, '::')) {
            [$class, $method] = explode('::', $action, 2);

            return [$class, $method];
        }

        // Invokable controller
        if (class_exists($action)) {
            return [$action, '__invoke'];
        }

        return [null, '__invoke'];
    }
}
