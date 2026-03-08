<?php

declare(strict_types=1);

namespace Fissible\Drift;

use cebe\openapi\spec\OpenApi;
use Fissible\Accord\SpecSourceInterface;

class DriftDetector
{
    public function __construct(
        private readonly SpecSourceInterface $specSource,
    ) {}

    /**
     * Compare the given routes against the spec for $version.
     *
     * @param RouteDefinition[] $routes
     */
    public function detect(array $routes, string $version): DriftReport
    {
        $spec = $this->specSource->load($version);

        if ($spec === null) {
            // No spec — every route is undocumented
            return new DriftReport(
                added:   $routes,
                removed: [],
                matched: [],
                version: $version,
            );
        }

        $specRoutes  = $this->extractSpecRoutes($spec);
        $routeKeys   = $this->indexByKey($routes);
        $specKeys    = $this->indexByKey($specRoutes);

        $added   = array_values(array_diff_key($routeKeys, $specKeys));
        $removed = array_values(array_diff_key($specKeys, $routeKeys));
        $matched = array_values(array_intersect_key($routeKeys, $specKeys));

        return new DriftReport(
            added:   $added,
            removed: $removed,
            matched: $matched,
            version: $version,
        );
    }

    /**
     * @return RouteDefinition[]
     */
    private function extractSpecRoutes(OpenApi $spec): array
    {
        $routes = [];

        foreach ($spec->paths as $path => $pathItem) {
            foreach ($pathItem->getOperations() as $method => $_operation) {
                $routes[] = new RouteDefinition($method, $path);
            }
        }

        return $routes;
    }

    /**
     * @param  RouteDefinition[] $routes
     * @return array<string, RouteDefinition>
     */
    private function indexByKey(array $routes): array
    {
        $index = [];

        foreach ($routes as $route) {
            $index[$route->key()] = $route;
        }

        return $index;
    }
}
