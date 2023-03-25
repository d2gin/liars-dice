<?php


namespace support;


use Webman\Http\Request;
use Webman\Http\Response;

class ResponseException extends exception\BusinessException
{

    public function render(Request $request): ?Response
    {
        if ($request->expectsJson()) {
            $code = $this->getCode();
            $json = ['code' => $code ?: -1, 'message' => $this->getMessage()];
            return new Response(200, ['Content-Type' => 'application/json'],
                                json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        return new Response(200, [], $this->getMessage());
    }
}
