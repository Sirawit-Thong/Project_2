<?php
class EquipmentStats extends Model {
    protected static $table = 'equipment';

    public static function countByStatus($status) {
        $sql = "SELECT COUNT(*) FROM equipment WHERE status = ?";
        return (int) self::fetchColumn($sql, [$status]);
    }

    public static function totalCount() {
        $sql = "SELECT COUNT(*) FROM equipment";
        return (int) self::fetchColumn($sql);
    }

    public static function getTotalValue() {
        // มูลค่าทรัพย์สินรวม = ราคารวมของชุดครุภัณฑ์/รายการครุภัณฑ์
        // เฉพาะกลุ่มที่มีครุภัณฑ์หลายชิ้น (>= 2) นับราคารวมแค่ชุด/รายการละ 1 ครั้ง
        $sql = "SELECT COALESCE((
            (SELECT COALESCE(SUM(s.price), 0) FROM sets s
             WHERE s.price > 0
               AND (SELECT COUNT(*) FROM items i
                    JOIN equipment e ON e.item_id = i.id
                    WHERE i.set_id = s.id AND e.status != 'disposed') >= 2)
            +
            (SELECT COALESCE(SUM(i.price), 0) FROM items i
             WHERE i.price > 0
               AND (SELECT COUNT(*) FROM equipment e
                    WHERE e.item_id = i.id AND e.status != 'disposed') >= 2
               AND NOT EXISTS (SELECT 1 FROM sets s WHERE s.id = i.set_id AND s.price > 0))
        ), 0) AS total_value";
        return (float) self::fetchColumn($sql);
    }

    public static function getAssetValue() {
        // มูลค่าทรัพย์สินรวม (ตามสูตรออริจินอล) = ราคารวมครุภัณฑ์ + ราคารายการครุภัณฑ์ + ราคาชุดครุภัณฑ์
        $sql = "SELECT
            (SELECT COALESCE(SUM(price), 0) FROM equipment WHERE status != 'disposed') AS eq_val,
            (SELECT COALESCE(SUM(i.price * (SELECT COUNT(*) FROM equipment e WHERE e.item_id = i.id AND e.status != 'disposed')), 0) FROM items i) AS item_val,
            (SELECT COALESCE(SUM(s.price), 0) FROM sets s WHERE s.price > 0 AND EXISTS (SELECT 1 FROM items i JOIN equipment e ON i.id = e.item_id WHERE i.set_id = s.id AND e.status != 'disposed')) AS set_val";
        $row = self::fetchOne($sql);
        return (float) (($row['eq_val'] ?? 0) + ($row['item_val'] ?? 0) + ($row['set_val'] ?? 0));
    }

    public static function countByDepartment() {
        $sql = "SELECT d.name, COUNT(e.id) AS count
            FROM equipment e
            JOIN items i ON e.item_id = i.id
            JOIN sets s ON i.set_id = s.id
            JOIN dept d ON s.dept_id = d.id
            GROUP BY d.id, d.name
            ORDER BY d.name";
        return self::fetchAll($sql);
    }

    public static function countByHolder($userId) {
        $sql = "SELECT COUNT(*) FROM equipment WHERE holder_id = ?";
        return (int) self::fetchColumn($sql, [$userId]);
    }

    public static function getStatusCounts() {
        $sql = "SELECT status, COUNT(*) AS count FROM equipment GROUP BY status";
        return self::fetchAll($sql);
    }

    public static function getRoomStatsForTeacher($userId) {
        $sql = "SELECT
            rm.room_id,
            r.name AS room_name,
            COUNT(DISTINCT e.id) AS total_equip,
            COUNT(DISTINCT CASE WHEN e.status = 'broken' THEN e.id END) AS broken_count,
            COUNT(DISTINCT CASE WHEN e.status = 'available' THEN e.id END) AS available_count,
            COUNT(DISTINCT CASE WHEN e.check_date = CURDATE() THEN e.id END) AS checked_today,
            COUNT(DISTINCT CASE WHEN e.check_date IS NULL OR e.check_date < CURDATE() THEN e.id END) AS need_check
            FROM room_managers rm
            JOIN rooms r ON r.id = rm.room_id
            LEFT JOIN equipment e ON e.room_id = rm.room_id
            WHERE rm.user_id = ?
            GROUP BY rm.room_id, r.name
            ORDER BY r.name";
        return self::fetchAll($sql, [$userId]);
    }

    public static function getReportStatsForTeacher($userId) {
        $sql = "
            SELECT
                r.id AS room_id,
                r.name AS room_name,
                COUNT(e.id) AS total_equipment,
                SUM(CASE WHEN e.status = 'available' THEN 1 ELSE 0 END) AS available_count,
                SUM(CASE WHEN e.status = 'repair' THEN 1 ELSE 0 END) AS repair_count,
                SUM(CASE WHEN e.status = 'broken' THEN 1 ELSE 0 END) AS broken_count,
                SUM(CASE WHEN e.status IN ('pending_disposal', 'disposed') THEN 1 ELSE 0 END) AS disposed_count,
                SUM(COALESCE(e.price, 0)) AS total_value,
                SUM(CASE WHEN e.check_date IS NULL OR e.check_date < DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 1 ELSE 0 END) AS need_check_count,
                1 AS sort_order
            FROM room_managers rm
            JOIN rooms r ON rm.room_id = r.id
            LEFT JOIN equipment e ON r.id = e.room_id
            WHERE rm.user_id = ?
            GROUP BY r.id, r.name

            UNION ALL

            SELECT
                'other' AS room_id,
                'อื่นๆ (ไม่ได้อยู่ในห้องที่รับผิดชอบ)' AS room_name,
                COUNT(e.id) AS total_equipment,
                SUM(CASE WHEN e.status = 'available' THEN 1 ELSE 0 END) AS available_count,
                SUM(CASE WHEN e.status = 'repair' THEN 1 ELSE 0 END) AS repair_count,
                SUM(CASE WHEN e.status = 'broken' THEN 1 ELSE 0 END) AS broken_count,
                SUM(CASE WHEN e.status IN ('pending_disposal', 'disposed') THEN 1 ELSE 0 END) AS disposed_count,
                SUM(COALESCE(e.price, 0)) AS total_value,
                SUM(CASE WHEN e.check_date IS NULL OR e.check_date < DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 1 ELSE 0 END) AS need_check_count,
                2 AS sort_order
            FROM equipment e
            WHERE e.holder_id = ?
              AND (e.room_id IS NULL OR e.room_id NOT IN (SELECT room_id FROM room_managers WHERE user_id = ?))
            HAVING COUNT(e.id) > 0

            ORDER BY sort_order, room_name
        ";
        return self::fetchAll($sql, [$userId, $userId, $userId]);
    }
}
