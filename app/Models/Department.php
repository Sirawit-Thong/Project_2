<?php
class Department extends Model
{
    protected static $table = 'dept';

    public static function getAllWithCounts()
    {
        $sql = "
            SELECT
                d.id,
                d.name,
                d.created_at,
                d.updated_at,
                COUNT(DISTINCT s.id) AS set_count,
                COUNT(DISTINCT e.id) AS equipment_count
            FROM dept d
            LEFT JOIN sets s ON s.dept_id = d.id
            LEFT JOIN items i ON i.set_id = s.id
            LEFT JOIN equipment e ON e.item_id = i.id
            GROUP BY d.id, d.name, d.created_at, d.updated_at
            ORDER BY d.name ASC
        ";
        return self::fetchAll($sql);
    }

    public static function isNameTaken($name, $excludeId = 0)
    {
        $sql = "SELECT COUNT(*) FROM dept WHERE name = ? AND id != ?";
        return (int) self::fetchColumn($sql, [$name, $excludeId]) > 0;
    }

    public static function childSetCount($id)
    {
        $sql = "SELECT COUNT(*) FROM sets WHERE dept_id = ?";
        return (int) self::fetchColumn($sql, [$id]);
    }

    public static function getAll()
    {
        $sql = "SELECT * FROM dept ORDER BY name ASC";
        return self::fetchAll($sql);
    }

    public static function getStatsWithValues()
    {
        // ตามแบบออริจินอล: นับจำนวนครุภัณฑ์ + มูลค่ารวม (ครุภัณฑ์ + รายการ + ชุด) ต่อสาขา
        $deptRows = self::fetchAll("SELECT id, name FROM dept ORDER BY name");

        $stats = [];
        foreach ($deptRows as $d) {
            $sql = "SELECT
                (SELECT COUNT(*) FROM equipment e JOIN items i ON e.item_id = i.id JOIN sets s ON i.set_id = s.id WHERE s.dept_id = ?) AS c,
                (SELECT COALESCE(SUM(e.price), 0) FROM equipment e JOIN items i ON e.item_id = i.id JOIN sets s ON i.set_id = s.id WHERE s.dept_id = ?) AS eq_val,
                (SELECT COALESCE(SUM(i.price * (SELECT COUNT(*) FROM equipment e WHERE e.item_id = i.id)), 0) FROM items i JOIN sets s ON i.set_id = s.id WHERE s.dept_id = ?) AS item_val,
                (SELECT COALESCE(SUM(s.price), 0) FROM sets s WHERE s.dept_id = ? AND s.price > 0 AND EXISTS (SELECT 1 FROM items i JOIN equipment e ON i.id = e.item_id WHERE i.set_id = s.id)) AS set_val";
            $row = self::fetchOne($sql, [$d['id'], $d['id'], $d['id'], $d['id']]);

            if ($row['c'] > 0 || $row['eq_val'] > 0 || $row['item_val'] > 0 || $row['set_val'] > 0) {
                $stats[] = [
                    'name' => $d['name'],
                    'c' => (int) $row['c'],
                    'v' => (float) ($row['eq_val'] + $row['item_val'] + $row['set_val']),
                ];
            }
        }

        usort($stats, function ($a, $b) {
            return $b['v'] <=> $a['v'];
        });

        return $stats;
    }
}
