<?php
class SystemLog extends Model
{
    protected static $table = 'system_logs';

    public static function log($userId, $action, $details = null)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        // ใช้เวลาประเทศไทยชัดเจน กัน MySQL TIMESTAMP ช้ากว่า 14 ชม. บน InfinityFree
        $now = date('Y-m-d H:i:s');
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $ip,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public static function getFiltered($search, $page, $perPage = 20)
    {
        $where = '';
        $params = [];

        if ($search) {
            $where = "WHERE sl.action LIKE ? OR sl.details LIKE ? OR CONCAT(u.firstname, ' ', u.lastname) LIKE ? OR sl.user_id LIKE ? OR u.email LIKE ? OR sl.ip_address LIKE ?";
            $like = "%{$search}%";
            $params = [$like, $like, $like, $like, $like, $like];
        }

        $countSql = "
            SELECT COUNT(*)
            FROM system_logs sl
            LEFT JOIN users u ON u.id = sl.user_id
            {$where}
        ";
        $totalItems = (int) self::fetchColumn($countSql, $params);
        $pagination = self::paginate($totalItems, $page, $perPage);

        $sql = "
            SELECT
                sl.*,
                CONCAT(u.firstname, ' ', u.lastname) AS user_name,
                u.email AS user_email,
                u.role AS user_role
            FROM system_logs sl
            LEFT JOIN users u ON u.id = sl.user_id
            {$where}
            ORDER BY sl.created_at DESC
            LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
        ";
        $rows = self::fetchAll($sql, $params);

        return [
            'rows' => $rows,
            'pagination' => $pagination,
        ];
    }

    public static function totalCount()
    {
        $sql = "SELECT COUNT(*) FROM system_logs";
        return (int) self::fetchColumn($sql);
    }
}
