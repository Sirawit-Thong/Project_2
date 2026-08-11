<?php
/**
 * Export Reports
 */
require_once '../../includes/auth.php';
requireRole(['admin', 'staff']);

$pdo = getDB();
$type = $_GET['type'] ?? 'equipment';
$format = $_GET['format'] ?? 'csv';

// Get data based on type
switch ($type) {
    case 'equipment':
        // Execute query
        $stmt = $pdo->query("SELECT e.code, i.name, i.brand, i.model, e.status, rm.name as room_name, e.price, e.purchase_date, e.check_date, s.name as set_name, d.name as dept_name, e.price_remark as eq_price_remark, i.price_remark as item_price_remark, s.price_remark as set_price_remark
            FROM equipment e 
            JOIN items i ON e.item_id = i.id 
            JOIN sets s ON i.set_id = s.id 
            LEFT JOIN dept d ON s.dept_id = d.id 
            LEFT JOIN rooms rm ON e.room_id = rm.id 
            ORDER BY e.code");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($results as $row) {
            // Apply cascading logic for price remark
            $priceRemark = '';
            if (!empty($row['eq_price_remark'])) {
                $priceRemark = $row['eq_price_remark'] . ' (เฉพาะชิ้น)';
            } elseif (!empty($row['item_price_remark'])) {
                $priceRemark = $row['item_price_remark'] . ' (ทั้งรายการ)';
            } elseif (!empty($row['set_price_remark'])) {
                $priceRemark = $row['set_price_remark'] . ' (ทั้งชุด)';
            }

            $data[] = [
                'code' => $row['code'],
                'name' => $row['name'],
                'brand' => $row['brand'],
                'model' => $row['model'],
                'status' => translateEquipmentStatus($row['status']),
                'room_name' => $row['room_name'],
                'price' => $row['price'],
                'purchase_date' => $row['purchase_date'],
                'check_date' => $row['check_date'],
                'dept_name' => $row['dept_name'],
                'set_name' => $row['set_name'],
                'item_name' => $row['name'],
                'price_remark' => $priceRemark
            ];
        }

        $headers = ['รหัส', 'ชื่อ', 'ยี่ห้อ', 'รุ่น', 'สถานะ', 'ห้อง', 'ราคา', 'วันที่ซื้อ', 'ตรวจสอบล่าสุด', 'สาขาวิชา', 'ชุดครุภัณฑ์', 'รายการครุภัณฑ์', 'หมายเหตุงบ/ราคา'];
        $filename = 'equipment_' . date('Ymd');
        break;
    case 'repairs':
        $data = $pdo->query("SELECT r.id, e.code, i.name, r.issue, r.status, r.created_at, u.firstname, u.lastname 
            FROM repair r JOIN equipment e ON r.equipment_id = e.id JOIN items i ON e.item_id = i.id 
            LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

        // Translate status
        foreach ($data as &$row) {
            $row['status'] = translateRepairStatus($row['status']);
        }

        $headers = ['ID', 'รหัสครุภัณฑ์', 'ชื่อ', 'อาการ', 'สถานะ', 'วันที่แจ้ง', 'ชื่อผู้แจ้ง', 'นามสกุล'];
        $filename = 'repairs_' . date('Ymd');
        break;
    case 'users':
        $data = $pdo->query("SELECT sid, firstname, lastname, email, role, status, created_at FROM users ORDER BY created_at DESC")->fetchAll();
        $headers = ['รหัส', 'ชื่อ', 'นามสกุล', 'อีเมล', 'บทบาท', 'สถานะ', 'วันที่สมัคร'];
        $filename = 'users_' . date('Ymd');
        break;
    default:
        die('Invalid type');
}

// Export as CSV
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    foreach ($data as $row) {
        fputcsv($output, array_values($row));
    }
    fclose($output);
    exit;
}

// Export as Excel (HTML table that Excel can open)
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1"><tr>';
    foreach ($headers as $h) {
        echo "<th>$h</th>";
    }
    echo '</tr>';
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell ?? '') . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}
?>