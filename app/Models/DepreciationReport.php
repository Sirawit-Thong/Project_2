<?php
/**
 * Depreciation Report Model
 * Query + aggregate ข้อมูลค่าเสื่อมราคา — คำนวณ real-time ผ่าน DepreciationCalculator
 * ไม่มีตารางเก็บผลลัพธ์ (deterministic จาก equipment + sets.year + settings)
 */
class DepreciationReport
{
    private const EXCLUDED_STATUSES = ['disposed', 'pending_disposal'];

    public static function getEquipmentRows(array $filters = [], ?int $teacherUserId = null): array
    {
        $where[] = "e.status NOT IN ('" . implode("', '", self::EXCLUDED_STATUSES) . "')";
        $params = [];

        if (!empty($filters['dept_id'])) {
            $where[] = "st.dept_id = ?";
            $params[] = (int) $filters['dept_id'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = "i.category_id = ?";
            $params[] = (int) $filters['category_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = "e.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['year'])) {
            $where[] = "st.year = ?";
            $params[] = $filters['year'];
        }
        if ($teacherUserId !== null) {
            $where[] = "(e.holder_id = ? OR e.room_id IN (
                SELECT rm.room_id FROM room_managers rm WHERE rm.user_id = ?
            ))";
            $params[] = $teacherUserId;
            $params[] = $teacherUserId;
        }

        $sql = "SELECT e.id, e.code, e.status, e.price, e.purchase_date,
                i.name AS item_name, i.brand, i.model, i.category_id,
                ac.name AS category_name,
                ds.useful_life_years, ds.dep_rate, ds.method,
                st.name AS set_name, st.year AS set_year,
                dp.name AS dept_name, rm.name AS room_name,
                u.firstname AS holder_firstname, u.lastname AS holder_lastname
            FROM equipment e
            JOIN items i ON e.item_id = i.id
            JOIN sets st ON i.set_id = st.id
            LEFT JOIN dept dp ON st.dept_id = dp.id
            LEFT JOIN rooms rm ON e.room_id = rm.id
            LEFT JOIN asset_categories ac ON i.category_id = ac.id
            LEFT JOIN depreciation_settings ds ON ds.category_id = i.category_id
            LEFT JOIN users u ON e.holder_id = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY st.year DESC, COALESCE(e.code, '') ASC";

        $rows = Model::fetchAll($sql, $params);

        foreach ($rows as &$row) {
            $calc = DepreciationCalculator::calculate(
                $row['price'] !== null ? (float) $row['price'] : null,
                DepreciationCalculator::toBuddhistYear($row['set_year']),
                $row['useful_life_years'] !== null ? (int) $row['useful_life_years'] : null,
                $row['dep_rate'] !== null ? (float) $row['dep_rate'] : null,
                $row['method']
            );
            $row['dep_ok']        = $calc['ok'];
            $row['dep_reason']    = $calc['reason'];
            $row['annual_dep']    = $calc['annual_dep'];
            $row['years_elapsed'] = $calc['years_elapsed'];
            $row['accumulated']   = $calc['accumulated'];
            $row['nbv']           = $calc['nbv'];
            $row['schedule']      = $calc['schedule'];
        }
        unset($row);

        return $rows;
    }

    public static function getDistinctYears(): array
    {
        $rows = Model::fetchAll(
            "SELECT DISTINCT year FROM sets WHERE year REGEXP '^[0-9]+$'
             ORDER BY CAST(year AS UNSIGNED) DESC"
        );
        return array_column($rows, 'year');
    }

    private static function emptySummary(string $keyField, string $keyValue): array
    {
        return [
            $keyField           => $keyValue,
            'count'             => 0,
            'total_cost'        => 0.0,
            'total_annual'      => 0.0,
            'total_accumulated' => 0.0,
            'total_nbv'         => 0.0,
        ];
    }

    private static function accumulate(array &$bucket, array $row): void
    {
        $bucket['count']++;
        $bucket['total_cost'] += (float) $row['price'];
        if ($row['dep_ok']) {
            $bucket['total_annual']      += (float) $row['annual_dep'];
            $bucket['total_accumulated'] += (float) $row['accumulated'];
            $bucket['total_nbv']         += (float) $row['nbv'];
        }
    }

    public static function summarizeByYear(array $rows): array
    {
        $buckets = [];
        foreach ($rows as $row) {
            $year = (string) ($row['set_year'] ?? '-');
            if (!isset($buckets[$year])) {
                $buckets[$year] = self::emptySummary('year', $year);
            }
            self::accumulate($buckets[$year], $row);
        }
        usort($buckets, fn($a, $b) => strcmp($a['year'], $b['year']));
        return $buckets;
    }

    public static function summarizeByCategory(array $rows): array
    {
        $buckets = [];
        foreach ($rows as $row) {
            $name = $row['category_name'] ?: 'ไม่ระบุหมวดหมู่';
            if (!isset($buckets[$name])) {
                $buckets[$name] = self::emptySummary('category_name', $name);
            }
            self::accumulate($buckets[$name], $row);
        }
        usort($buckets, fn($a, $b) => strcmp($a['category_name'], $b['category_name']));
        return $buckets;
    }

    public static function totals(array $rows): array
    {
        $t = [
            'count_total'       => count($rows),
            'count_ok'          => 0,
            'count_skip'        => 0,
            'total_cost'        => 0.0,
            'total_annual'      => 0.0,
            'total_accumulated' => 0.0,
            'total_nbv'         => 0.0,
        ];
        foreach ($rows as $row) {
            $t['total_cost'] += (float) $row['price'];
            if ($row['dep_ok']) {
                $t['count_ok']++;
                $t['total_annual']      += (float) $row['annual_dep'];
                $t['total_accumulated'] += (float) $row['accumulated'];
                $t['total_nbv']         += (float) $row['nbv'];
            } else {
                $t['count_skip']++;
            }
        }
        return $t;
    }
}
