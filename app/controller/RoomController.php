<?php


namespace app\controller;


use app\entity\DicePoint;
use app\entity\Room;
use app\game\Pusher;
use app\game\Storage;
use app\response\HttpData;
use support\exception\BusinessException;
use support\ResponseException;
use Webman\Http\Request;

class RoomController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->user) {
            throw  new ResponseException('请先绑定玩家档案');
        }
    }

    /**
     * 房间信息
     * @return HttpData
     */
    public function detail()
    {
        $room = $this->user->getRoom();
        if (!$room) {
            return HttpData::error('找不到房间');
        }
        $find = array_filter($room->members, function ($member) {
            return $member == $this->user->id;
        });
        if (empty($find)) {
            return HttpData::error('不是该房间玩家');
        }
        $members      = [];
        $prevUser     = $room->prevUser();
        $prevGuess    = null;
        $isSettlement = $room->status == Room::STATUS_SETTLEMENT;
        foreach ($room->members as $member) {
            //if ($member == $this->user->id) continue;
            $player = Storage::getPlayerById($member);
            if (!$player) continue;
            $isPrev    = $member == $prevUser && $member != $this->user->id;
            $members[] = [
                'id'        => $player->id,
                'nickname'  => $player->nickname,
                'score'     => $player->score,
                'win'       => $player->win,
                'lose'      => $player->lose,
                'guess'     => $player->guess,
                'turn_to'   => $player->isTurnToMe(),
                'is_online' => $player->online,
                'is_prev'   => $player->guess ? $isPrev : false,
                'dices'     => $isSettlement ? $player->getDices() : [],
            ];
            if ($isPrev) {
                $prevGuess = $player->guess;
            }
        }
        $settlementResult = '';
        if ($isSettlement) {
            $referto          = $room->picker->referto;
            $point            = $referto->guess->point;
            $pointResult      = $room->getDicesSummary();
            $settlementResult = "{$pointResult[$point]}个{$point}";
        }
        return HttpData::success('success', [
            'id'                => $room->getId(),
            'creator'           => $room->creator,
            'name'              => $room->name,
            'status'            => $room->status,
            'dice_num'          => $room->diceNum,
            'member_total'      => count($room->members),
            'win_score'         => $room->winScore,
            'lose_score'        => $room->loseScore,
            'members'           => $members,
            'prev_guess'        => $prevGuess,
            'lose_half'         => $room->loseHalf,
            'settlement_result' => $settlementResult,
            'is_zhai'           => $room->zhai,
            'winner'            => $settlementResult ? [
                'id'       => $room->getWinner()->id,
                'nickname' => $room->getWinner()->nickname,
                'guess'    => $room->getWinner()->guess,
            ] : null,
            'picker'            => $settlementResult ? [
                'referto' => [
                    'id'       => $room->picker->referto->id,
                    'nickname' => $room->picker->referto->nickname,
                    'guess'    => $room->picker->referto->guess,
                ],
                'sponsor' => [
                    'id'       => $room->picker->sponsor->id,
                    'nickname' => $room->picker->sponsor->nickname,
                    'guess'    => $room->picker->sponsor->guess,
                ],
            ] : null,
        ]);
    }

    /**
     * 创建房间
     * @param Request $request
     * @return HttpData
     */
    public function create(Request $request)
    {
        $name        = trim($request->post('room_name'));
        $diceNum     = intval($request->post('dice_num', 4));
        $memberLimit = intval($request->post('member_limit', 20));
        $loseHalf    = boolval($request->post('lose_half', true));
        if (!$this->user) {
            return HttpData::error('先创建用户档案');
        } else if (!$this->user->connectionId) {
            return HttpData::error('请确保websocket已连接');
        } else if ($this->user->room && $room = $this->user->getRoom()) {
            return HttpData::error("请先退出房间[{$room->name}]");
        } else if ($name == '') {
            return HttpData::error('房间名称不能为空');
        } else if (mb_strlen($name) > 6) {
            return HttpData::error('房间名称不能超过6个字');
        } else if ($diceNum > 15) {
            return HttpData::error('骰子数上限为15个');
        } else if ($diceNum < 1) {
            return HttpData::error('至少每人1个骰子');
        } else if ($memberLimit < 2) {
            return HttpData::error('房间人数至少2人');
        }
        $room = new Room($name);
        $room->setDiceNum($diceNum);
        $room->setMemberLimit($memberLimit);
        $room->setLoseHalf($loseHalf);
        $room->setCreator($this->user->id);
        $this->user->setRoom($room->getId())->createDices($diceNum);
        Storage::setRoom($room);
        $this->pusher->roomsUpdate();
        return HttpData::success('创建成功');
    }

    /**
     * 退出房间
     * @return HttpData
     */
    public function leave()
    {
        if ($this->user->room) {
            $room   = $this->user->getRoom();
            $roomId = $room->getId();
            $room->leave($this->user->id);
            $this->user->reset();
            $this->pusher->roomsUpdate();
            $this->pusher->roomInsideUpdate($roomId);
            $this->pusher->toast("玩家[{$this->user->nickname}]退出房间", $roomId);
            return HttpData::success('已退出');
        }
        return HttpData::error('error');
    }

    /**
     * 进入房间
     * @param Request $request
     * @return HttpData
     */
    public function enter(Request $request)
    {
        $roomId = $request->post('room_id');
        $room   = Storage::getRoom($roomId);
        if (!$this->user) {
            return HttpData::error('请先建立玩家档案');
        } else if (!$room) {
            return HttpData::error('房间不存在');
        } else if (in_array($this->user->id, $room->members)) {
            return HttpData::error('已在房间');
        } else if ($room->status !== Room::STATUS_PREPARING) {
            return HttpData::error('房间正在游戏');
        } else if ($this->user->room && $room = $this->user->getRoom()) {
            return HttpData::error("请先退出房间[{$room->name}]");
        } else if ($room->memberLimit <= count($room->members)) {
            return HttpData::error('房间满人');
        }
        $room->setMembers($this->user->id);
        $this->user->createDices($room->diceNum)->setRoom($roomId);
        $this->pusher->roomsUpdate();
        $this->pusher->roomInsideUpdate($room->getId());
        $this->pusher->toast("玩家[{$this->user->nickname}]进入房间", $room->getId());
        return HttpData::success('进入房间[' . $room->name . ']');
    }

    /**
     * 开局
     * @return HttpData
     */
    public function start()
    {
        $room = $this->user->getRoom();
        if (!$room || $room->creator !== $this->user->id) {
            return HttpData::error('参数异常');
        }
        $room->setStatus(Room::STATUS_GAMING);
        $room->setZhai(false);
        $room->setPicker(null);
        $room->setWinner(null);
        $room->resetMembers();
        $this->pusher->broadcast('statistics_update');
        $this->pusher->roomsUpdate();
        $this->pusher->roomInsideUpdate($room->getId());
        $this->pusher->toast('游戏开局，准备猜骰', $room->getId());
        return HttpData::success('已开始');
    }

    public function restart()
    {
        $room = $this->user->getRoom();
        if (!$room) {
            return HttpData::error('非法请求');
        } else if ($room->creator !== $this->user->id) {
            return HttpData::error('不是房主');
        }
        $room->resetMembers();
        $room->setStatus(Room::STATUS_PREPARING);
        $this->pusher->broadcast('statistics_update');
        $this->pusher->roomsUpdate();
        $this->pusher->roomInsideUpdate($room->getId());
        $this->pusher->toast('房间重新开放，等待玩家', $room->getId());
        return HttpData::success('房间开放');
    }

    /**
     * 猜骰
     * @param Request $request
     * @return HttpData
     */
    public function guess(Request $request)
    {
        $num      = intval($request->post('num', 0));
        $point    = intval($request->post('point', 0));
        $isZhai   = $request->post('is_zhai', null);// 叫斋
        $isPozhai = $request->post('is_pozhai', null);// 破斋
        $room     = $this->user->getRoom();
        $prevUser = Storage::getPlayerById($room->prevUser());
        if ($room->status !== Room::STATUS_GAMING) {
            return HttpData::error('暂未开局');
        } else if (!$this->user->isTurnToMe()) {
            return HttpData::error('还没轮到你');
        } else if ($num <= 0 || !DicePoint::pointVerify($point)) {
            return HttpData::error('注意叫骰格式');
        } else if ($num < count($room->members)) {
            return HttpData::error('个数必须大于等于人头数');
        } else if ($prevUser && $prevUser->guess
            && $prevUser->guess->num >= $num
            && $prevUser->guess->point >= $point) {
            return HttpData::error('叫骰不能比上家小');
        } else if ($isPozhai && $room->zhai && $prevUser && ($num < $prevUser->guess->num * 2 || $point < $prevUser->guess->point * 2)) {
            return HttpData::error('破斋叫骰的个数不能小于上家的2倍');
        } else if ($isPozhai && $room->zhai && !$prevUser) {
            return HttpData::error('无法破斋');
        }
        if ($isZhai) {
            $room->setZhai(true);
        } else if ($isPozhai) {
            $room->setZhai(false);
        }
        $this->user->guess($num, $point);
        $this->pusher->roomsUpdate();
        $this->pusher->roomInsideUpdate($room->getId());
        return HttpData::success('成功猜骰');
    }

    /**
     * 劈
     * @param Request $request
     * @return HttpData
     * @throws \Exception
     */
    public function pick(Request $request)
    {
        $room = $this->user->getRoom();
        if ($room->status !== Room::STATUS_GAMING) {
            return HttpData::error('暂未开局');
        }
        $id     = $request->post('id');
        $player = Storage::getPlayerById($id);
        $this->user->pick($player);
        // 重新加载room实例
        $room   = $this->user->getRoom();
        $roomId = $room->getId();
        $pusher = Pusher::instance();
        $pusher->toast("{$this->user->nickname} 劈 {$player->nickname}", $roomId);
        // 计算胜者
        $room->setStatus(Room::STATUS_SETTLEMENT)->computeWinner();
        $room->getWinner()->win();
        $room->getLoser()->lose();
        $pusher->notice('对局结算成功', $roomId);
        $this->pusher->roomsUpdate();
        $this->pusher->roomInsideUpdate($room->getId());
        return HttpData::success('劈Ta成功，开始结算');
    }

    public function kickOut(Request $request)
    {
        $id   = $request->post('id');
        $room = $this->user->getRoom();
        if ($room->creator != $this->user->id) {
            return HttpData::error('不是房主');
        } else if ($id == $this->user->id) {
            return HttpData::error('不能踢掉自己');
        }
        $player = Storage::getPlayerById($id);
        if ($player) {
            $player->setRoom(null);
            $room->leave($id);
            $player->reset();
            $this->pusher->channel('room#' . $room->getId())->emit('kicked_out', ['id' => $id]);
            $this->pusher->roomInsideUpdate($room->getId());
            $this->pusher->roomsUpdate();
            $this->pusher->toast($player->nickname . ' 被踢出房间', $room->getId());
        }
        return HttpData::success('已踢出房间');
    }
}
