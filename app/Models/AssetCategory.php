<?php
/**
 * Asset Category Model
 * หมวดหมู่ครุภัณฑ์ — ใช้อ้างอิงเกณฑ์ค่าเสื่อมราคา
 */
class AssetCategory extends Model
{
    protected static $table = 'asset_categories';

    public static function getAll(): array
    {
        return self::fetchAll("SELECT * FROM asset_categories ORDER BY name ASC");
    }

    public static function createCategory(string $name, string $remark = ''): int
    {
        return (int) self::create([
            'name'   => trim($name),
            'remark' => trim($remark) !== '' ? trim($remark) : null,
        ]);
    }

    public static function updateCategory(int $id, string $name, string $remark = ''): bool
    {
        return self::update($id, [
            'name'   => trim($name),
            'remark' => trim($remark) !== '' ? trim($remark) : null,
        ]);
    }

    /**
     * items.category_id เป็น ON DELETE SET NULL / settings เป็น CASCADE — ลบได้ปลอดภัย
     */
    public static function deleteCategory(int $id): bool
    {
        return self::delete($id);
    }

    public static function countItems(int $categoryId): int
    {
        return (int) self::count('items', 'category_id = ?', [$categoryId]);
    }
}
