<?php


namespace app\game;


use icy8\EventPusher\Pusher as PusherKernal;
use Webman\Config;

class Pusher
{

    protected        $channel;
    protected        $pusher;
    static protected $instance;

    public function __construct()
    {
        $this->pusher = new PusherKernal('127.0.0.1:' . Config::get('process.websocket_game.constructor.config.http_port', '9580'));
    }

    static public function instance()
    {
        if (!self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function channel($name)
    {
        $this->channel = $name;
        return $this;
    }

    public function emit($event, $data = [], $channel = '')
    {
        if ($channel) {
            $this->channel($channel);
        }
        return $this->pusher->trigger($event, $this->channel, $data);
    }

    public function subcribe($data = [], $channel = '')
    {
        if ($channel) {
            $this->channel($channel);
        }
        return $this->pusher->trigger('subcribe', $this->channel, $data);
    }

    public function unsubcribe($channel = '')
    {
        if ($channel) {
            $this->channel($channel);
        }
        return $this->pusher->trigger('unsubcribe', $this->channel, []);
    }

    public function roomsUpdate()
    {
        return $this->broadcast('rooms_update');
    }

    public function roomInsideUpdate($roomId)
    {
        $channel = 'room#' . $roomId;
        return $this->channel($channel)->emit('room_inside_update');
    }

    public function toast($message, $roomId = null, $type = 'success')
    {
        return $this->remoteTips($message, $roomId, $type, 'toast');
    }

    public function notice($message, $roomId = null, $type = 'success')
    {
        return $this->remoteTips($message, $roomId, $type, 'notice');
    }

    public function remoteTips($message, $roomId = null, $tipsType = 'success', $poupType = 'toast')
    {
        $data = [
            'type'    => $tipsType,
            'message' => $message,
        ];
        if ($roomId) {
            $channel = 'room#' . $roomId;
            return $this->channel($channel)->emit($poupType, $data);
        }
        return $this->broadcast($poupType, $data);
    }

    public function broadcast($event, $data = [])
    {
        return $this->pusher->broadcast($event, $data);
    }
}
