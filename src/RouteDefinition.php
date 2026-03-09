<?php

declare(strict_types=1);

namespace Fissible\Drift;

/**
 * A single API route: an HTTP method and a normalised path.
 *
 * Three path representations are stored:
 *
 *   $rawPath    — the original path as supplied by the framework
 *   $path       — normalised for comparison ({param} placeholders)
 *   openApiPath() — converted to OpenAPI parameter syntax ({name} preserved)
 *
 * Path parameters are normalised to {param} in $path so that framework-specific
 * syntax (:id, {user}, <id>, etc.) compares consistently with OpenAPI templates.
 * openApiPath() preserves parameter names for spec generation.
 */
final class RouteDefinition
{
    public readonly string $rawPath;
    public readonly string $method;
    public readonly string $path;

    /** Framework-specific action string (e.g. "App\Http\Controllers\UserController@store"). */
    public readonly ?string $action;

    /** Framework-specific route name (e.g. "v1.posts.index"). */
    public readonly ?string $name;

    public function __construct(string $method, string $path, ?string $action = null, ?string $name = null)
    {
        $this->method  = strtoupper($method);
        $this->rawPath = $path;
        $this->path    = self::normalisePath($path);
        $this->action  = $action;
        $this->name    = $name;
    }

    /**
     * Path formatted for use in an OpenAPI spec.
     * Converts all parameter syntaxes to {paramName} while preserving names.
     *
     * Examples:
     *   /v1/users/:id   → /v1/users/{id}
     *   /v1/users/{id}  → /v1/users/{id}  (unchanged)
     *   /v1/users/<id>  → /v1/users/{id}
     */
    public function openApiPath(): string
    {
        $path = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '{$1}', $this->rawPath);
        $path = preg_replace('/<([^>]+)>/', '{$1}', $path);
        // {name} already OpenAPI-compatible — leave as-is
        return $path;
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
        $path = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '{param}', $path);
        $path = preg_replace('/\{[^}]+\}/', '{param}', $path);
        $path = preg_replace('/<[^>]+>/', '{param}', $path);

        return $path;
    }
}
