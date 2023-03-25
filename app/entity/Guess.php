<?php


namespace app\entity;


class Guess extends Entity
{
    public $num   = 0;
    public $point = 1;
    public $zhai  = false;

    public function __construct($n = 0, $point = 0, $zhai = false)
    {
        $this->set($n, $point);
        if (is_bool($zhai)) {
            $this->zhai = $zhai;
        }
    }

    /**
     * @param int $num
     * @param int $point
     */
    public function set($num, $point)
    {
        if (!DicePoint::pointVerify($point)) {
            throw new \Exception('Invalid `Guess::$point` value');
        }
        $this->num   = $num;
        $this->point = $point;
    }

    /**
     * @param bool $zhai
     * @return Guess
     */
    public function setZhai(bool $zhai): Guess
    {
        $this->zhai = $zhai;
        return $this;
    }
}
