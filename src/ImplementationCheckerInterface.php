<?php

declare(strict_types=1);

namespace Fissible\Drift;

interface ImplementationCheckerInterface
{
    public function check(RouteDefinition $route): CoverageStatus;
}
