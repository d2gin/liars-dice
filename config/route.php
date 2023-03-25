<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use app\controller\IndexController;
use app\controller\PlayerController;
use app\controller\RoomController;
use Webman\Route;

Route::group('/api', function () {
    // 公共接口
    Route::add(['OPTIONS','GET'],'/index/roomList', [IndexController::class, 'roomList']);
    Route::add(['OPTIONS','GET'],'/index/statistics', [IndexController::class, 'statistics']);

    // 房间相关
    Route::add(['OPTIONS','GET'],'/room/detail', [RoomController::class, 'detail']);
    Route::add(['OPTIONS','POST'],'/room/create', [RoomController::class, 'create']);
    Route::add(['OPTIONS','POST'],'/room/leave', [RoomController::class, 'leave']);
    Route::add(['OPTIONS','POST'],'/room/enter', [RoomController::class, 'enter']);
    Route::add(['OPTIONS','POST'],'/room/start', [RoomController::class, 'start']);
    Route::add(['OPTIONS','POST'],'/room/guess', [RoomController::class, 'guess']);
    Route::add(['OPTIONS','POST'],'/room/pick', [RoomController::class, 'pick']);
    Route::add(['OPTIONS','POST'],'/room/restart', [RoomController::class, 'restart']);
    Route::add(['OPTIONS','POST'],'/room/kickOut', [RoomController::class, 'kickOut']);

    // 玩家相关
    Route::add(['OPTIONS','GET'],'/player/detail', [PlayerController::class, 'detail']);
    Route::add(['OPTIONS','POST'],'/player/bind', [PlayerController::class, 'bind']);
    Route::add(['OPTIONS','POST'],'/player/rollDice', [PlayerController::class, 'rollDice']);
    Route::add(['OPTIONS','POST'],'/player/bindConnectionId', [PlayerController::class, 'bindConnectionId']);
});
