<?php

namespace Iqlearning\Pulse;

use CodeIgniter\Config\Services;
use Iqlearning\Pulse\Contracts\Storage;

class Pulse
{
    /**
     * The list of items to record.
     */
    protected array $entries = [];

    /**
     * The storage implementation.
     */
    protected Storage $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Record a new entry.
     *
     * @param mixed $value
     */
    public function record(string $type, string $key, $value = null, ?int $timestamp = null): self
    {
        $this->entries[] = [
            'type'      => $type,
            'key'       => $key,
            'value'     => $value,
            'timestamp' => $timestamp ?? time(),
        ];

        return $this;
    }

    /**
     * Record a current value (overwriting previous).
     *
     * @param mixed $value
     */
    public function set(string $type, string $key, $value, ?int $timestamp = null): self
    {
        $this->entries[] = [
            'type'      => 'value', // Special type for 'values' table
            'key'       => $key,
            'value'     => $value,
            'timestamp' => $timestamp ?? time(),
        ];

        return $this;
    }

    /**
     * Ingest the recorded items.
     */
    public function ingest(): void
    {
        if (empty($this->entries)) {
            return;
        }

        $this->storage->store($this->entries);
        $this->entries = [];
    }

    public function flush(int $timestamp = 0): void
    {
        $this->storage->flush($timestamp);
    }

    /**
     * Return the singleton instance via Services.
     */
    public static function instance(): self
    {
        return Services::pulse();
    }
}
