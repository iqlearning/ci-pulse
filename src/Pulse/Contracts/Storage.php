<?php

namespace Iqlearning\Pulse\Contracts;

interface Storage
{
    /**
     * Store the items.
     *
     * @param array $items List of Entry or Value objects/arrays
     */
    public function store(array $items): void;

    /**
     * Trim the storage.
     */
    public function trim(): void;

    /**
     * Purge the storage.
     */
    public function purge(?array $types = null): void;

    /**
     * Flush the storage.
     */
    public function flush(int $timestamp): void;

    /**
     * Retrieve values for the given type.
     */
    public function values(string $type, ?array $keys = null): array;

    /**
     * Retrieve aggregate values for plotting on a graph.
     */
    public function graph(array $types, string $aggregate, int $interval): array;

    /**
     * Retrieve aggregate values for a table.
     */
    public function aggregate(
        string $type,
        array|string|null $aggregate,
        int $interval,
        ?string $orderBy = null,
        string $direction = 'desc',
        int $limit = 101,
        ?array $keys = null,
    ): array;
}
