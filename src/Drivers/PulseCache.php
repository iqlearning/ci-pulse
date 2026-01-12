<?php

namespace Iqlearning\Pulse\Drivers;

use CodeIgniter\Cache\CacheInterface;
use Iqlearning\Pulse\Pulse;

class PulseCache implements CacheInterface
{
    protected CacheInterface $handler;

    public function __construct(CacheInterface $handler)
    {
        $this->handler = $handler;
    }

    public function initialize()
    {
        $this->handler->initialize();
    }

    public function get(string $key)
    {
        $start    = microtime(true);
        $value    = $this->handler->get($key);
        $duration = microtime(true) - $start;

        $status = $value === null ? 'miss' : 'hit';

        Pulse::instance()->record('cache_interaction', $key, $status)
            ->record('cache_duration', $key, $duration);

        return $value;
    }

    public function save(string $key, $value, int $ttl = 60)
    {
        Pulse::instance()->record('cache_save', $key, 1);

        return $this->handler->save($key, $value, $ttl);
    }

    public function delete(string $key)
    {
        return $this->handler->delete($key);
    }

    // public function deleteMatching(string $pattern)
    // {
    //     return $this->handler->deleteMatching($pattern);
    // }

    public function increment(string $key, int $offset = 1)
    {
        return $this->handler->increment($key, $offset);
    }

    public function decrement(string $key, int $offset = 1)
    {
        return $this->handler->decrement($key, $offset);
    }

    public function clean()
    {
        return $this->handler->clean();
    }

    public function getCacheInfo()
    {
        return $this->handler->getCacheInfo();
    }

    public function getMetaData(string $key)
    {
        return $this->handler->getMetaData($key);
    }

    public function isSupported(): bool
    {
        return $this->handler->isSupported();
    }

    // Additional methods required by interface if any... CI4 CacheInterface is simple.
}
