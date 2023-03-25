<?php


namespace app\entity;


abstract class Entity
{

    public function getUniqid()
    {
        return $this->id ?? null;
    }

    static public function make()
    {
        return new static(...func_get_args());
    }

    static public function fromJson($json)
    {
        if (!is_array($json)) {
            $data = json_decode($json, true);
        } else $data = $json;
        $instance = new static();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->$key = $value;
            }
        }
        return $instance;
    }

    public function __toString()
    {
        return json_encode($this, JSON_UNESCAPED_UNICODE);
    }
}
