<?php

declare(strict_types=1);

namespace Fissible\Drift\Tests\Unit;

use Fissible\Drift\RouteDefinition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RouteDefinitionTest extends TestCase
{
    public function test_method_is_uppercased(): void
    {
        $route = new RouteDefinition('get', '/v1/users');
        $this->assertSame('GET', $route->method);
    }

    public function test_key_combines_method_and_path(): void
    {
        $route = new RouteDefinition('GET', '/v1/users');
        $this->assertSame('GET /v1/users', $route->key());
    }

    #[DataProvider('paramNormalisationCases')]
    public function test_path_parameters_are_normalised(string $input, string $expected): void
    {
        $route = new RouteDefinition('GET', $input);
        $this->assertSame($expected, $route->path);
    }

    public static function paramNormalisationCases(): array
    {
        return [
            'laravel style'  => ['/v1/users/{id}',     '/v1/users/{param}'],
            'express style'  => ['/v1/users/:id',       '/v1/users/{param}'],
            'symfony style'  => ['/v1/users/<id>',      '/v1/users/{param}'],
            'named param'    => ['/v1/orders/{orderId}', '/v1/orders/{param}'],
            'no params'      => ['/v1/users',            '/v1/users'],
            'multiple params'=> ['/v1/a/{b}/c/{d}',     '/v1/a/{param}/c/{param}'],
        ];
    }

    public function test_routes_with_equivalent_params_produce_same_key(): void
    {
        $appRoute  = new RouteDefinition('GET', '/v1/users/{user}');   // Laravel
        $specRoute = new RouteDefinition('GET', '/v1/users/{id}');     // OpenAPI

        $this->assertSame($appRoute->key(), $specRoute->key());
    }

    #[DataProvider('openApiPathCases')]
    public function test_open_api_path_preserves_param_names(string $input, string $expected): void
    {
        $route = new RouteDefinition('GET', $input);
        $this->assertSame($expected, $route->openApiPath());
    }

    public static function openApiPathCases(): array
    {
        return [
            'laravel style preserved'  => ['/v1/users/{user}',    '/v1/users/{user}'],
            'express style converted'  => ['/v1/users/:id',        '/v1/users/{id}'],
            'symfony style converted'  => ['/v1/users/<userId>',   '/v1/users/{userId}'],
            'no params unchanged'      => ['/v1/users',            '/v1/users'],
            'multiple params'          => ['/v1/a/{b}/c/:d',       '/v1/a/{b}/c/{d}'],
        ];
    }

    public function test_raw_path_is_stored_unmodified(): void
    {
        $route = new RouteDefinition('GET', '/v1/users/:id');
        $this->assertSame('/v1/users/:id', $route->rawPath);
    }

    public function test_name_is_null_by_default(): void
    {
        $route = new RouteDefinition('GET', '/v1/users');
        $this->assertNull($route->name);
    }

    public function test_name_is_stored_when_provided(): void
    {
        $route = new RouteDefinition('GET', '/v1/users', null, 'v1.users.index');
        $this->assertSame('v1.users.index', $route->name);
    }
}
