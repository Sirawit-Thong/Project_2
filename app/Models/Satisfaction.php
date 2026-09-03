<?php
/**
 * Satisfaction Model
 * แบบประเมินความพึงพอใจหลังซ่อมเสร็จ — 1 ใบซ่อมประเมินได้ 1 ครั้ง (UNIQUE repair_id)
 */
class Satisfaction extends Model
{
    protected static $table = 'satisfaction_surveys';

    public static function getByRepairId(int $repairId): ?array
    {
        return self::findBy(static::$table, 'repair_id', $repairId);
    }

    /**
     * @return bool false ถ้าใบซ่อมนี้ถูกประเมินไปแล้ว (duplicate key 23000)
     */
    public static function createSurvey(int $repairId, int $userId, int $rating, string $comment = ''): bool
    {
        try {
            self::create([
                'repair_id' => $repairId,
                'user_id'   => $userId,
                'rating'    => $rating,
                'comment'   => trim($comment) !== '' ? trim($comment) : null,
            ]);
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    /**
     * คะแนนเฉลี่ยรายเดือน N เดือนย้อนหลัง (fill เดือนที่ไม่มีข้อมูลเป็น 0)
     */
    public static function getMonthlyStats(int $months = 12): array
    {
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym,
                    ROUND(AVG(rating), 2) AS avg_rating, COUNT(*) AS cnt
                FROM satisfaction_surveys
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY ym";
        $found = [];
        foreach (self::fetchAll($sql, [$months]) as $row) {
            $found[$row['ym']] = ['avg' => (float) $row['avg_rating'], 'cnt' => (int) $row['cnt']];
        }

        $stats = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = new DateTime(date('Y-m-01'));
            $date->modify("-{$i} months");
            $ym = $date->format('Y-m');
            $stats[] = [
                'month'      => $ym,
                'label'      => chartMonthYearThai($date),
                'avg_rating' => $found[$ym]['avg'] ?? 0.0,
                'count'      => $found[$ym]['cnt'] ?? 0,
            ];
        }
        return $stats;
    }

    public static function getOverall(): array
    {
        $avg = self::fetchColumn("SELECT ROUND(AVG(rating), 2) FROM satisfaction_surveys");
        return ['avg_rating' => $avg !== null ? (float) $avg : null, 'total' => self::count()];
    }

    /**
     * อัตราการตอบแบบประเมิน (%) = จำนวนแบบประเมิน / ใบซ่อมสถานะ completed x 100
     */
    public static function responseRate(): float
    {
        $completed = self::completedRepairCount();
        if ($completed === 0) {
            return 0.0;
        }
        return round(self::count() / $completed * 100, 1);
    }

    public static function completedRepairCount(): int
    {
        return (int) self::fetchColumn("SELECT COUNT(*) FROM repair WHERE status = 'completed'");
    }

    public static function getRecent(int $limit = 20): array
    {
        $sql = "SELECT ss.*, u.firstname, u.lastname, u.role,
                e.code AS eq_code, i.name AS item_name
                FROM satisfaction_surveys ss
                JOIN repair r ON ss.repair_id = r.id
                LEFT JOIN equipment e ON r.equipment_id = e.id
                LEFT JOIN items i ON e.item_id = i.id
                LEFT JOIN users u ON ss.user_id = u.id
                ORDER BY ss.created_at DESC
                LIMIT " . (int) $limit;
        return self::fetchAll($sql);
    }

    public static function getAllForExport(): array
    {
        $sql = "SELECT ss.*, u.firstname, u.lastname, u.role,
                e.code AS eq_code, i.name AS item_name
                FROM satisfaction_surveys ss
                JOIN repair r ON ss.repair_id = r.id
                LEFT JOIN equipment e ON r.equipment_id = e.id
                LEFT JOIN items i ON e.item_id = i.id
                LEFT JOIN users u ON ss.user_id = u.id
                ORDER BY ss.created_at DESC";
        return self::fetchAll($sql);
    }
}
