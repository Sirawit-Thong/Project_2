<?php
class SetModel extends Model
{
    protected static $table = 'sets';

    public static function getFiltered($deptId, $page, $perPage = 20)
    {
        $where = '';
        $params = [];

        if ($deptId) {
            $where = 'WHERE s.dept_id = ?';
            $params[] = $deptId;
        }

        $countSql = "
            SELECT COUNT(DISTINCT s.id)
            FROM sets s
            {$where}
        ";
        $totalItems = (int) self::fetchColumn($countSql, $params);
        $pagination = self::paginate($totalItems, $page, $perPage);

        $sql = "
            SELECT
                s.*,
                d.name AS dept_name,
                COUNT(DISTINCT i.id) AS item_count,
                COUNT(DISTINCT e.id) AS equipment_count
            FROM sets s
            LEFT JOIN dept d ON d.id = s.dept_id
            LEFT JOIN items i ON i.set_id = s.id
            LEFT JOIN equipment e ON e.item_id = i.id
            {$where}
            GROUP BY s.id, s.dept_id, s.name, s.year, s.price, s.price_remark, s.remark, s.created_at, s.updated_at, d.name
            ORDER BY s.year DESC, s.name ASC
            LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
        ";
        $rows = self::fetchAll($sql, $params);

        return [
            'rows' => $rows,
            'pagination' => $pagination,
        ];
    }

    public static function getAllWithDept()
    {
        $sql = "
            SELECT s.*, d.name AS dept_name
            FROM sets s
            LEFT JOIN dept d ON d.id = s.dept_id
            ORDER BY d.name ASC, s.name ASC
        ";
        return self::fetchAll($sql);
    }

    public static function childItemCount($id)
    {
        $sql = "SELECT COUNT(*) FROM items WHERE set_id = ?";
        return (int) self::fetchColumn($sql, [$id]);
    }

    public static function getPrice($id)
    {
        $sql = "SELECT price FROM sets WHERE id = ?";
        $result = self::fetchColumn($sql, [$id]);
        return $result !== false ? (float) $result : 0.0;
    }
}
