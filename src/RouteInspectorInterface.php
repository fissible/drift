<?php

declare(strict_types=1);

namespace Fissible\Drift;

/**
 * Retrieves the current set of API routes from a framework's route registry.
 *
 * Implement this interface to teach Drift how to enumerate routes in your
 * framework. The Laravel driver provides LaravelRouteInspector out of the box.
 */
interface RouteInspectorInterface
{
    /**
     * Return all API route definitions from the application.
     *
     * Implementations should filter to API routes only (e.g. those in the
     * 'api' middleware group, or matching a versioned URI pattern) and
     * exclude framework-internal routes.
     *
     * @return RouteDefinition[]
     */
    public function getRoutes(): array;
}
