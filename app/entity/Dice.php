<?php


namespace app\entity;


class Dice extends Entity
{
    protected $id;
    /**
     * 显示的点数
     * @var DicePoint $point
     */
    public           $point;
    protected        $sides     = [];
    static protected $increment = 0;

    public function __construct()
    {
        // 产生id
        $this->id = ++static::$increment;
        // 六面点数
        $this->sides = [
            DicePoint::make(1),
            DicePoint::make(2),
            DicePoint::make(3),
            DicePoint::make(4),
            DicePoint::make(5),
            DicePoint::make(6),
        ];
        $this->roll();
    }

    /**
     * 摇骰子
     * @return $this
     */
    public function roll()
    {
        $this->point = $this->sides[array_rand($this->sides)];
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param DicePoint $point
     * @return Dice
     * @throws \Exception
     */
    public function setPoint($point)
    {
        if (!in_array($point, $this->sides)) {
            throw new \Exception('`Dice::$point` incoming illegal value');
        }
        $this->point = $point;
        return $this;
    }

}
