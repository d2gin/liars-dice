<?php

namespace app\controller;

use app\entity\Room;
use app\entity\User;
use app\game\Storage;
use app\response\HttpData;

class IndexController extends BaseController
{
    /**
     * 房间列表
     * @return HttpData
     */
    public function roomList()
    {
        $result = [];
        /* @var Room $room */
        foreach (Storage::getAllRoom() as $room) {
            $result[] = [
                'id'           => $room->getId(),
                'name'         => $room->name,
                'status'       => $room->status,
                'dice_num'     => $room->diceNum,
                'member_total' => count($room->members),
                'member_limit' => $room->memberLimit,
                'win_score'    => $room->winScore,
                'lose_score'   => $room->loseScore,
                'lose_half'    => $room->loseHalf,
                'can_enter'    => $room->status == Room::STATUS_PREPARING && $room->memberLimit > count($room->members),
            ];
        }
        return HttpData::success('', $result);
    }

    public function statistics()
    {
        $online    = 0;
        $gaming    = 0;
        $preparing = 0;
        $res       = [];
        /* @var User $player */
        foreach (Storage::getAllPlayer() as $player) {
            if ($player->online) $online += 1;
            $room = $player->getRoom();
            if ($room) {
                if ($room->status === Room::STATUS_GAMING) $gaming += 1;
                if ($room->status === Room::STATUS_PREPARING) $preparing += 1;
            }
        }
        return HttpData::success('success', [
            'online'    => $online,// 在线
            'gaming'    => $gaming,// 游戏中
            'preparing' => $preparing,// 准备中
        ]);
    }
}
