<?php
class Item extends Model
{
    protected static $table = 'items';

    public static function getFiltered($setFilter, $deptFilter, $page, $perPage = 20)
    {
        $conditions = [];
        $params = [];

        if ($setFilter) {
            $conditions[] = 'i.set_id = ?';
            $params[] = $setFilter;
        }
        if ($deptFilter) {
            $conditions[] = 's.dept_id = ?';
            $params[] = $deptFilter;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countSql = "
            SELECT COUNT(DISTINCT i.id)
            FROM items i
            LEFT JOIN sets s ON s.id = i.set_id
            {$where}
        ";
        $totalItems = (int) self::fetchColumn($countSql, $params);
        $pagination = self::paginate($totalItems, $page, $perPage);

        $sql = "
            SELECT
                i.*,
                s.name AS set_name,
                s.year AS set_year,
                s.dept_id AS dept_id,
                d.name AS dept_name,
                COUNT(DISTINCT e.id) AS equipment_count
            FROM items i
            LEFT JOIN sets s ON s.id = i.set_id
            LEFT JOIN dept d ON d.id = s.dept_id
            LEFT JOIN equipment e ON e.item_id = i.id
            {$where}
            GROUP BY i.id, i.set_id, i.name, i.brand, i.model, i.qty, i.unit, i.price, i.price_remark, i.remark, i.created_at, i.updated_at, s.name, s.year, s.dept_id, d.name
            ORDER BY d.name ASC, s.name ASC, i.name ASC
            LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
        ";
        $rows = self::fetchAll($sql, $params);

        return [
            'rows' => $rows,
            'pagination' => $pagination,
        ];
    }

    public static function getAllForDropdown()
    {
        $sql = "
            SELECT
                i.*,
                s.id AS set_id,
                s.name AS set_name,
                s.year,
                s.dept_id,
                s.price AS set_price,
                d.name AS dept_name,
                COUNT(e.id) AS existing_count
            FROM items i
            LEFT JOIN sets s ON s.id = i.set_id
            LEFT JOIN dept d ON d.id = s.dept_id
            LEFT JOIN equipment e ON e.item_id = i.id
            GROUP BY i.id
            ORDER BY d.name ASC, s.name ASC, i.name ASC
        ";
        return self::fetchAll($sql);
    }

    public static function childEquipmentCount($id)
    {
        $sql = "SELECT COUNT(*) FROM equipment WHERE item_id = ?";
        return (int) self::fetchColumn($sql, [$id]);
    }

    public static function parentSetHasPrice($setId)
    {
        $sql = "SELECT price FROM sets WHERE id = ?";
        $price = self::fetchColumn($sql, [$setId]);
        return $price !== false && (float) $price > 0;
    }
}
