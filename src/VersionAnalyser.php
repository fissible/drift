<?php

declare(strict_types=1);

namespace Fissible\Drift;

use Fissible\Accord\SpecSourceInterface;

class VersionAnalyser
{
    public function __construct(
        private readonly SpecSourceInterface $specSource,
    ) {}

    public function analyse(DriftReport $report): VersionRecommendation
    {
        $current = $this->currentSemver($report->version);

        if ($report->isClean()) {
            return new VersionRecommendation(
                currentVersion:       $current,
                recommendedVersion:   $current,
                bumpType:             'none',
                reason:               'No drift detected.',
                requiresNewUriVersion: false,
            );
        }

        [$major, $minor, $patch] = $this->parseSemver($current);

        if ($report->hasBreakingChanges()) {
            $removed = implode(', ', array_map(fn($r) => (string) $r, $report->removed));

            return new VersionRecommendation(
                currentVersion:        $current,
                recommendedVersion:    ($major + 1) . '.0.0',
                bumpType:              'major',
                reason:                'Route(s) removed or renamed: ' . $removed,
                requiresNewUriVersion: true,
            );
        }

        // Additive only → minor bump
        $added = implode(', ', array_map(fn($r) => (string) $r, $report->added));

        return new VersionRecommendation(
            currentVersion:        $current,
            recommendedVersion:    $major . '.' . ($minor + 1) . '.0',
            bumpType:              'minor',
            reason:                'New route(s) added: ' . $added,
            requiresNewUriVersion: false,
        );
    }

    private function currentSemver(string $uriVersion): string
    {
        $spec = $this->specSource->load($uriVersion);

        if ($spec !== null && isset($spec->info->version)) {
            $raw = (string) $spec->info->version;
            // Ensure it's semver — if it's just "1" treat as "1.0.0"
            return preg_match('/^\d+\.\d+\.\d+$/', $raw) ? $raw : $raw . '.0.0';
        }

        return '1.0.0';
    }

    /** @return array{int, int, int} */
    private function parseSemver(string $version): array
    {
        [$major, $minor, $patch] = array_map('intval', explode('.', $version));

        return [$major, $minor, $patch];
    }
}
