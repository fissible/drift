<?php

declare(strict_types=1);

namespace Fissible\Drift;

class ChangelogGenerator
{
    public function generate(DriftReport $report, VersionRecommendation $recommendation): string
    {
        $date    = date('Y-m-d');
        $version = $recommendation->recommendedVersion;

        $lines = ["## [{$version}] - {$date}"];

        if ($report->hasBreakingChanges()) {
            $lines[] = '';
            $lines[] = '### Breaking Changes';
            foreach ($report->removed as $route) {
                $lines[] = "- Removed: `{$route}`";
            }
        }

        if ($report->hasAdditiveChanges()) {
            $lines[] = '';
            $lines[] = '### Added';
            foreach ($report->added as $route) {
                $lines[] = "- `{$route}`";
            }
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Prepend a new entry to an existing CHANGELOG.md, or create it.
     */
    public function prepend(string $entry, string $changelogPath): void
    {
        $existing = file_exists($changelogPath)
            ? file_get_contents($changelogPath)
            : "# Changelog\n\n";

        // Insert after the first heading line
        $updated = preg_replace(
            '/^(# [^\n]*\n)/m',
            "$1\n" . $entry . "\n",
            $existing,
            limit: 1,
        );

        file_put_contents($changelogPath, $updated ?? $entry . "\n" . $existing);
    }
}
