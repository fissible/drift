<?php

declare(strict_types=1);

namespace Fissible\Drift;

/**
 * A semver bump recommendation derived from a DriftReport.
 *
 * API versioning uses two levels:
 *   - Spec semver (info.version): tracks all changes within a URI version family
 *   - URI version (/v1/, /v2/): only increments on major breaking changes
 *
 * $requiresNewUriVersion is true when the major component is bumped,
 * signalling that a new spec file (v2.yaml) should be created.
 */
final class VersionRecommendation
{
    public function __construct(
        public readonly string $currentVersion,
        public readonly string $recommendedVersion,
        public readonly string $bumpType,            // 'major' | 'minor' | 'patch' | 'none'
        public readonly string $reason,
        public readonly bool   $requiresNewUriVersion,
    ) {}

    public function hasChange(): bool
    {
        return $this->bumpType !== 'none';
    }

    public function label(): string
    {
        if (!$this->hasChange()) {
            return sprintf('%s (no change)', $this->currentVersion);
        }

        return sprintf(
            '%s → %s (%s)',
            $this->currentVersion,
            $this->recommendedVersion,
            $this->bumpType,
        );
    }
}
