<?php


namespace app\game;


use app\entity\Entity;
use app\entity\Room;
use app\entity\User;
use support\Redis;

class Storage
{
    static public $key = 'icy8:dice-game:';

    static public function resetPlayerID()
    {
        return Redis::del(self::$key . 'player-id');
    }

    static public function incrPlayerID()
    {
        return Redis::incr(self::$key . 'player-id');
    }

    static public function setConnection($id, $prefix = 'icy8_')
    {
        $key = $prefix . $id;
        Redis::set(self::$key . 'connection:' . $key, $id);
    }

    static public function getConnection($id, $prefix = 'icy8_')
    {
        $key = $prefix . $id;
        return Redis::get(self::$key . 'connection:' . $key);
    }

    static public function delConnection($id, $prefix = 'icy8_')
    {
        $key = $prefix . $id;
        return Redis::del(self::$key . 'connection:' . $key);
    }

    static public function delAllConnection()
    {
        $list = Redis::keys(self::$key . 'connection:*');
        foreach ($list as $key) {
            Redis::del($key);
        }
    }

    static public function del($key, $field)
    {
        return Redis::hDel(self::$key . $key, $key . '#' . $field);
    }

    static public function delAll($key)
    {
        return Redis::del(self::$key . $key);
    }

    static public function set($key, $data)
    {
        if (is_array($data)) {
            foreach ($data as $item) {
                self::set($key, $item);
            }
        } else if ($data instanceof Entity) {
            Redis::hSet(self::$key . $key, $key . '#' . $data->getUniqid(), serialize($data));
        }
    }

    static public function get($key, $field)
    {
        if ($field === null) {
            return null;
        }
        $data = Redis::hGet(self::$key . $key, $key . '#' . $field);
        return unserialize($data);
    }

    static public function getAll($key)
    {
        $list = Redis::hGetAll(self::$key . $key);
        foreach ($list as &$item) {
            $item = unserialize($item);
        }
        return $list;
    }

    static public function getAllRoom()
    {
        $list = Redis::hGetAll(self::$key . 'room');
        foreach ($list as &$item) {
            $item = unserialize($item);
        }
        return $list;
    }

    static public function getAllPlayer()
    {
        $list = Redis::hGetAll(self::$key . 'player');
        foreach ($list as &$item) {
            $item = unserialize($item);
        }
        return $list;
    }

    static public function setRoom($data)
    {
        self::set('room', $data);
    }

    static public function setPlayer($data)
    {
        self::set('player', $data);
    }

    /**
     * @param $roomeId
     * @return Room|null
     */
    static public function getRoom($roomId)
    {
        return self::get('room', $roomId);
    }

    /**
     * @param $token
     * @return User|null
     */
    static public function getPlayer($token)
    {
        return self::get('player', $token);
    }

    /**
     * @param $id
     * @return bool|User
     */
    static public function getPlayerById($id)
    {
        $players = Storage::getAllPlayer();
        foreach ($players as $player) {
            if ($player->id == $id) {
                return $player;
            }
        }
        return null;
    }

    static public function delRoom($roomId)
    {
        self::del('room', $roomId);
    }

    static public function delAllRoom()
    {
        self::delAll('room');
    }

    static public function delAllPlayer()
    {
        self::delAll('player');
    }

    static public function delPlayer($token)
    {
        self::del('player', $token);
    }

    static public function totalPlayer()
    {
        return Redis::hLen(self::$key . 'player');
    }

    static public function totalRoom()
    {
        return Redis::hLen(self::$key . 'room');
    }
}
