<?php

namespace Iqlearning\Pulse\Storage;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use Iqlearning\Pulse\Contracts\Storage;

class DatabaseStorage implements Storage
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function store(array $items): void
    {
        if (empty($items)) {
            return;
        }

        $entries = [];
        $values  = [];

        foreach ($items as $item) {
            if ($item['type'] === 'value') {
                $values[] = $item;
            } else {
                $entries[] = $item;
            }
        }

        // Store Entries (History)
        if (! empty($entries)) {
            $formattedEntries = array_map(static fn ($entry) => [
                'timestamp' => $entry['timestamp'],
                'type'      => $entry['type'],
                'key'       => $entry['key'],
                'key_hash'  => md5($entry['key']),
                'value'     => $entry['value'] ?? null,
            ], $entries);

            // Chunking is good practice, doing 1000 at a time
            $chunks = array_chunk($formattedEntries, 1000);

            foreach ($chunks as $chunk) {
                $this->db->table('pulse_entries')->insertBatch($chunk);
            }

            // Process Aggregates (Simplified for now - just Counts)
            // In a full port, we would handle Min/Max/Avg/Sum here
            // matching Laravel Pulse's logic.
            $this->upsertCounts($entries);
        }

        // Store Values (Current State)
        if (! empty($values)) {
            $formattedValues = array_map(static fn ($val) => [
                'timestamp' => $val['timestamp'],
                'type'      => $val['type'],
                'key'       => $val['key'],
                'key_hash'  => md5($val['key']),
                'value'     => $val['value'],
            ], $values);

            foreach (array_chunk($formattedValues, 1000) as $chunk) {
                // Upsert logic for values: replace existing
                $this->db->table('pulse_values')->upsertBatch($chunk);
            }
        }
    }

    protected function upsertCounts(array $entries): void
    {
        // 1. Pre-aggregate in PHP (1 hour bucket for MVP)
        $aggregates = [];
        $period     = 60; // 1 hour minutes

        foreach ($entries as $entry) {
            // MVP: Only aggregating generic counts for now
            $bucket    = (int) (floor($entry['timestamp'] / ($period * 60)) * ($period * 60));
            $keyHash   = md5($entry['key']);
            $uniqueKey = $bucket . '_' . $entry['type'] . '_' . $keyHash;

            if (! isset($aggregates[$uniqueKey])) {
                $aggregates[$uniqueKey] = [
                    'bucket'    => $bucket,
                    'period'    => $period,
                    'type'      => $entry['type'],
                    'key'       => $entry['key'],
                    'key_hash'  => $keyHash,
                    'aggregate' => 'count',
                    'value'     => 1,
                    'count'     => 1,
                ];
            } else {
                $aggregates[$uniqueKey]['value']++;
                $aggregates[$uniqueKey]['count']++;
            }
        }

        if (empty($aggregates)) {
            return;
        }

        // 2. Upsert to DB
        // Requires raw SQL for "value = value + VALUES(value)" logic
        $table  = $this->db->prefixTable('pulse_aggregates');
        $driver = $this->db->DBDriver;

        foreach ($aggregates as $row) {
            if ($driver === 'SQLite3') {
                $sql = "INSERT INTO {$table} (bucket, period, type, `key`, key_hash, aggregate, value, count)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ON CONFLICT(bucket, period, type, aggregate, key_hash)
                        DO UPDATE SET
                            value = value + excluded.value,
                            count = count + excluded.count";
            } else {
                // Default to MySQL/MariaDB syntax
                $sql = "INSERT INTO {$table} (bucket, period, type, `key`, key_hash, aggregate, value, count)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            value = value + VALUES(value),
                            count = count + VALUES(count)";
            }

            $this->db->query($sql, [
                $row['bucket'],
                $row['period'],
                $row['type'],
                $row['key'],
                $row['key_hash'],
                $row['aggregate'],
                $row['value'],
                $row['count'],
            ]);
        }
    }

    public function trim(): void
    {
        $sevenDaysAgo = time() - (7 * 24 * 60 * 60);
        $this->db->table('pulse_entries')->where('timestamp <', $sevenDaysAgo)->delete();
        $this->db->table('pulse_values')->where('timestamp <', $sevenDaysAgo)->delete();
        $this->db->table('pulse_aggregates')->where('bucket <', $sevenDaysAgo)->delete();
    }

    public function purge(?array $types = null): void
    {
        if ($types === null) {
            $this->db->table('pulse_entries')->truncate();
            $this->db->table('pulse_values')->truncate();
            $this->db->table('pulse_aggregates')->truncate();
        } else {
            $this->db->table('pulse_entries')->whereIn('type', $types)->delete();
            $this->db->table('pulse_values')->whereIn('type', $types)->delete();
            $this->db->table('pulse_aggregates')->whereIn('type', $types)->delete();
        }
    }

    public function flush(int $timestamp): void
    {
        if ($timestamp === 0) {
            $this->db->table('pulse_entries')->truncate();
            // $this->db->table('pulse_values')->truncate();
            // $this->db->table('pulse_aggregates')->truncate();
        } else {
            $this->db->table('pulse_entries')->where('timestamp <=', $timestamp)->delete();
            $this->db->table('pulse_entries')->where('type', 'system_stats')->where('timestamp <=', now() - env('pulse.systemStatsTTL', 1) * 60 * 60)->delete();
            // $this->db->table('pulse_values')->where('timestamp <', $timestamp)->delete();
            // $this->db->table('pulse_aggregates')->where('bucket <', $timestamp)->delete();
        }
    }

    public function values(string $type, ?array $keys = null): array
    {
        $builder = $this->db->table('pulse_values')
            ->select('timestamp, key, value')
            ->where('type', $type);

        if ($keys) {
            $builder->whereIn('key', $keys);
        }

        return $builder->get()->getResultArray();
    }

    public function graph(array $types, string $aggregate, int $interval): array
    {
        // Minimal implementation for validation
        return $this->db->table('pulse_aggregates')
            ->whereIn('type', $types)
            ->where('aggregate', $aggregate)
            ->where('period', $interval)
            ->orderBy('bucket', 'asc')
            ->get()
            ->getResultArray();
    }

    public function aggregate(
        string $type,
        array|string|null $aggregate,
        int $interval,
        ?string $orderBy = null,
        string $direction = 'desc',
        int $limit = 101,
        ?array $keys = null,
    ): array {
        // Minimal implementation
        $builder = $this->db->table('pulse_aggregates')
            ->where('type', $type)
            ->where('period', $interval);

        if ($aggregate) {
            $builder->whereIn('aggregate', (array) $aggregate);
        }

        return $builder->get()->getResultArray();
    }
}
