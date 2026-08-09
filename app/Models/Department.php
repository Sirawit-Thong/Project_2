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
        $sql = "SELECT d.id, d.name,
            COUNT(DISTINCT e.id) AS equipment_count,
            SUM(COALESCE(
                CASE
                    WHEN e.price > 0 THEN e.price
                    WHEN i.price > 0 THEN i.price
                    WHEN s.price > 0 THEN s.price
                    ELSE 0
                END, 0
            )) AS total_value
            FROM dept d
            LEFT JOIN sets s ON s.dept_id = d.id
            LEFT JOIN items i ON i.set_id = s.id
            LEFT JOIN equipment e ON e.item_id = i.id AND e.status != 'disposed'
            GROUP BY d.id, d.name
            ORDER BY d.name";
        return self::fetchAll($sql);
    }
}
