<?php


namespace app\entity;


class Pick extends Entity
{
    /**
     * @var User $sponsor
     */
    public $sponsor;
    /**
     * @var User $referto
     */
    public $referto;
    /**
     * @var bool $reverse
     */
    public $reverse = false;

    public function __construct($sponsor, $referto)
    {
        $this->sponsor = $sponsor;
        $this->referto = $referto;
    }

    /**
     * @param bool $reverse
     * @return Pick
     */
    public function setReverse(bool $reverse): Pick
    {
        $this->reverse = $reverse;
        return $this;
    }
}
