<?php

namespace CodeIgniter\Shield\Config;

use CodeIgniter\Events\Events;
use Iqlearning\Pulse\Pulse;

class Registrar
{
    public static function Events(): array
    {
        return [
            // Pulse: Slow Query Monitoring
            Events::on('DBQuery', function (\CodeIgniter\Database\Query $query) {
                if (!is_null($query->getDuration()) && $query->getDuration() > 0.05) { // 50ms threshold
                    Pulse::instance()->record(
                        'slow_query_full',
                        json_encode([
                            'sql' => $query->getQuery(),
                            'time' => $query->getDuration()
                        ]),
                        $query->getDuration()
                    )->ingest();
                }
            }),
            $previousHandler = set_exception_handler(function ($exception) use (&$previousHandler) {
                try {
                    Pulse::instance()->record(
                        'exception',
                        get_class($exception),
                        json_encode([
                            'message' => $exception->getMessage(),
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine(),
                            'trace' => $exception->getTraceAsString()
                        ])
                    )
                        ->ingest();
                } catch (\Throwable $e) {
                    // Fail silently to avoid infinite loops if Pulse fails
                }

                if ($previousHandler) {
                    $previousHandler($exception);
                }
            })
        ];
    }
    public static function Filters(): array
    {
        return ['aliases' => [
            'pulse' => Pulse::class,
        ]];
    }
    // protected static function captureException(){

    // }
}
