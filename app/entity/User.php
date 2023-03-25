<?php

namespace app\entity;

use app\game\Storage;

class User extends Entity
{
    public $id;
    public $nickname = '游客';
    /**
     * 性别
     * @var int $sex
     */
    public $sex    = 1;
    public $win    = 0;
    public $lose   = 0;
    public $score  = 0;
    public $online = false;
    /* @var array<Dice> */
    protected $dices = [];
    public    $room;
    /* @var Guess $guess */
    public        $guess;
    public        $connectionId;
    public        $token;
    public        $updatedAt;
    protected     $createdAt;
    static public $increment = 0;

    public function __construct($nickname = null)
    {
        $this->updatedAt = time();
        $this->createdAt = time();
        $this->id        = ++self::$increment;
        $this->token     = md5(uniqid($this->id . '_'));
        if ($nickname) {
            $this->nickname = $nickname;
        }
    }

    /**
     * 产生骰子
     * @param $num
     * @return $this
     */
    public function createDices($num)
    {
        $this->dices = [];
        for ($i = 1; $i <= $num; $i++) {
            $this->dices[] = Dice::make();
        }
        Storage::setPlayer($this);
        return $this;
    }

    /**
     * 摇骰子
     * @return $this
     */
    public function roll()
    {
        /* @var Dice $dice */
        foreach ($this->dices as $dice) {
            $dice->roll();
        }
        Storage::setPlayer($this);
        return $this;
    }

    /**
     * 叫骰
     * @param $num
     * @param $point
     * @param bool $zhai 叫斋true 或 破斋false
     * @return User
     */
    public function guess($num, $point, $zhai = null)
    {
        $room = $this->getRoom();
        if ($room) {
            $this->guess = Guess::make($num, $point);
            if ($zhai !== null) {
                $room->setZhai($zhai);
            }
            $room->moveCursor();
            Storage::setPlayer($this);
        }
        return $this;
    }

    /**
     * 投降
     * @return $this
     */
    public function giveUp()
    {
        $room = $this->getRoom();
        if ($room) {
            $this->lose++;// 输局
            $loseScore = $room->loseScore;
            if ($room->status) {
                $loseScore = $loseScore / 2;// 投降输一半
            }
            $this->score -= $loseScore;
            Storage::setPlayer($this);
        }
        return $this;
    }

    /**
     * 劈
     * @param $user
     * @return User
     * @throws \Exception
     */
    public function pick(User $referto)
    {
        if (!$referto->guess) {
            throw new \Exception('玩家没有叫骰');
        }
        $this->getRoom()->setPicker(new Pick($this, $referto));
        return $this;
    }

    public function win()
    {
        if ($this->room) {
            $this->win++;
            $this->score += $this->getRoom()->winScore;
        }
        Storage::setPlayer($this);
        return $this;
    }

    public function lose()
    {
        if ($this->room) {
            $this->lose++;
            $this->score -= $this->getRoom()->loseScore;
        }
        Storage::setPlayer($this);
        return $this;
    }

    /**
     * 反劈
     * @param $userId
     * @return $this
     */
    public function reversePick($userId)
    {
        $this->getRoom()->setPicker(Pick::make($this, $this->getRoom()->members[$userId])->setReverse(true));
        return $this;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->score = $this->win = $this->lose = 0;
        $this->dices = [];
        $this->room  = null;
        $this->guess = null;
        Storage::setPlayer($this);
        return $this;
    }

    static public function nextId()
    {
        return Storage::totalPlayer() + 1;
    }

    /**
     * @param mixed $id
     * @return User
     */
    public function setId($id)
    {
        $this->id = $id;
        Storage::setPlayer($this);
        return $this;
    }

    /**
     * @param string $nickname
     * @return User
     */
    public function setNickname(string $nickname): User
    {
        $this->nickname = $nickname;
        Storage::setPlayer($this);
        return $this;
    }

    /**
     * @param mixed $win
     * @return User
     */
    public function setWin($win)
    {
        $this->win = $win;
        Storage::setPlayer($this);
        return $this;
    }

    /**
     * @param mixed $lose
     * @return User
     */
    public function setLose($lose)
    {
        $this->lose = $lose;
        Storage::setPlayer($this);
        return $this;
    }

    /**
     * @param $room
     * @return User
     */
    public function setRoom($room): User
    {
        $this->room = $room;
        Storage::setPlayer($this);
        return $this;
    }

    /**
     * @param int $sex
     * @return User
     */
    public function setSex(int $sex): User
    {
        $this->sex = $sex;
        Storage::setPlayer($this);
        return $this;
    }

    /**
     * @param mixed $connectionId
     * @return User
     */
    public function setConnectionId($connectionId)
    {
        $this->connectionId = $connectionId;
        Storage::setPlayer($this);
        return $this;
    }

    /**
     * @param string $token
     * @return User
     */
    public function setToken(string $token): User
    {
        $this->token = $token;
        Storage::setPlayer($this);
        return $this;
    }

    /**
     * @return array
     */
    public function getDices(): array
    {
        return $this->dices;
    }

    public function getDicesSummary()
    {
        $result = array_fill(1, 6, 0);
        /* @var Dice $dice */
        foreach ($this->dices as $dice) {
            $result[$dice->point->value] += 1;
        }
        return $result;
    }

    public function getRoom()
    {
        return Storage::getRoom($this->room);
    }

    public function isTurnToMe()
    {
        $room = $this->getRoom();
        if (!$room) return false;
        return $room->cursorUser() === $this->id;
    }

    public function getUniqid()
    {
        return $this->token;
    }

    public function checkName()
    {

    }

    /**
     * @param bool $online
     * @return User
     */
    public function setOnline(bool $online): User
    {
        $this->online = $online;
        Storage::setPlayer($this);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
