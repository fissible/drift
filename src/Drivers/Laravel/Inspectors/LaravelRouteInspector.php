<?php

declare(strict_types=1);

namespace Fissible\Drift\Drivers\Laravel\Inspectors;

use Fissible\Drift\RouteDefinition;
use Fissible\Drift\RouteInspectorInterface;
use Illuminate\Routing\Router;

/**
 * Inspects Laravel's route registry to produce RouteDefinition objects.
 *
 * By default, filters to routes in the 'api' middleware group. Pass a custom
 * filter callable to override this behaviour.
 */
class LaravelRouteInspector implements RouteInspectorInterface
{
    /** @var callable(array): bool */
    private $filter;

    public function __construct(
        private readonly Router $router,
        ?callable $filter = null,
    ) {
        $this->filter = $filter ?? static fn(array $route): bool =>
            in_array('api', (array) ($route['middleware'] ?? []), strict: true);
    }

    public function getRoutes(): array
    {
        $routes = [];

        foreach ($this->router->getRoutes() as $route) {
            $action = $route->getAction();

            if (!($this->filter)($action)) {
                continue;
            }

            foreach ($route->methods() as $method) {
                if ($method === 'HEAD') {
                    continue;
                }

                $routes[] = new RouteDefinition($method, '/' . ltrim($route->uri(), '/'));
            }
        }

        return $routes;
    }
}
