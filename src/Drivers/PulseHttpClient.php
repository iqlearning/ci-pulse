<?php

namespace App\Pulse\Drivers;

use Config\Services;
use Exception;
use Iqlearning\Pulse\Pulse;

class PulseHttpClient
{
    public static function request(string $method, string $url, array $options = [])
    {
        $client = Services::curlrequest();

        $start = microtime(true);

        try {
            $response = $client->request($method, $url, $options);
            $outcome  = 'success';
        } catch (Exception $e) {
            $outcome = 'failure';

            throw $e;
        } finally {
            $duration = microtime(true) - $start;
            Pulse::instance()->record('outgoing_request', $url, $duration)
                ->record('outgoing_request_outcome', $url, $outcome);
        }

        return $response;
    }
}
