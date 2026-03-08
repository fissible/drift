<?php

declare(strict_types=1);

namespace Fissible\Drift;

final class CoverageAnalyser
{
    public function __construct(
        private readonly ImplementationCheckerInterface $checker,
    ) {}

    /**
     * @param  RouteDefinition[]  $routes
     */
    public function analyse(array $routes): CoverageReport
    {
        $results = [];

        foreach ($routes as $route) {
            $results[] = new CoverageResult($route, $this->checker->check($route));
        }

        return new CoverageReport($results);
    }
}
