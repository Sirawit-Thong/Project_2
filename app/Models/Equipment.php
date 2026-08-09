<?php
class Equipment extends Model {
    protected static $table = 'equipment';

    public static function getFiltered($filters, $page, $perPage = 20) {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(e.code LIKE ? OR i.name LIKE ? OR i.brand LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['status'])) {
            $where[] = 'e.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['room'])) {
            $where[] = 'e.room_id = ?';
            $params[] = $filters['room'];
        }

        if (!empty($filters['item'])) {
            $where[] = 'e.item_id = ?';
            $params[] = $filters['item'];
        }

        if (!empty($filters['dept'])) {
            $where[] = 's.dept_id = ?';
            $params[] = $filters['dept'];
        }

        if (!empty($filters['set'])) {
            $where[] = 'i.set_id = ?';
            $params[] = $filters['set'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) FROM equipment e
            JOIN items i ON e.item_id = i.id
            JOIN sets s ON i.set_id = s.id
            LEFT JOIN dept d ON s.dept_id = d.id
            LEFT JOIN users u ON e.holder_id = u.id
            LEFT JOIN rooms rm ON e.room_id = rm.id
            {$whereClause}";
        $total = (int) self::fetchColumn($countSql, $params);

        $pagination = self::paginate($total, $page, $perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT e.*, i.name AS item_name, i.brand, i.model, i.set_id,
            s.name AS set_name, s.year, d.name AS dept_name,
            u.firstname AS holder_firstname, u.lastname AS holder_lastname,
            rm.name AS room_name
            FROM equipment e
            JOIN items i ON e.item_id = i.id
            JOIN sets s ON i.set_id = s.id
            LEFT JOIN dept d ON s.dept_id = d.id
            LEFT JOIN users u ON e.holder_id = u.id
            LEFT JOIN rooms rm ON e.room_id = rm.id
            {$whereClause}
            ORDER BY e.code
            LIMIT {$perPage} OFFSET {$offset}";
        $equipment = self::fetchAll($sql, $params);

        return [
            'equipment' => $equipment,
            'pagination' => $pagination,
            'total' => $total,
        ];
    }

    public static function getDetail($id, $userId = null) {
        $sql = "SELECT e.*, e.price_remark AS eq_price_remark,
            i.name AS item_name, i.brand, i.model, i.set_id, i.price_remark AS item_price_remark,
            s.name AS set_name, s.year, s.price_remark AS set_price_remark,
            d.name AS dept_name,
            u.firstname AS holder_firstname, u.lastname AS holder_lastname,
            rm.name AS room_name
            FROM equipment e
            JOIN items i ON e.item_id = i.id
            JOIN sets s ON i.set_id = s.id
            LEFT JOIN dept d ON s.dept_id = d.id
            LEFT JOIN users u ON e.holder_id = u.id
            LEFT JOIN rooms rm ON e.room_id = rm.id
            WHERE e.id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND (e.holder_id = ? OR e.room_id IN (
                SELECT rm2.room_id FROM room_managers rm2 WHERE rm2.user_id = ?
            ))";
            $params[] = $userId;
            $params[] = $userId;
        }

        return self::fetchOne($sql, $params);
    }

    public static function isCodeTaken($code, $excludeId = 0) {
        $sql = "SELECT COUNT(*) FROM equipment WHERE code = ? AND id != ?";
        return (int) self::fetchColumn($sql, [$code, $excludeId]) > 0;
    }

    public static function countByItem($itemId) {
        $sql = "SELECT COUNT(*) FROM equipment WHERE item_id = ?";
        return (int) self::fetchColumn($sql, [$itemId]);
    }

    public static function getParentPrices($itemId) {
        $sql = "SELECT i.price AS item_price, s.price AS set_price
            FROM items i
            JOIN sets s ON i.set_id = s.id
            WHERE i.id = ?";
        $row = self::fetchOne($sql, [$itemId]);
        return [
            'item_price' => $row ? $row['item_price'] : null,
            'set_price' => $row ? $row['set_price'] : null,
        ];
    }

    public static function checkQtyLimit($itemId) {
        $sql = "SELECT i.name, i.qty, (SELECT COUNT(*) FROM equipment WHERE item_id = i.id) AS existing
            FROM items i WHERE i.id = ?";
        $row = self::fetchOne($sql, [$itemId]);
        if (!$row) {
            return ['exceeded' => false, 'existing' => 0, 'qty' => 0, 'name' => ''];
        }
        return [
            'exceeded' => (int) $row['existing'] >= (int) $row['qty'],
            'existing' => (int) $row['existing'],
            'qty' => (int) $row['qty'],
            'name' => $row['name'],
        ];
    }

    public static function getImages($equipmentId, $type = null) {
        return EquipmentImage::getByEquipment($equipmentId, $type);
    }

    public static function addImage($equipmentId, $path, $type) {
        return EquipmentImage::add($equipmentId, $path, $type);
    }

    public static function deleteImage($imageId, $equipmentId) {
        return EquipmentImage::remove($imageId, $equipmentId);
    }

    public static function getImage($imageId, $equipmentId) {
        return EquipmentImage::getOne($imageId, $equipmentId);
    }

    public static function getByRoom($roomId) {
        $sql = "SELECT id FROM equipment WHERE room_id = ?";
        $rows = self::fetchAll($sql, [$roomId]);
        return array_column($rows, 'id');
    }

    public static function bulkUpdateHolder($where = '', $params = [], $holderId = null) {
        $sql = "UPDATE equipment SET holder_id = ?";
        $bindParams = [$holderId];
        if ($where) {
            $sql .= " WHERE {$where}";
            $bindParams = array_merge($bindParams, $params);
        }
        return self::query($sql, $bindParams);
    }

    public static function countByStatus($status) {
        return EquipmentStats::countByStatus($status);
    }

    public static function totalCount() {
        return EquipmentStats::totalCount();
    }

    public static function getTotalValue() {
        return EquipmentStats::getTotalValue();
    }

    public static function countByDepartment() {
        return EquipmentStats::countByDepartment();
    }

    public static function getDisposalList() {
        $sql = "SELECT e.*, i.name AS item_name, i.brand, rm.name AS room_name
            FROM equipment e
            JOIN items i ON e.item_id = i.id
            LEFT JOIN rooms rm ON e.room_id = rm.id
            WHERE e.status = 'pending_disposal'
            ORDER BY e.code";
        return self::fetchAll($sql);
    }

    public static function dispose($id) {
        return self::update($id, ['status' => 'disposed']);
    }

    public static function updateStatus($id, $status) {
        return self::update($id, ['status' => $status]);
    }

    public static function getByRoomWithItems($roomId) {
        $sql = "SELECT e.*, i.name AS item_name, i.model, i.brand, i.unit
            FROM equipment e
            JOIN items i ON e.item_id = i.id
            WHERE e.room_id = ?
            ORDER BY e.code";
        return self::fetchAll($sql, [$roomId]);
    }

    public static function getByStatus($status, $page = 1, $perPage = 20, $orderBy = 'code') {
        $countSql = "SELECT COUNT(*) FROM equipment WHERE status = ?";
        $total = (int) self::fetchColumn($countSql, [$status]);
        $pagination = self::paginate($total, $page, $perPage);
        $offset = ($page - 1) * $perPage;

        // Whitelist allowed ORDER BY columns to prevent SQL injection
        $allowedColumns = ['code', 'name', 'price', 'check_date', 'created_at', 'updated_at'];
        if (!in_array($orderBy, $allowedColumns, true)) {
            $orderBy = 'code';
        }

        $sql = "SELECT e.*, i.name AS item_name, i.brand, rm.name AS room_name
            FROM equipment e
            JOIN items i ON e.item_id = i.id
            LEFT JOIN rooms rm ON e.room_id = rm.id
            WHERE e.status = ?
            ORDER BY e.{$orderBy}
            LIMIT {$perPage} OFFSET {$offset}";
        $equipment = self::fetchAll($sql, [$status]);

        return [
            'equipment' => $equipment,
            'pagination' => $pagination,
            'total' => $total,
        ];
    }

    public static function hasNonManagedEquipment($userId) {
        $sql = "SELECT 1 FROM equipment e
            WHERE e.holder_id = ?
            AND (e.room_id IS NULL OR e.room_id NOT IN (
                SELECT rm.room_id FROM room_managers rm WHERE rm.user_id = ?
            ))
            LIMIT 1";
        return self::fetchOne($sql, [$userId, $userId]) !== null;
    }

    public static function getNonManagedByHolder($userId) {
        $sql = "SELECT e.*, i.name AS item_name, i.brand, i.model
            FROM equipment e
            JOIN items i ON e.item_id = i.id
            WHERE e.holder_id = ?
            AND (e.room_id IS NULL OR e.room_id NOT IN (
                SELECT rm.room_id FROM room_managers rm WHERE rm.user_id = ?
            ))
            ORDER BY e.code";
        return self::fetchAll($sql, [$userId, $userId]);
    }

    public static function check($id, $remark) {
        return self::update($id, [
            'check_date' => date('Y-m-d'),
            'remark' => $remark,
        ]);
    }

    public static function getStatusCounts() {
        return EquipmentStats::getStatusCounts();
    }

    public static function getRoomStatsForTeacher($userId) {
        return EquipmentStats::getRoomStatsForTeacher($userId);
    }

    public static function getAllForExport() {
        $sql = "SELECT e.code, i.name AS item_name, i.brand, i.model,
            s.name AS set_name, s.year, d.name AS dept_name,
            rm.name AS room_name,
            u.firstname AS holder_firstname, u.lastname AS holder_lastname,
            e.status, e.purchase_date, e.price, e.price_remark AS eq_price_remark,
            i.price_remark AS item_price_remark, s.price_remark AS set_price_remark,
            e.check_date, e.remark
            FROM equipment e
            JOIN items i ON e.item_id = i.id
            JOIN sets s ON i.set_id = s.id
            LEFT JOIN dept d ON s.dept_id = d.id
            LEFT JOIN rooms rm ON e.room_id = rm.id
            LEFT JOIN users u ON e.holder_id = u.id
            ORDER BY e.code";
        return self::fetchAll($sql);
    }

    public static function getAvailableWithRoom() {
        $sql = "SELECT e.id, e.code, rm.name AS room, i.name, i.brand
            FROM equipment e
            LEFT JOIN rooms rm ON e.room_id = rm.id
            JOIN items i ON e.item_id = i.id
            WHERE e.status IN ('available', 'broken')
            ORDER BY e.code";
        return self::fetchAll($sql);
    }

    public static function getReportStatsForTeacher($userId) {
        return EquipmentStats::getReportStatsForTeacher($userId);
    }

    public static function inspectionUpdate($id, $status, $remark, $updateCheckDate = false) {
        if ($updateCheckDate) {
            return self::update($id, [
                'status' => $status,
                'remark' => $remark,
                'check_date' => date('Y-m-d'),
            ]);
        }
        return self::update($id, [
            'status' => $status,
            'remark' => $remark,
        ]);
    }

    public static function getRepairHistory($equipmentId, $limit = null) {
        $sql = "SELECT r.*, u.firstname, u.lastname
            FROM repair r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.equipment_id = ?
            ORDER BY r.created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int) $limit;
        }
        return self::fetchAll($sql, [$equipmentId]);
    }

    public static function getByRoomForInspection($roomId) {
        $sql = "SELECT e.*, i.name AS item_name, i.model, i.brand, i.unit
            FROM equipment e
            JOIN items i ON e.item_id = i.id
            WHERE e.room_id = ?
            ORDER BY i.name, e.code";
        return self::fetchAll($sql, [$roomId]);
    }
}
