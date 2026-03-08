<?php

declare(strict_types=1);

namespace Fissible\Drift\Tests\Unit;

use Fissible\Accord\FileSpecSource;
use Fissible\Drift\DriftDetector;
use Fissible\Drift\RouteDefinition;
use PHPUnit\Framework\TestCase;

class DriftDetectorTest extends TestCase
{
    private DriftDetector $detector;

    protected function setUp(): void
    {
        $fixturesPath  = __DIR__ . '/../Fixtures';
        $specSource    = new FileSpecSource($fixturesPath, '{base}/{version}');
        $this->detector = new DriftDetector($specSource);
    }

    public function test_clean_when_routes_match_spec(): void
    {
        $routes = [
            new RouteDefinition('GET',  '/v1/users'),
            new RouteDefinition('POST', '/v1/users'),
        ];

        $report = $this->detector->detect($routes, 'v1');

        $this->assertTrue($report->isClean());
        $this->assertCount(2, $report->matched);
    }

    public function test_detects_undocumented_route(): void
    {
        $routes = [
            new RouteDefinition('GET',    '/v1/users'),
            new RouteDefinition('POST',   '/v1/users'),
            new RouteDefinition('DELETE', '/v1/users/{id}'), // not in spec
        ];

        $report = $this->detector->detect($routes, 'v1');

        $this->assertCount(1, $report->added);
        $this->assertSame('DELETE /v1/users/{param}', $report->added[0]->key());
    }

    public function test_detects_removed_route(): void
    {
        // Only GET /v1/users — POST /v1/users is in spec but not here
        $routes = [new RouteDefinition('GET', '/v1/users')];

        $report = $this->detector->detect($routes, 'v1');

        $this->assertCount(1, $report->removed);
        $this->assertSame('POST', $report->removed[0]->method);
    }

    public function test_all_routes_added_when_no_spec(): void
    {
        $routes = [new RouteDefinition('GET', '/v99/things')];

        $report = $this->detector->detect($routes, 'v99');

        $this->assertCount(1, $report->added);
        $this->assertEmpty($report->matched);
    }

    public function test_param_style_differences_are_normalised(): void
    {
        // App uses {user}, spec uses {id} — should still match
        $routes = [
            new RouteDefinition('GET', '/v1/users'),
            new RouteDefinition('POST', '/v1/users'),
        ];

        $report = $this->detector->detect($routes, 'v1');

        $this->assertTrue($report->isClean());
    }
}
