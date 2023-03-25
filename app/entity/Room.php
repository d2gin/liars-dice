<?php


namespace app\entity;


use app\game\Storage;

class Room extends Entity
{

    protected $id;
    public    $name = '海景房';
    /**
     * 1准备中 2游戏中
     * @var int $status
     */
    public $status = self::STATUS_PREPARING;
    /**
     * @var array<User> $members
     */
    public $members = [];
    /**
     * @var User $creator
     */
    public $creator;
    /**
     * 房间是否叫斋
     * @var bool $zhai
     */
    public $zhai = false;
    /**
     * 劈骰
     * @var Pick $picker
     */
    public           $picker;
    public           $winScore    = 1;
    public           $loseScore   = 2;
    public           $diceNum     = 4;
    public           $loseHalf    = true;// 投降输一半
    public           $memberLimit = 20;// 房间人数限制
    protected        $winner;// 胜者
    protected        $loser;// 败者
    protected        $updatedAt;
    protected        $createdAt;
    protected        $cursor      = 0;
    static protected $increment   = 0;

    const STATUS_PREPARING  = 1;// 准备中
    const STATUS_GAMING     = 2;// 游戏中
    const STATUS_SETTLEMENT = 3;// 结算

    public function __construct($name = null)
    {
        $this->createdAt = time();
        //
        $this->id = ++static::$increment;
        if ($name !== null) {
            $this->name = $name;
        }
    }

    public function getDicesSummary()
    {
        $result = array_fill(1, 6, 0);
        foreach ($this->members as $id) {
            /* @var User $member */
            $member = Storage::getPlayerById($id);
            if (!$member) continue;
            foreach ($member->getDicesSummary() as $key => $value) {
                $result[$key] += $value;
            }
        }
        return $result;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return Room
     */
    public function setName(string $name): Room
    {
        $this->name = $name;
        Storage::setRoom($this);
        return $this;
    }

    /**
     * @param $creator
     * @return Room
     */
    public function setCreator($creator): Room
    {
        $this->creator = $creator;
        $this->setMembers($creator);
        Storage::setRoom($this);
        return $this;
    }

    /**
     * @param mixed $members
     * @return Room
     */
    public function setMembers($members): Room
    {
        if (is_array($members)) {
            foreach ($members as $member) {
                $this->setMembers($member);
            }
        } else if (!in_array($members, $this->members)) {
            $this->members[] = $members;
            Storage::setRoom($this);
        }
        return $this;
    }

    /**
     * @param bool $zhai
     * @return Room
     */
    public function setZhai(bool $zhai): Room
    {
        $this->zhai = $zhai;
        Storage::setRoom($this);
        return $this;
    }

    /**
     * @param Pick|null $picker
     * @return Room
     */
    public function setPicker($picker): Room
    {
        $this->picker = $picker;
        Storage::setRoom($this);
        return $this;
    }

    /**
     * @param int $diceNum
     * @return Room
     */
    public function setDiceNum(int $diceNum): Room
    {
        if ($diceNum > 0) {
            $this->diceNum = $diceNum;
        }
        Storage::setRoom($this);
        return $this;
    }

    public function leave($uid)
    {
        $members = $this->members;
        $index   = array_search($uid, $members);
        if ($index !== false) {
            unset($members[$index]);
            $this->members = array_values($members);
        }
        if ($this->cursor + 1 > count($this->members)) {
            // 重置游标
            $this->cursor = 0;
        }
        if ($uid == $this->creator && empty(count($members))) {
            Storage::delRoom($this->id);
        } else {
            $this->creator = array_shift($members);
            Storage::setRoom($this);
        }
        return true;
    }

    /**
     * @param int $status
     * @return Room
     */
    public function setStatus(int $status): Room
    {
        $this->status = $status;
        Storage::setRoom($this);
        return $this;
    }

    public function cursorUser()
    {
        return $this->members[$this->cursor] ?? null;
    }

    public function prevUser()
    {
        if ($this->cursor < 1) {
            $index = count($this->members) - 1;
            $index = $index > 0 ? $index : 0;
            return $this->members[$index];
        }
        $index = $this->cursor - 1;
        return $this->members[$index];
    }

    public function moveCursor()
    {
        if ($this->cursor + 1 >= count($this->members)) {
            $this->cursor = 0;
        } else $this->cursor++;
        Storage::setRoom($this);
        return $this;
    }

    public function computeWinner()
    {
        // 被劈者的猜点
        $guessReferto    = $this->picker->referto->guess;
        $pointResult     = $this->getDicesSummary();
        $guessPointTotal = $pointResult[$guessReferto->point];
        if (!$this->zhai) {
            // 不叫斋就把1点数加进去
            $guessPointTotal += $pointResult[1];
        }
        if ($guessPointTotal >= $guessReferto->num) {
            // 被劈者赢
            $this->setWinner($this->picker->referto);// 被劈者
            $this->setLoser($this->picker->sponsor);
        } else {
            $this->setWinner($this->picker->sponsor);//
            $this->setLoser($this->picker->referto);// 被劈者
        }
        return $this;
    }

    /**
     * @param bool $loseHalf
     * @return Room
     */
    public function setLoseHalf(bool $loseHalf): Room
    {
        $this->loseHalf = $loseHalf;
        return $this;
    }

    /**
     * @param mixed $winner
     * @return Room
     */
    public function setWinner($winner)
    {
        $this->winner = $winner;
        Storage::setRoom($this);
        return $this;
    }

    /**
     * @return User
     */
    public function getWinner()
    {
        return $this->winner;
    }

    /**
     * @return User
     */
    public function getLoser()
    {
        return $this->loser;
    }

    /**
     * @param mixed $loser
     * @return Room
     */
    public function setLoser($loser)
    {
        $this->loser = $loser;
        return $this;
    }

    public function resetMembers()
    {
        foreach ($this->members as $id) {
            $player = Storage::getPlayerById($id);
            if (!$player) continue;
            $player->guess = null;
            $player->roll();
        }
    }

    /**
     * @param int $memberLimit
     * @return Room
     */
    public function setMemberLimit(int $memberLimit): Room
    {
        $this->memberLimit = $memberLimit;
        return $this;
    }

    /**
     * @return int
     */
    public function getMemberLimit(): int
    {
        return $this->memberLimit;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
