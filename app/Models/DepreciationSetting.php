<?php
/**
 * Depreciation Setting Model
 * เกณฑ์ค่าเสื่อมราคาต่อหมวดหมู่ครุภัณฑ์ (1 หมวด = 1 เกณฑ์)
 */
class DepreciationSetting extends Model
{
    protected static $table = 'depreciation_settings';

    public const METHODS = ['straight_line', 'declining_balance'];

    public static function getAllWithCategory(): array
    {
        $sql = "SELECT ds.*, ac.name AS category_name,
                    CONCAT(u.firstname, ' ', u.lastname) AS updated_by_name
                FROM depreciation_settings ds
                JOIN asset_categories ac ON ds.category_id = ac.id
                LEFT JOIN users u ON ds.updated_by = u.id
                ORDER BY ac.name ASC";
        return self::fetchAll($sql);
    }

    public static function findByCategoryId(int $categoryId): ?array
    {
        return self::findBy('category_id', $categoryId);
    }

    /**
     * INSERT ถ้ายังไม่มี / UPDATE ถ้ามีแล้ว (อาศัย UNIQUE category_id)
     */
    public static function upsert(int $categoryId, int $lifeYears, float $ratePercent, string $method, ?int $updatedBy): void
    {
        $sql = "INSERT INTO depreciation_settings
                    (category_id, useful_life_years, dep_rate, method, updated_by)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    useful_life_years = VALUES(useful_life_years),
                    dep_rate = VALUES(dep_rate),
                    method = VALUES(method),
                    updated_by = VALUES(updated_by)";
        self::query($sql, [$categoryId, $lifeYears, round($ratePercent, 2), $method, $updatedBy]);
    }
}
