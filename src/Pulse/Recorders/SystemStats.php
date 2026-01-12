<?php

namespace Iqlearning\Pulse\Recorders;

use Iqlearning\Pulse\Contracts\Recorder;
use Iqlearning\Pulse\Pulse;

class SystemStats implements Recorder
{
    public function start(): void
    {
        // 1. Memory Usage (MB)
        $memoryUsed = round(memory_get_usage(true), 2);

        // 2. CPU Load (Approximate for Windows/Linux)
        $cpuLoad  = $this->getCpuLoad();
        $availMem = $this->getAvailableMem();
        $totalMem = $this->getTotalMem();
        // $memLoad = $this->getMemLoad();
        // $this->CI->helper('date');
        // $timespan=30 * 24 * 60 * 60;
        helper('date');
        $timespan  = env('pulse.metricsTTL', 1) * 24 * 60 * 60; // 2 hour ago
        $timestamp = time() - $timespan;
        // log_message('alert', 'Flushing data older than ' . $timestamp);
        Pulse::instance()->flush($timestamp);

        Pulse::instance()->record('system_stats', 'memory_used', $memoryUsed)
            ->record('system_stats', 'cpu_load', $cpuLoad)
            ->record('system_stats', 'avail_mem', $availMem)
            // ->record('system_stats', 'total_mem', $totalMem)
            ->set('system', 'server_01', json_encode([
                'cpu'       => $cpuLoad,
                'memory'    => $memoryUsed,
                'avail_mem' => $availMem,
                'total_mem' => $totalMem,
                // 'mem_load' => $memLoad
            ]));
    }

    public function exceptionStart(): void
    {
        // 1. Memory Usage (MB)
        $memoryUsed = round(memory_get_usage(true), 2);

        // 2. CPU Load (Approximate for Windows/Linux)
        $cpuLoad  = $this->getCpuLoad();
        $availMem = $this->getAvailableMem();
        $totalMem = $this->getTotalMem();
        // $memLoad = $this->getMemLoad();
        // $this->CI->helper('date');
        // $timespan=30 * 24 * 60 * 60;
        helper('date');
        $timespan  = env('pulse.metricsTTL', 1) * 24 * 60 * 60; // 2 hour ago
        $timestamp = time() - $timespan;
        // log_message('alert', 'Flushing data older than ' . $timestamp);
        Pulse::instance()->flush($timestamp);

        Pulse::instance()->record('exception_stats', 'memory_used', $memoryUsed)
            ->record('exception_stats', 'cpu_load', $cpuLoad)
            ->record('exception_stats', 'avail_mem', $availMem);
    }

    public function stop(): void
    {
        // Not needed for snapshot recorders
    }

    protected function getCpuLoad(): int
    {
        if (stristr(PHP_OS, 'win')) {
            $cmd = 'wmic cpu get loadpercentage';
            @exec($cmd, $output);

            return (int) ($output[1] ?? 0);
        }

        $load = sys_getloadavg();

        return (int) ($load[0] * 100);
    }

    protected function getTotalMem(): int
    {
        if (stristr(PHP_OS, 'win')) {
            $cmd = 'wmic computersystem get TotalPhysicalMemory';
            @exec($cmd, $output);

            return (int) ($output[1] ?? 0);
        }
        $load = memory_get_usage(true); // need handler for linux/macos

        return (int) ($load / 1024 / 1024);
    }

    protected function getAvailableMem(): int
    {
        if (stristr(PHP_OS, 'win')) {
            $cmd = 'wmic OS get FreePhysicalMemory';
            @exec($cmd, $output);

            return (int) ($output[1] * 1024 ?? 0);
            // windows FreePhysicalMemory return free physical memory in kilobytes while the other in bytes. yeah, fuck microsoft
        }
        $load = memory_get_usage(true); // need handler for linux/macos

        return (int) ($load / 1024 / 1024);
    }
    // Source - https://stackoverflow.com/a
    // Posted by J.C. Inacio, modified by community. See post 'Timeline' for change history
    // Retrieved 2026-01-12, License - CC BY-SA 3.0

    protected function getSystemMemInfo()
    {
        $data    = explode("\n", file_get_contents('/proc/meminfo'));
        $meminfo = [];

        foreach ($data as $line) {
            [$key, $val]   = explode(':', $line);
            $meminfo[$key] = trim($val);
        }

        return $meminfo;
    }
}
