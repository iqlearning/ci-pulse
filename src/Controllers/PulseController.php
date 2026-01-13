<?php

namespace Iqlearning\Pulse\Controller;

use App\Controllers\BaseController;
use Iqlearning\Pulse\Pulse as IqlearningPulse;
use Iqlearning\Pulse\Recorders\SystemStats;
use Iqlearning\Pulse\Traits\Viewable;

class PulseController extends BaseController
{
    use Viewable;

    /**
     * Dashboard View.
     */
    public function index()
    {
        return $this->view('\Iqlearning\Pulse\Views\pulse\dashboard');
    }

    /**
     * Trigger collection (normally called via CLI/Cron).
     */
    public function check()
    {
        helper('date');
        // $timespan=30 * 24 * 60 * 60;
        $timespan = 2 * 60 * 60; //2 hour ago
        $timestamp = now('Asia/Jakarta') - $timespan;
        // log_message('debug', 'Flushing data older than ' . $timestamp);
        // 1. Flush old data
        // Pulse::instance()->flush(now() - 1 * 60 * 60);

        // 2. Run Recorders
        (new SystemStats())->start();

        // 3. Ingest to Storage
        IqlearningPulse::instance()->ingest();

        return $this->response->setJSON(['status' => 'recorded', 'timestamp' => $timestamp]);
    }

    /**
     * Dashboard Data Endpoint.
     */
    public function stats()
    {
        $db = \Config\Database::connect();


        // Fetch latest system state from 'pulse_values'
        $system = $db->table('pulse_values')
            ->where('type', 'value')
            ->get()
            ->getRow();

        $stats = $system ? json_decode($system->value, true) : ['cpu' => 0, 'memory' => 0, 'avail_mem' => 0, 'total_mem' => 0];
        $stats['db'] = $db->getDatabase();

        // 1. Avg Request Time (Last 1 hour)
        $oneHourAgo = time() - 3600;
        $avgQuery = $db->table('pulse_entries')
            ->selectAvg('value', 'avg_time')
            ->where('type', 'request_time')
            ->notLike('key', '%pulse%') // Exclude pulse tables
            ->where('timestamp >', $oneHourAgo)
            ->get()
            ->getRow();

        $stats['avg_req_time'] = $avgQuery ? round($avgQuery->avg_time * 1000, 0) : 0; // ms

        // 2. Slow Requests (> 1s)
        $subquery = $db->table('pulse_entries')->select('key,
        value,
        timestamp,
        COUNT(*) OVER (PARTITION BY key) AS cnt,
        ROW_NUMBER() OVER (
            PARTITION BY key
            ORDER BY value DESC, timestamp DESC
        ) AS rn')
            ->where('type', 'request_time')
            ->where('value >', 1.0)
            ->notLike('key', '%pulse%') // Exclude internal pulse routes
            ->groupBy('key')
            ->orderBy('timestamp', 'DESC')
            ->limit(10);
        $builder  = $db->newQuery()->fromSubquery($subquery, 't');
        $query    = $builder->get();
        $slowRequests = $query->getResultArray();

        // Format timestamp
        foreach ($slowRequests as &$req) {
            $req['time'] = date('H:i:s', $req['timestamp']);
            $req['duration'] = round($req['duration'], 3) . 's';
        }

        $stats['slow_requests'] = $slowRequests;

        // 3. Slow Queries (> 50ms)
        $subquery = $db->table('pulse_entries')->select('key,
        value,
        timestamp,
        COUNT(*) OVER (PARTITION BY key) AS cnt,
        ROW_NUMBER() OVER (
            PARTITION BY key
            ORDER BY value DESC, timestamp DESC
        ) AS rn')
            ->where('type', 'slow_query_full')
            ->where('value >', 0.05)
            ->notLike('key', '%pulse%') // Exclude internal pulse routes
            ->groupBy('key');
        $builder  = $db->newQuery()->fromSubquery($subquery, 't');
        $query    = $builder->get();
        $slowQueries = $query->getResultArray();
        $formattedQueries = [];
        foreach ($slowQueries as $q) {
            $data = json_decode($q['json'], true);
            $formattedQueries[] = [
                'time' => date('H:i:s', $q['timestamp']),
                'sql'  => $data['sql'] ?? '',
                'count' => $q['cnt'],
                'duration' => round($data['time'] * 1000, 2) . 'ms'
            ];
        }
        $stats['slow_queries'] = $formattedQueries;

        // 4. Exceptions (Recent)
        $exceptions = $db->table('pulse_entries')
            ->select('key as class, value as details_json, timestamp')
            ->where('type', 'exception')
            ->orderBy('timestamp', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        $formattedExceptions = [];
        foreach ($exceptions as $e) {
            $data = json_decode($e['details_json'], true);
            $formattedExceptions[] = [
                'time'    => date('H:i:s', $e['timestamp']),
                'class'   => $e['class'],
                'message' => $data['message'] ?? 'Unknown error',
                'location' => basename($data['file'] ?? '') . ':' . ($data['line'] ?? 0)
            ];
        }

        $stats['exceptions'] = $formattedExceptions;

        return $this->response->setJSON($stats);
    }
}
