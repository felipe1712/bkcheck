<?php

namespace App\Services\BackgroundCheck\Contracts;

use App\Models\Subject;

interface SourceConnector
{
    /**
     * Get the identifier for this source (e.g. 'rfc', 'csd', 'siger', 'sat_listas', 'marcas').
     */
    public function getIdentifier(): string;

    /**
     * Get the human-readable name of this source.
     */
    public function getName(): string;

    /**
     * Determine if this connector applies to the given subject.
     */
    public function appliesTo(Subject $subject): bool;

    /**
     * Execute the query for the given subject.
     * Returns the raw payload array or throws an exception on failure.
     */
    public function execute(Subject $subject): array;
}
