<?php

declare(strict_types=1);

namespace Fissible\Drift;

final class CoverageReport
{
    /** @param CoverageResult[] $results */
    public function __construct(public readonly array $results) {}

    /** @return CoverageResult[] */
    public function missing(): array
    {
        return array_values(array_filter(
            $this->results,
            fn (CoverageResult $r) => $r->status === CoverageStatus::Missing,
        ));
    }

    /** @return CoverageResult[] */
    public function implemented(): array
    {
        return array_values(array_filter(
            $this->results,
            fn (CoverageResult $r) => $r->status === CoverageStatus::Implemented,
        ));
    }

    /** @return CoverageResult[] */
    public function unknown(): array
    {
        return array_values(array_filter(
            $this->results,
            fn (CoverageResult $r) => $r->status === CoverageStatus::Unknown,
        ));
    }

    public function isFullyCovered(): bool
    {
        return count($this->missing()) === 0;
    }

    public function summary(): string
    {
        $total = count($this->results);
        $implemented = count($this->implemented());
        $missing = count($this->missing());
        $unknown = count($this->unknown());

        $parts = ["{$implemented}/{$total} implemented"];

        if ($missing > 0) {
            $parts[] = "{$missing} missing";
        }

        if ($unknown > 0) {
            $parts[] = "{$unknown} unknown (closure/no action)";
        }

        return implode(', ', $parts).'.';
    }
}
