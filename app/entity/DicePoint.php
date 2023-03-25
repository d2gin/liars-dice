<?php


namespace app\entity;


class DicePoint extends Entity
{
    public $value;

    public function __construct($value = null)
    {
        if ($value) {
            $this->value = $value;
        }
    }

    static public function pointVerify($point)
    {
        if (preg_match('/[1-6]{1}/', $point)) {
            return true;
        }
        return false;
    }

    /**
     * @param mixed $value
     * @return DicePoint
     * @throws \Exception
     */
    public function setValue($value)
    {
        if (!self::pointVerify($value)) {
            throw new \Exception('`DicePoint::$value` incoming illegal value');
        }
        $this->value = $value;
        return $this;
    }
}
