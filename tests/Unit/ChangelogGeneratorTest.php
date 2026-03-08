<?php

declare(strict_types=1);

namespace Fissible\Drift\Tests\Unit;

use Fissible\Drift\ChangelogGenerator;
use Fissible\Drift\DriftReport;
use Fissible\Drift\RouteDefinition;
use Fissible\Drift\VersionRecommendation;
use PHPUnit\Framework\TestCase;

class ChangelogGeneratorTest extends TestCase
{
    private ChangelogGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ChangelogGenerator();
    }

    public function test_generates_entry_with_version_and_date(): void
    {
        $report = new DriftReport(
            added:   [new RouteDefinition('GET', '/v1/orders')],
            removed: [],
            matched: [],
            version: 'v1',
        );

        $recommendation = new VersionRecommendation('1.0.0', '1.1.0', 'minor', 'New routes', false);

        $entry = $this->generator->generate($report, $recommendation);

        $this->assertStringContainsString('[1.1.0]', $entry);
        $this->assertStringContainsString(date('Y-m-d'), $entry);
        $this->assertStringContainsString('GET /v1/orders', $entry);
        $this->assertStringContainsString('Added', $entry);
    }

    public function test_generates_breaking_changes_section(): void
    {
        $report = new DriftReport(
            added:   [],
            removed: [new RouteDefinition('DELETE', '/v1/users/{id}')],
            matched: [],
            version: 'v1',
        );

        $recommendation = new VersionRecommendation('1.0.0', '2.0.0', 'major', 'Routes removed', true);

        $entry = $this->generator->generate($report, $recommendation);

        $this->assertStringContainsString('Breaking Changes', $entry);
        $this->assertStringContainsString('Removed:', $entry);
        $this->assertStringContainsString('DELETE', $entry);
    }

    public function test_prepend_creates_file_if_missing(): void
    {
        $path = sys_get_temp_dir() . '/accord_test_changelog_' . uniqid() . '.md';

        $this->generator->prepend("## [1.1.0] - 2026-01-01\n\n- Added thing\n", $path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('[1.1.0]', file_get_contents($path));

        unlink($path);
    }

    public function test_prepend_inserts_before_existing_entries(): void
    {
        $path = sys_get_temp_dir() . '/accord_test_changelog_' . uniqid() . '.md';
        file_put_contents($path, "# Changelog\n\n## [1.0.0] - 2025-01-01\n\n- Initial\n");

        $this->generator->prepend("## [1.1.0] - 2026-01-01\n\n- New thing\n", $path);

        $contents = file_get_contents($path);
        $this->assertLessThan(strpos($contents, '[1.0.0]'), strpos($contents, '[1.1.0]'));

        unlink($path);
    }
}
