<?php
class Repair extends Model
{
    protected static $table = 'repair';

    public static function getFiltered($status, $page, $perPage = 20)
    {
        $where = [];
        $params = [];

        if ($status !== '' && $status !== null) {
            $where[] = "r.status = ?";
            $params[] = $status;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) FROM repair r {$whereSql}";
        $total = (int) self::fetchColumn($countSql, $params);

        $pagination = self::paginate($total, $page, $perPage);

        $dataSql = "SELECT r.*, e.code AS eq_code, i.name AS item_name, i.brand, i.model, u.firstname, u.lastname, u.email
            FROM repair r
            LEFT JOIN equipment e ON r.equipment_id = e.id
            LEFT JOIN items i ON e.item_id = i.id
            LEFT JOIN users u ON r.user_id = u.id
            {$whereSql}
            ORDER BY r.created_at DESC
            LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
        $repairs = self::fetchAll($dataSql, $params);

        return [
            'repairs' => $repairs,
            'pagination' => $pagination,
            'total' => $total,
        ];
    }

    public static function getDetail($id)
    {
        $sql = "SELECT r.*, e.code AS eq_code, rm.name AS room, i.name AS item_name, i.brand, i.model,
                u.firstname, u.lastname, u.email, u.role
            FROM repair r
            LEFT JOIN equipment e ON r.equipment_id = e.id
            LEFT JOIN rooms rm ON e.room_id = rm.id
            LEFT JOIN items i ON e.item_id = i.id
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.id = ?";
        return self::fetchOne($sql, [$id]);
    }

    public static function getStatusCounts()
    {
        $sql = "SELECT status, COUNT(*) AS cnt FROM repair GROUP BY status";
        $rows = self::fetchAll($sql);

        $counts = [
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cannot_fix' => 0,
        ];

        foreach ($rows as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int) $row['cnt'];
            }
        }

        return $counts;
    }

    public static function getMonthlyStats($months = 6)
    {
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
            FROM repair
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY ym
            ORDER BY ym ASC";
        $rows = self::fetchAll($sql, [$months]);

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['ym']] = (int) $row['cnt'];
        }

        $stats = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = new DateTime();
            $date->modify("-{$i} months");
            $yearMonth = $date->format('Y-m');
            $label = $date->format('M Y');

            $stats[] = [
                'month' => $yearMonth,
                'label' => $label,
                'count' => $counts[$yearMonth] ?? 0,
            ];
        }

        return $stats;
    }

    public static function getRecent($limit = 5)
    {
        $sql = "SELECT r.*, e.code AS eq_code, i.name AS item_name, u.firstname, u.lastname
            FROM repair r
            LEFT JOIN equipment e ON r.equipment_id = e.id
            LEFT JOIN items i ON e.item_id = i.id
            LEFT JOIN users u ON r.user_id = u.id
            ORDER BY r.created_at DESC
            LIMIT " . (int) $limit;
        return self::fetchAll($sql);
    }

    public static function getByUser($userId)
    {
        $sql = "SELECT r.*, e.code AS eq_code, i.name AS item_name, i.brand, i.model
            FROM repair r
            LEFT JOIN equipment e ON r.equipment_id = e.id
            LEFT JOIN items i ON e.item_id = i.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC";
        return self::fetchAll($sql, [$userId]);
    }

    public static function getImages($repairId)
    {
        return self::findAllBy('repair_img', 'repair_id', $repairId);
    }

    public static function updateStatus($id, $newStatus)
    {
        self::update($id, ['status' => $newStatus]);

        $equipmentStatusMap = [
            'in_progress' => 'repair',
            'completed' => 'available',
            'cannot_fix' => 'broken',
        ];

        if (isset($equipmentStatusMap[$newStatus])) {
            $repair = self::find($id);
            if ($repair && $repair['equipment_id']) {
                self::update('equipment', $repair['equipment_id'], [
                    'status' => $equipmentStatusMap[$newStatus],
                ]);
            }
        }
    }

    public static function pendingCount()
    {
        return self::count(static::$table, "status = 'pending'");
    }

    public static function totalCount()
    {
        return self::count(static::$table);
    }

    public static function createRepair($equipmentId, $userId, $issue)
    {
        return self::create([
            'equipment_id' => $equipmentId,
            'user_id' => $userId,
            'issue' => $issue,
            'status' => 'pending',
        ]);
    }

    public static function addImage($repairId, $path)
    {
        return self::create('repair_img', [
            'repair_id' => $repairId,
            'path' => $path,
        ]);
    }

    public static function getDetailForUser($id, $userId)
    {
        $sql = "SELECT r.*, e.code AS eq_code, rm.name AS room, i.name AS item_name, i.brand, i.model,
                u.firstname, u.lastname, u.email, u.role
            FROM repair r
            LEFT JOIN equipment e ON r.equipment_id = e.id
            LEFT JOIN rooms rm ON e.room_id = rm.id
            LEFT JOIN items i ON e.item_id = i.id
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.id = ? AND r.user_id = ?";
        return self::fetchOne($sql, [$id, $userId]);
    }

    public static function getByEquipment($equipmentId)
    {
        $sql = "SELECT r.*, u.firstname, u.lastname
            FROM repair r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.equipment_id = ?
            ORDER BY r.created_at DESC
            LIMIT 10";
        return self::fetchAll($sql, [$equipmentId]);
    }

    public static function countByUser($userId)
    {
        return (int) self::count(static::$table, "user_id = ?", [$userId]);
    }

    public static function pendingCountByUser($userId)
    {
        return (int) self::count(static::$table, "user_id = ? AND status = 'pending'", [$userId]);
    }

    public static function getRecentByUser($userId, $limit = 5)
    {
        $sql = "SELECT r.*, e.code AS eq_code, i.name AS item_name
            FROM repair r
            LEFT JOIN equipment e ON r.equipment_id = e.id
            LEFT JOIN items i ON e.item_id = i.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
            LIMIT " . (int) $limit;
        return self::fetchAll($sql, [$userId]);
    }

    public static function countByStatus($status)
    {
        return (int) self::count(static::$table, "status = ?", [$status]);
    }

    public static function getTopBrokenItems($limit = 5)
    {
        $sql = "SELECT i.name, COUNT(r.id) AS cnt
            FROM repair r
            JOIN equipment e ON r.equipment_id = e.id
            JOIN items i ON e.item_id = i.id
            GROUP BY i.id, i.name
            ORDER BY cnt DESC
            LIMIT " . (int) $limit;
        return self::fetchAll($sql);
    }

    public static function getAllForExport()
    {
        $sql = "SELECT r.id, e.code AS eq_code, i.name AS item_name,
            r.issue, r.status, r.created_at,
            u.firstname, u.lastname
            FROM repair r
            LEFT JOIN equipment e ON r.equipment_id = e.id
            LEFT JOIN items i ON e.item_id = i.id
            LEFT JOIN users u ON r.user_id = u.id
            ORDER BY r.created_at DESC";
        return self::fetchAll($sql);
    }
}
