<?php
class User extends Model
{
    protected static $table = 'users';

    public static function findByEmail($email)
    {
        return self::findBy(static::$table, 'email', $email);
    }

    public static function findBySid($sid)
    {
        return self::findBy(static::$table, 'sid', $sid);
    }

    public static function isEmailTaken($email, $excludeId = 0)
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?";
        return (int) self::fetchColumn($sql, [$email, $excludeId]) > 0;
    }

    public static function isSidTaken($sid, $excludeId = 0)
    {
        $sql = "SELECT COUNT(*) FROM users WHERE sid = ? AND id != ?";
        return (int) self::fetchColumn($sql, [$sid, $excludeId]) > 0;
    }

    public static function getFiltered($search, $role, $status, $page, $perPage = 15)
    {
        $where = [];
        $params = [];

        if ($search !== '' && $search !== null) {
            $where[] = "(firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR sid LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($role !== '' && $role !== null) {
            $where[] = "role = ?";
            $params[] = $role;
        }

        if ($status !== '' && $status !== null) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) FROM users {$whereSql}";
        $total = (int) self::fetchColumn($countSql, $params);

        $pagination = self::paginate($total, $page, $perPage);

        $dataSql = "SELECT * FROM users {$whereSql} ORDER BY id ASC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
        $users = self::fetchAll($dataSql, $params);

        return [
            'users' => $users,
            'pagination' => $pagination,
            'total' => $total,
        ];
    }

    public static function getHolders()
    {
        $sql = "SELECT * FROM users WHERE role IN ('admin', 'staff', 'teacher') AND status = 'approved' ORDER BY firstname ASC";
        return self::fetchAll($sql);
    }

    public static function createWithPassword($data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return self::create($data);
    }

    public static function updateWithPassword($id, $data, $password = null)
    {
        if ($password !== null && $password !== '') {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        return self::update($id, $data);
    }

    public static function pendingCount()
    {
        return self::count(static::$table, "status = 'pending'");
    }

    public static function totalCount()
    {
        return self::count(static::$table);
    }

    public static function getPending()
    {
        $sql = "SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC";
        return self::fetchAll($sql);
    }

    public static function approve($id)
    {
        return self::update($id, ['status' => 'approved']);
    }

    public static function rejectPending($id)
    {
        return self::update($id, ['status' => 'rejected']);
    }

    public static function getAllForExport()
    {
        $sql = "SELECT sid, firstname, lastname, email, role, status, created_at
            FROM users ORDER BY created_at DESC";
        return self::fetchAll($sql);
    }
}
