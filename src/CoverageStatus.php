<?php

declare(strict_types=1);

namespace Fissible\Drift;

enum CoverageStatus: string
{
    /** Controller class and method both exist. */
    case Implemented = 'implemented';

    /** Controller class and/or method cannot be found. */
    case Missing = 'missing';

    /** Route has no resolvable action (closure, no action string). */
    case Unknown = 'unknown';
}
