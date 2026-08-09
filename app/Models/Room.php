<?php
class Room extends Model
{
    protected static $table = 'rooms';

    public static function getFiltered($page, $perPage = 20)
    {
        $countSql = "SELECT COUNT(*) FROM rooms";
        $totalItems = (int) self::fetchColumn($countSql);
        $pagination = self::paginate($totalItems, $page, $perPage);

        $sql = "
            SELECT
                r.*,
                COUNT(DISTINCT e.id) AS equipment_count,
                GROUP_CONCAT(
                    CONCAT(u.firstname, ' ', u.lastname)
                    ORDER BY u.lastname, u.firstname
                    SEPARATOR ', '
                ) AS managers
            FROM rooms r
            LEFT JOIN equipment e ON e.room_id = r.id
            LEFT JOIN room_managers rm ON rm.room_id = r.id
            LEFT JOIN users u ON u.id = rm.user_id
            GROUP BY r.id, r.name, r.created_at, r.updated_at
            ORDER BY r.name ASC
            LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
        ";
        $rows = self::fetchAll($sql);

        return [
            'rows' => $rows,
            'pagination' => $pagination,
        ];
    }

    public static function getAll()
    {
        $sql = "SELECT * FROM rooms ORDER BY name ASC";
        return self::fetchAll($sql);
    }

    public static function isNameTaken($name, $excludeId = 0)
    {
        $sql = "SELECT COUNT(*) FROM rooms WHERE name = ? AND id != ?";
        return (int) self::fetchColumn($sql, [$name, $excludeId]) > 0;
    }

    public static function equipmentCount($id)
    {
        $sql = "SELECT COUNT(*) FROM equipment WHERE room_id = ?";
        return (int) self::fetchColumn($sql, [$id]);
    }
}
