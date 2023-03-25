<?php

namespace app\response;

use support\Response;

class HttpData extends Response
{

    public $data    = [];
    public $code    = self::CODE_NORMAL_SUCCESS;
    public $message = '';

    const CODE_NORMAL_ERROR   = -1;
    const CODE_NORMAL_SUCCESS = 0;

    static function success($message = '', $data = [])
    {
        return static::make()->setMessage($message)->setData($data);
    }

    static function error($message = '', $data = [])
    {
        return static::make()->setMessage($message)->setCode(static::CODE_NORMAL_ERROR)->setData($data);
    }

    static public function make()
    {
        return new static(...func_get_args());
    }

    static public function fromJson($json)
    {
        $instance = static::make();
        foreach ($json as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->$key = $value;
            }
        }
        return $instance;
    }

    /**
     * @param array $data
     * @return HttpData
     */
    public function setData($data): HttpData
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param int $code
     * @return HttpData
     */
    public function setCode(int $code): HttpData
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @param string $message
     * @return HttpData
     */
    public function setMessage(string $message): HttpData
    {
        $this->message = $message;
        return $this;
    }

    public function __toString()
    {
        $this->withHeader('Content-Type', 'application/json');
        $this->withBody(json_encode(['data' => $this->data, 'code' => $this->code, 'message' => $this->message], JSON_UNESCAPED_UNICODE));
        return parent::__toString();
    }
}
