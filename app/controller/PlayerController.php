<?php


namespace app\controller;

use app\entity\User;
use app\game\Storage;
use app\response\HttpData;
use support\Request;

class PlayerController extends BaseController
{
    public function detail()
    {
        if (!$this->user) {
            return HttpData::error('未绑定用户信息');
        }
        $user = $this->user;
        $room = $user->getRoom();
        return HttpData::success('success', [
            'id'            => $user->id,
            'nickname'      => $user->nickname,
            'sex'           => $user->sex,
            'token'         => $user->token,
            'connection_id' => $user->connectionId,
            'dices'         => $user->getDices(),
            'guess'         => $user->guess,
            'room'          => $room ? [
                'id'            => $room->getId(),
                'name'          => $room->name,
                'status'        => $room->status,
                'dice_num'      => $room->diceNum,
                'member_total'  => count($room->members),
                'win_score'     => $room->winScore,
                'lose_score'    => $room->loseScore,
                'is_turn_to_me' => $user->isTurnToMe(),
                'lose_half'     => $room->loseHalf,
            ] : [],
        ]);
    }

    public function bind(Request $request)
    {
        $nickname     = trim($request->post('nickname'));
        $sex          = $request->post('sex', 1);
        $sex          = in_array($sex, [1, 2]) ? $sex : 1;
        $connectionId = $request->post('connection_id');
        if (mb_strlen($nickname) > 6) {
            return HttpData::error("名字不能超过6个字");
        } else if (!$connectionId) {
            return HttpData::error('请确保websocket已连接');
        } else if ($nickname == '') {
            return HttpData::error('昵称不能为空');
        }
        $user = $this->user;
        if (!$user) {
            $user = User::make()->setId(Storage::incrPlayerID());
        }
        $user->setOnline(true);
        $user->setSex($sex)->setConnectionId($connectionId)->setNickname($nickname);
        Storage::setPlayer($user);
        $this->pusher->broadcast('statistics_update');
        return HttpData::success('绑定成功', [
            'user_id'       => $user->id,
            'token'         => $user->token,
            'connection_id' => $user->connectionId,
        ]);
    }

    /**
     * 摇骰子
     * @return HttpData
     */
    public function rollDice()
    {
        $room = $this->user->getRoom();
        if ($room->picker) {
            return HttpData::error('房间有人劈骰，无法摇骰');
        }
        $this->user->roll();
        //@todo 推送
        return HttpData::success('success');
    }

    public function bindConnectionId(Request $request)
    {
        if (!$this->user) {
            return HttpData::error('请先绑定玩家信息');
        }
        $connectionId = $request->post('connection_id');
        $this->user->setConnectionId($connectionId);
        $this->pusher->roomInsideUpdate($this->user->room);
        return HttpData::success('绑定成功');
    }
}
