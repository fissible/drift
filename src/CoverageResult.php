<?php

declare(strict_types=1);

namespace Fissible\Drift;

final class CoverageResult
{
    public function __construct(
        public readonly RouteDefinition $route,
        public readonly CoverageStatus $status,
    ) {}
}
