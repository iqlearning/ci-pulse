<?php
namespace Iqlearning\Pulse\Filters;

use Iqlearning\Pulse\Pulse;
use Iqlearning\Pulse\Recorders\SystemStats;

class PulseRecording implements \CodeIgniter\Filters\FilterInterface
{
    public function before(\CodeIgniter\HTTP\RequestInterface $request, $arguments = null)
    {
        // Start pulse recording
        // $pulse = \Iqlearning\Pulse\Pulse::instance();
        // $pulse->startRecording();
    }
    public function after(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, $arguments = null)
    {
        $startTime = $request->getServer('REQUEST_TIME_FLOAT');
        $duration = 0;

        if ($startTime) {
            $duration = microtime(true) - $startTime;
        }

        $path = $request->getUri()->getPath();

        Pulse::instance()->record(
            'request_time',
            $path,
            $duration
        )->ingest();

        // Record System Stats except for /pulse/check
        if (strpos($path, 'pulse') === false) {
            // 1. Run System Stats Recorders
            (new SystemStats())->exceptionStart();

            // 2. Ingest to Storage
            Pulse::instance()->ingest();
        }
    }
    // This class can be expanded to include methods for starting and stopping
    // recording, as well as any other functionality needed for pulse recording.
}