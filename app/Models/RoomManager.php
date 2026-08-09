<?php
class RoomManager extends Model
{
    protected static $table = 'room_managers';

    public static function getAll()
    {
        $sql = "
            SELECT
                rm.*,
                r.name AS room_name,
                u.firstname,
                u.lastname
            FROM room_managers rm
            LEFT JOIN rooms r ON r.id = rm.room_id
            LEFT JOIN users u ON u.id = rm.user_id
            ORDER BY r.name ASC, u.lastname ASC, u.firstname ASC
        ";
        return self::fetchAll($sql);
    }

    public static function getByRoom($roomId)
    {
        $sql = "
            SELECT
                rm.*,
                u.firstname,
                u.lastname
            FROM room_managers rm
            LEFT JOIN users u ON u.id = rm.user_id
            WHERE rm.room_id = ?
            ORDER BY u.lastname ASC, u.firstname ASC
        ";
        return self::fetchAll($sql, [$roomId]);
    }

    public static function getManagedRooms($userId)
    {
        $sql = "
            SELECT r.name
            FROM room_managers rm
            LEFT JOIN rooms r ON r.id = rm.room_id
            WHERE rm.user_id = ?
            ORDER BY r.name ASC
        ";
        $rows = self::fetchAll($sql, [$userId]);
        return array_column($rows, 'name');
    }

    public static function syncHoldersFill()
    {
        $sql = "
            UPDATE equipment e
            INNER JOIN room_managers rm ON rm.room_id = e.room_id
            SET e.holder_id = rm.user_id
            WHERE e.holder_id IS NULL
        ";
        $stmt = self::query($sql);
        return $stmt->rowCount();
    }

    public static function syncHoldersOverwrite()
    {
        $sql = "
            UPDATE equipment e
            INNER JOIN room_managers rm ON rm.room_id = e.room_id
            SET e.holder_id = rm.user_id
        ";
        $stmt = self::query($sql);
        return $stmt->rowCount();
    }

    public static function getAllWithRoomAndUser()
    {
        $sql = "
            SELECT rm.room_id, r.name AS room_name, rm.user_id, u.firstname, u.lastname
            FROM room_managers rm
            LEFT JOIN rooms r ON r.id = rm.room_id
            LEFT JOIN users u ON u.id = rm.user_id
            ORDER BY r.name ASC, u.lastname ASC, u.firstname ASC
        ";
        return self::fetchAll($sql);
    }

    public static function getManagedRoomIds($userId)
    {
        $sql = "SELECT rm.room_id FROM room_managers rm WHERE rm.user_id = ?";
        $rows = self::fetchAll($sql, [$userId]);
        return array_column($rows, 'room_id');
    }

    public static function getManagedRoomCount($userId)
    {
        $sql = "SELECT r.id, r.name, COUNT(e.id) AS eq_count
            FROM room_managers rm
            JOIN rooms r ON r.id = rm.room_id
            LEFT JOIN equipment e ON e.room_id = r.id
            WHERE rm.user_id = ?
            GROUP BY r.id, r.name";
        return self::fetchAll($sql, [$userId]);
    }

    public static function isOwner($userId, $roomId)
    {
        $sql = "SELECT COUNT(*) FROM room_managers WHERE user_id = ? AND room_id = ?";
        return (int) self::fetchColumn($sql, [$userId, $roomId]) > 0;
    }
}
