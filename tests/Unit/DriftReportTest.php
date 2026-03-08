<?php

declare(strict_types=1);

namespace Fissible\Drift\Tests\Unit;

use Fissible\Drift\DriftReport;
use Fissible\Drift\RouteDefinition;
use PHPUnit\Framework\TestCase;

class DriftReportTest extends TestCase
{
    public function test_is_clean_when_no_drift(): void
    {
        $route  = new RouteDefinition('GET', '/v1/users');
        $report = new DriftReport(added: [], removed: [], matched: [$route], version: 'v1');

        $this->assertTrue($report->isClean());
        $this->assertFalse($report->hasBreakingChanges());
        $this->assertFalse($report->hasAdditiveChanges());
    }

    public function test_additive_change_detected(): void
    {
        $route  = new RouteDefinition('GET', '/v1/orders');
        $report = new DriftReport(added: [$route], removed: [], matched: [], version: 'v1');

        $this->assertFalse($report->isClean());
        $this->assertTrue($report->hasAdditiveChanges());
        $this->assertFalse($report->hasBreakingChanges());
    }

    public function test_breaking_change_detected(): void
    {
        $route  = new RouteDefinition('DELETE', '/v1/users/{id}');
        $report = new DriftReport(added: [], removed: [$route], matched: [], version: 'v1');

        $this->assertFalse($report->isClean());
        $this->assertTrue($report->hasBreakingChanges());
    }

    public function test_summary_clean(): void
    {
        $report = new DriftReport(
            added:   [],
            removed: [],
            matched: [new RouteDefinition('GET', '/v1/users'), new RouteDefinition('POST', '/v1/users')],
            version: 'v1',
        );

        $this->assertStringContainsString('2 route(s) match', $report->summary());
    }

    public function test_summary_with_drift(): void
    {
        $report = new DriftReport(
            added:   [new RouteDefinition('GET', '/v1/orders')],
            removed: [new RouteDefinition('DELETE', '/v1/users/{id}')],
            matched: [],
            version: 'v1',
        );

        $this->assertStringContainsString('removed', $report->summary());
        $this->assertStringContainsString('undocumented', $report->summary());
    }
}
