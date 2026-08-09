<?php
/**
 * Base Model
 * คลาสแม่สำหรับ Model ทั้งหมด — ครอบ PDO
 *
 * รองรับ 2 รูปแบบการเรียก:
 *   Model::find('users', $id)        ← explicit table (backward-compatible)
 *   User::find($id)                  ← auto-resolve table จาก static::$table
 */

class Model
{
    protected static $pdo;
    protected static $table;

    /**
     * รับ PDO instance
     */
    protected static function db()
    {
        if (self::$pdo === null) {
            self::$pdo = getDB();
        }
        return self::$pdo;
    }

    /**
     * Resolve table name — ใช้ static::$table ถ้าไม่ได้ส่งมา
     */
    protected static function table($table = null)
    {
        return $table ?? static::$table;
    }

    /**
     * SELECT * FROM table WHERE id = ?
     *
     * Supports: find('users', $id)  OR  User::find($id)
     */
    public static function find($table, $id = null)
    {
        if ($id === null) {
            $id = $table;
            $table = static::$table;
        }
        $stmt = self::db()->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * SELECT * FROM table WHERE column = ?
     */
    public static function findBy($table, $column, $value)
    {
        $stmt = self::db()->prepare("SELECT * FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        return $stmt->fetch() ?: null;
    }

    /**
     * SELECT * FROM table WHERE column = ? (คืน array ทั้งหมด)
     */
    public static function findAllBy($table, $column, $value)
    {
        $stmt = self::db()->prepare("SELECT * FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    /**
     * SELECT * FROM table (with optional WHERE, ORDER BY, LIMIT)
     *
     * Supports: all('users', [...])  OR  User::all([...])
     */
    public static function all($table = null, $options = [])
    {
        if (is_array($table)) {
            $options = $table;
            $table = null;
        }
        $table = static::table($table);
        $where = $options['where'] ?? '';
        $params = $options['params'] ?? [];
        $orderBy = $options['order'] ?? 'id ASC';
        $limit = $options['limit'] ?? null;
        $offset = $options['offset'] ?? null;

        $sql = "SELECT * FROM {$table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $sql .= " ORDER BY {$orderBy}";
        if ($limit !== null) {
            $sql .= " LIMIT " . (int) $limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int) $offset;
            }
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * SELECT COUNT(*) FROM table (with optional WHERE)
     *
     * Supports: count('users', ...)  OR  User::count(...)
     */
    public static function count($table = null, $where = '', $params = [])
    {
        $table = static::table($table);
        $sql = "SELECT COUNT(*) FROM {$table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * INSERT INTO table (data = column => value)
     *
     * Supports: create('users', [...])  OR  User::create([...])
     */
    public static function create($table, $data = null)
    {
        if ($data === null) {
            $data = $table;
            $table = static::$table;
        }
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_values($data));
        return self::db()->lastInsertId();
    }

    /**
     * UPDATE table SET ... WHERE id = ?
     *
     * Supports: update('users', $id, [...])  OR  User::update($id, [...])
     */
    public static function update($table, $id, $data = null)
    {
        if ($data === null) {
            $data = $id;
            $id = $table;
            $table = static::$table;
        }
        $set = [];
        $values = [];
        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
            $values[] = $value;
        }
        $values[] = $id;

        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE id = ?";
        $stmt = self::db()->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * DELETE FROM table WHERE id = ?
     *
     * Supports: delete('users', $id)  OR  User::delete($id)
     */
    public static function delete($table, $id = null)
    {
        if ($id === null) {
            $id = $table;
            $table = static::$table;
        }
        $stmt = self::db()->prepare("DELETE FROM {$table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * DELETE FROM table WHERE column = ?
     */
    public static function deleteBy($table, $column, $value)
    {
        $stmt = self::db()->prepare("DELETE FROM {$table} WHERE {$column} = ?");
        return $stmt->execute([$value]);
    }

    /**
     * รัน raw query
     */
    public static function query($sql, $params = [])
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * SELECT แล้ว fetch แถวเดียว
     */
    public static function fetchOne($sql, $params = [])
    {
        return self::query($sql, $params)->fetch() ?: null;
    }

    /**
     * SELECT แล้ว fetch ทุกแถว
     */
    public static function fetchAll($sql, $params = [])
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * SELECT แล้ว fetchColumn (ค่าเดียว)
     */
    public static function fetchColumn($sql, $params = [])
    {
        return self::query($sql, $params)->fetchColumn();
    }

    /**
     * คำนวณ pagination
     */
    public static function paginate($totalItems, $currentPage, $perPage = 10)
    {
        $totalPages = (int) max(1, ceil($totalItems / $perPage));
        $currentPage = (int) max(1, min($currentPage, $totalPages));
        $offset = (int) ($currentPage - 1) * $perPage;

        return [
            'total_items' => (int) $totalItems,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'per_page' => (int) $perPage,
            'offset' => $offset
        ];
    }
}
