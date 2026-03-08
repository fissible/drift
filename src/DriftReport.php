<?php

declare(strict_types=1);

namespace Fissible\Drift;

/**
 * The result of comparing live API routes against an OpenAPI spec.
 *
 * - $added:   routes present in the app but not documented in the spec
 * - $removed: routes documented in the spec but no longer present in the app
 * - $matched: routes present in both (passing)
 */
final class DriftReport
{
    /**
     * @param RouteDefinition[] $added
     * @param RouteDefinition[] $removed
     * @param RouteDefinition[] $matched
     */
    public function __construct(
        public readonly array $added,
        public readonly array $removed,
        public readonly array $matched,
        public readonly string $version,
    ) {}

    public function isClean(): bool
    {
        return empty($this->added) && empty($this->removed);
    }

    public function hasBreakingChanges(): bool
    {
        return !empty($this->removed);
    }

    public function hasAdditiveChanges(): bool
    {
        return !empty($this->added);
    }

    public function summary(): string
    {
        if ($this->isClean()) {
            return sprintf('v%s: all %d route(s) match the spec.', $this->version, count($this->matched));
        }

        $parts = [];

        if ($this->hasBreakingChanges()) {
            $parts[] = count($this->removed) . ' removed';
        }

        if ($this->hasAdditiveChanges()) {
            $parts[] = count($this->added) . ' undocumented';
        }

        return sprintf('v%s: %s.', $this->version, implode(', ', $parts));
    }
}
