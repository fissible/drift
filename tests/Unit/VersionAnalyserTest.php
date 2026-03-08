<?php

declare(strict_types=1);

namespace Fissible\Drift\Tests\Unit;

use Fissible\Accord\FileSpecSource;
use Fissible\Drift\DriftReport;
use Fissible\Drift\RouteDefinition;
use Fissible\Drift\VersionAnalyser;
use PHPUnit\Framework\TestCase;

class VersionAnalyserTest extends TestCase
{
    private VersionAnalyser $analyser;

    protected function setUp(): void
    {
        $specSource     = new FileSpecSource(__DIR__ . '/../Fixtures', '{base}/{version}');
        $this->analyser = new VersionAnalyser($specSource);
    }

    public function test_no_bump_when_clean(): void
    {
        $report = new DriftReport([], [], [new RouteDefinition('GET', '/v1/users')], 'v1');

        $rec = $this->analyser->analyse($report);

        $this->assertSame('none', $rec->bumpType);
        $this->assertFalse($rec->hasChange());
        $this->assertFalse($rec->requiresNewUriVersion);
    }

    public function test_minor_bump_for_additive_change(): void
    {
        $report = new DriftReport(
            added:   [new RouteDefinition('GET', '/v1/orders')],
            removed: [],
            matched: [],
            version: 'v1',
        );

        $rec = $this->analyser->analyse($report);

        $this->assertSame('minor', $rec->bumpType);
        $this->assertFalse($rec->requiresNewUriVersion);
        $this->assertStringContainsString('.', $rec->recommendedVersion);
    }

    public function test_major_bump_for_breaking_change(): void
    {
        $report = new DriftReport(
            added:   [],
            removed: [new RouteDefinition('DELETE', '/v1/users/{id}')],
            matched: [],
            version: 'v1',
        );

        $rec = $this->analyser->analyse($report);

        $this->assertSame('major', $rec->bumpType);
        $this->assertTrue($rec->requiresNewUriVersion);
        $this->assertStringStartsWith('2.', $rec->recommendedVersion);
    }

    public function test_label_formats_correctly(): void
    {
        $report = new DriftReport(
            added:   [new RouteDefinition('GET', '/v1/orders')],
            removed: [],
            matched: [],
            version: 'v1',
        );

        $rec = $this->analyser->analyse($report);

        $this->assertStringContainsString('→', $rec->label());
        $this->assertStringContainsString('minor', $rec->label());
    }
}
