<?php

namespace app\bootstrap;

use app\game\Storage;
use Webman\Bootstrap;
use Workerman\Worker;

class App implements Bootstrap
{

    /**
     * @inheritDoc
     */
    public static function start(?Worker $worker)
    {
        if (!$worker) return;
        if ($worker->name == 'monitor') return;
        if ($worker->name == 'webman') {
            // Storage::resetPlayerID();
        }
    }
}
