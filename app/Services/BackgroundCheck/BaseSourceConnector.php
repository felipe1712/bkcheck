<?php

namespace App\Services\BackgroundCheck;

use App\Services\BackgroundCheck\Contracts\SourceConnector;
use App\Models\Subject;

abstract class BaseSourceConnector implements SourceConnector
{
    /**
     * Get the minimum investigation tier level required for this connector (1 to 4).
     */
    public function getMinTierLevel(): int
    {
        return 1;
    }
}
