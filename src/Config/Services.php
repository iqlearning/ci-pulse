<?php

namespace Iqlearning\Pulse\Config;

use CodeIgniter\Config\BaseService;
use Iqlearning\Pulse\Pulse;
use Iqlearning\Pulse\Storage\DatabaseStorage;

class Services extends BaseService
{
    public static function pulse($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('pulse');
        }

        return new Pulse(
            new DatabaseStorage()
        );
    }
}
