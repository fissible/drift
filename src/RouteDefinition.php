<?php

declare(strict_types=1);

namespace Fissible\Drift;

/**
 * A single API route: an HTTP method and a normalised path.
 *
 * Path parameters are normalised to {param} so that framework-specific
 * syntax (:id, {user}, etc.) compares consistently with OpenAPI templates.
 */
final class RouteDefinition
{
    public readonly string $method;
    public readonly string $path;

    public function __construct(string $method, string $path)
    {
        $this->method = strtoupper($method);
        $this->path   = self::normalisePath($path);
    }

    /**
     * Stable key for set comparisons: "GET /v1/users/{param}"
     */
    public function key(): string
    {
        return $this->method . ' ' . $this->path;
    }

    public function __toString(): string
    {
        return $this->key();
    }

    /**
     * Normalise path parameter syntax to {param} regardless of framework.
     * Handles :param (Express-style), {param} (OpenAPI/Laravel), <param> (Symfony).
     */
    private static function normalisePath(string $path): string
    {
        // :param → {param}
        $path = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '{param}', $path);
        // {anything} → {param}
        $path = preg_replace('/\{[^}]+\}/', '{param}', $path);
        // <anything> → {param}
        $path = preg_replace('/<[^>]+>/', '{param}', $path);

        return $path;
    }
}
