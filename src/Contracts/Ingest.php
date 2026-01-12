<?php

namespace Iqlearning\Pulse\Contracts;

interface Ingest
{
    /**
     * Ingest the items.
     */
    public function ingest(array $items): void;

    /**
     * Trim the ingest.
     */
    public function trim(): void;
}
