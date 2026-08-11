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
        // มูลค่ารวมต่อสาขา = ราคารวมของชุด/รายการครุภัณฑ์ เฉพาะกลุ่มที่มีครุภัณฑ์หลายชิ้น (>= 2)
        // นับราคารวมแค่ชุด/รายการละ 1 ครั้ง
        $sql = "SELECT d.id, d.name,
            COUNT(DISTINCT e.id) AS equipment_count,
            (
                (SELECT COALESCE(SUM(s.price), 0) FROM sets s
                 WHERE s.dept_id = d.id AND s.price > 0
                   AND (SELECT COUNT(*) FROM items i JOIN equipment e ON e.item_id = i.id
                        WHERE i.set_id = s.id AND e.status != 'disposed') >= 2)
                +
                (SELECT COALESCE(SUM(i.price), 0) FROM items i JOIN sets s ON s.id = i.set_id
                 WHERE s.dept_id = d.id AND i.price > 0
                   AND (SELECT COUNT(*) FROM equipment e WHERE e.item_id = i.id AND e.status != 'disposed') >= 2
                   AND NOT EXISTS (SELECT 1 FROM sets s2 WHERE s2.id = i.set_id AND s2.price > 0))
            ) AS total_value
            FROM dept d
            LEFT JOIN sets s ON s.dept_id = d.id
            LEFT JOIN items i ON i.set_id = s.id
            LEFT JOIN equipment e ON e.item_id = i.id AND e.status != 'disposed'
            GROUP BY d.id, d.name
            ORDER BY d.name";
        return self::fetchAll($sql);
    }
}
