<?php
class EquipmentImage extends Model {
    protected static $table = 'equipment_img';

    public static function getByEquipment($equipmentId, $type = null) {
        $sql = "SELECT * FROM equipment_img WHERE equipment_id = ?";
        $params = [$equipmentId];
        if ($type !== null) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        $sql .= " ORDER BY created_at ASC";
        return self::fetchAll($sql, $params);
    }

    public static function getOne($imageId, $equipmentId) {
        $sql = "SELECT * FROM equipment_img WHERE id = ? AND equipment_id = ?";
        return self::fetchOne($sql, [$imageId, $equipmentId]);
    }

    public static function add($equipmentId, $path, $type) {
        return self::create(static::$table, [
            'equipment_id' => $equipmentId,
            'path' => $path,
            'type' => $type,
        ]);
    }

    public static function remove($imageId, $equipmentId) {
        $image = self::getOne($imageId, $equipmentId);
        if (!$image) {
            return null;
        }
        self::delete(static::$table, $imageId);
        return $image['path'];
    }
}
