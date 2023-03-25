<?php

namespace app\command;

use app\entity\Room;
use app\entity\User;
use app\game\Play;
use app\game\Storage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Test extends Command
{
    protected static $defaultName        = 'test';
    protected static $defaultDescription = 'Test';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Name description');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $room = new Room('生死局');
        $roomId = $room->getId();
        $play = new Play($room);// 创建游戏模型
        // 产生4个筛子
        $diceNum = 4;
        $me    = User::make(User::nextId(), '房主马云')->createDices($diceNum)->setRoom($roomId)->guess(5, 3);
        $user2 = User::make(User::nextId(), '马化腾')->createDices($diceNum)->setRoom($roomId)->guess(5, 4);
        $user3 = User::make(User::nextId(), '刘强东')->createDices($diceNum)->setRoom($roomId)->guess(6, 5);
        $user4 = User::make(User::nextId(), '李彦宏')->createDices($diceNum)->setRoom($roomId)->guess(7, 5);
        $user5 = User::make(User::nextId(), '雷军')->createDices($diceNum)->setRoom($roomId)->guess(7, 6);
        $room->setMembers([$me, $user2, $user3, $user4, $user5]);
        $room->setCreator($me);
        return self::SUCCESS;
    }

}
