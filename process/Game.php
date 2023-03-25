<?php


namespace process;


use app\entity\User;
use app\game\Storage;
use icy8\SocketIO\Server;
use icy8\SocketIO\Socket;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use Workerman\Worker;

class Game
{
    /* @var TcpConnection $connection */
    protected $connection;
    /* @var Server $server */
    protected $server;
    protected $config = [];

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function onWorkerStart(Worker $worker)
    {
        $this->server = new Server($this->config['listen'], $this->config['context']);
        //
        $this->server->httpHost    = "0.0.0.0:{$this->config['http_port']}";
        $this->server->httpLimitIp = '127.0.0.1';

        $this->server->on('workerStart', function () {
            Storage::delAllConnection();
            Timer::add(0.1, [$this, 'playerTimer']);
        });
        $this->server->on('connection', function (Socket $socket) {
            Storage::setConnection($socket->id);
            $socket->emit('connection', ['id' => $socket->id]);
            $socket->emit('rooms_update');
            $this->server->emit('statistics_update');
        });
        $this->server->on('disconnect', function (Socket $socket) {
            Storage::delConnection($socket->id);
            /* @var User $player */
            foreach (Storage::getAllPlayer() as $player) {
                if ($player->connectionId == $socket->id) {
                    $player->setOnline(false);
                    $this->server->to('room#' . $player->room)->emit('room_inside_update');
                }
            }
            $this->server->emit('statistics_update');
        });
        $this->server->on('subcribe', function ($socket, $channel, $info) {
            $token  = $info['token'] ?? '';
            $player = Storage::getPlayer($token);
            if (!$player) {
                $this->server->leave($channel, $socket->id);
                return;
            }
            $this->server->emit('statistics_update');
        });
        $this->server->on('unsubcribe', function ($socket, $channel, $info) {
            $this->server->emit('statistics_update');
        });
        $this->server->worker()->run();
    }

    public function playerTimer()
    {
        /* @var User $player */
        foreach (Storage::getAllPlayer() as $player) {
//            if (Storage::getConnection($player->connectionId)) {
//                $player->setOnline(true);
//            } else {
//                $player->setOnline(false);
//            }
            if (!$player->online && (time() - $player->getUpdatedAt()) > 86400 * 3) {
                //
            }
        }
    }

    public function onConnect(TcpConnection $connection)
    {
    }

    public function onWorkerStop(Worker $worker)
    {
    }

    public function onWebSocketConnect(TcpConnection $connection)
    {
    }

    public function onMessage(TcpConnection $connection, $data)
    {
    }

    public function onClose(TcpConnection $connection)
    {
    }
}
