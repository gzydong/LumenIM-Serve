<?php

namespace App\Services\Common;

use SwooleTW\Http\Websocket\Facades\Room;

/**
 * 聊天室管理(群房间信息是存放 Swoole table 中,支持集群环境)
 *
 * Class RoomManageService
 * @package App\Services
 */
class RoomManageService
{
    const ROOM_GROUP_PREFIX = 'group:chat';

    /**
     * 将指定的用户添加到指定的群聊（用户加入群聊时调用）
     *
     * @param int $group_id 群ID
     * @param int $user_id 用户ID
     */
    public function bindUserToRoom(int $group_id, int $user_id)
    {
        $room = $this->getRoomGroupName($group_id);
        $fds = app('client.manage')->findUserIdFds($user_id);
        if ($fds) {
            foreach ($fds as $fd) {
                Room::add($fd, $room);
            }
        }
    }

    /**
     * 将指定的客户端fd退出所有聊天室（客户端关闭连接时调用）
     *
     * @param int $fd 客户端fd
     */
    public function removeFdRoomAll(int $fd)
    {
        if ($rooms = Room::getRooms($fd)) {
            Room::delete($fd, $rooms);
        }
    }

    /**
     * 删除指定的聊天室（解散群聊时调用）
     *
     * @param int $group_id 群ID
     */
    public function removeRoom(int $group_id)
    {
        $room_name = $this->getRoomGroupName($group_id);
        $fds = Room::getClients($room_name);
        if ($fds) {
            foreach ($fds as $fd) {
                Room::delete($fd, $room_name);
            }
        }
    }

    /**
     * 将指定的用户从聊天室中删除（用户退群时调用）
     *
     * @param int $group_id 群ID
     * @param int $user_id 用户ID
     */
    public function removeRoomUser(int $group_id, int $user_id)
    {
        $room = $this->getRoomGroupName($group_id);
        $fds = app('client.manage')->findUserIdFds($user_id);
        if ($fds) {
            foreach ($fds as $fd) {
                Room::delete($fd, $room);
            }
        }
    }

    /**
     * 获取群ID的房间名称
     *
     * @param int $group_id 群ID
     * @return string 群聊房间名
     */
    public function getRoomGroupName(int $group_id)
    {
        return self::ROOM_GROUP_PREFIX . ':' . $group_id;
    }
}
