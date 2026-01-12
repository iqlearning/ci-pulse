<?php

namespace Iqlearning\Pulse\Contracts;

interface Recorder
{
    /**
     * Start recording.
     */
    public function start(): void;

    /**
     * Stop recording.
     */
    public function stop(): void;
}
