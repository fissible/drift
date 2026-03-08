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
}
