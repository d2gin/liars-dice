<?php


namespace app\controller;


use app\entity\User;
use app\game\Pusher;
use app\game\Storage;
use support\Context;
use Webman\Http\Request;

abstract class BaseController
{

    protected $token;
    /* @var User $user */
    protected $user;
    /* @var Pusher $pusher */
    protected $pusher;

    public function __construct()
    {
        $request      = Context::get(Request::class);
        $this->token  = $request->header('token');
        $this->pusher = Pusher::instance();
        if ($this->token) {
            $this->user = Storage::getPlayer($this->token ?? null);
            if ($this->user) {
                $this->user->updatedAt = time();
                $this->user->setOnline(true);
                // 活跃房间
                if ($room = $this->user->getRoom()) {
                    $room->setUpdatedAt(time());
                }
            }
        }
    }
}
