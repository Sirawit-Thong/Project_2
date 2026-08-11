<?php
/**
 * Teacher Export Excel
 * ส่งออกรายการครุภัณฑ์เป็น Excel
 */
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !hasRole('teacher')) {
    die('Unauthorized');
}

$pdo = getDB();
$userId = getCurrentUserId();

// Get room filter
$room = $_GET['room'] ?? '';

// Build query for equipment
if ($room === 'other') {
    $sql = "
        SELECT e.code, i.name as item_name, i.brand, i.model, 
               'อื่นๆ (ไม่ได้อยู่ในห้องที่รับผิดชอบ)' as room_name, e.status, e.price, e.purchase_date, e.check_date,
               u.firstname as holder_firstname, u.lastname as holder_lastname,
               e.remark, e.price_remark as eq_price_remark, i.price_remark as item_price_remark, s.price_remark as set_price_remark
        FROM equipment e
        JOIN items i ON e.item_id = i.id
        JOIN sets s ON i.set_id = s.id
        LEFT JOIN users u ON e.holder_id = u.id
        WHERE e.holder_id = ? 
        AND (e.room_id IS NULL OR e.room_id NOT IN (SELECT room_id FROM room_managers WHERE user_id = ?))
        ORDER BY e.code
    ";
    $params = [$userId, $userId];
} else {
    $sql = "
        SELECT e.code, i.name as item_name, i.brand, i.model, 
               r.name as room_name, e.status, e.price, e.purchase_date, e.check_date,
               u.firstname as holder_firstname, u.lastname as holder_lastname,
               e.remark, e.price_remark as eq_price_remark, i.price_remark as item_price_remark, s.price_remark as set_price_remark
        FROM equipment e
        JOIN items i ON e.item_id = i.id
        JOIN sets s ON i.set_id = s.id
        JOIN rooms r ON e.room_id = r.id
        JOIN room_managers rm ON rm.room_id = r.id
        LEFT JOIN users u ON e.holder_id = u.id
        WHERE rm.user_id = ?
    ";

    $params = [$userId];

    if ($room) {
        $sql .= " AND e.room_id = ?";
        $params[] = $room;
    }

    $sql .= " ORDER BY r.name, e.code";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

$equipment = [];
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
    $row['price_remark'] = $priceRemark;
    $equipment[] = $row;
}

// Set headers for Excel download
$filename = 'equipment_' . ($room ? $room . '_' : '') . date('Y-m-d') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// BOM for UTF-8
echo "\xEF\xBB\xBF";

// Status translation function
function getStatusThai($status)
{
    $statuses = [
        'available' => 'พร้อมใช้งาน',
        'repair' => 'ส่งซ่อม',
        'broken' => 'ซ่อมไม่ได้',
        'pending_disposal' => 'รอจำหน่ายออก',
        'disposed' => 'จำหน่ายออก'
    ];
    return $statuses[$status] ?? $status;
}
?>
<html>

<head>
    <meta charset="UTF-8">
</head>

<body>
    <table border="1">
        <thead>
            <tr style="background-color: #0d6efd; color: white; font-weight: bold;">
                <th>ลำดับ</th>
                <th>รหัสครุภัณฑ์</th>
                <th>ชื่อรายการครุภัณฑ์</th>
                <th>ยี่ห้อ</th>
                <th>รุ่น</th>
                <th>ห้อง/สถานที่</th>
                <th>สถานะ</th>
                <th>ราคา</th>
                <th>วันที่ได้รับ/จัดซื้อ</th>
                <th>ตรวจสอบล่าสุด</th>
                <th>ผู้รับผิดชอบดูแล</th>
                <th>หมายเหตุ</th>
                <th>หมายเหตุงบ/ราคา</th>
            </tr>
        </thead>
        <tbody>
            <?php $n = 1;
            foreach ($equipment as $eq): ?>
                <tr>
                    <td><?= $n++ ?></td>
                    <td><?= htmlspecialchars($eq['code'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($eq['item_name']) ?></td>
                    <td><?= htmlspecialchars($eq['brand'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($eq['model'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($eq['room_name'] ?? '-') ?></td>
                    <td><?= getStatusThai($eq['status']) ?></td>
                    <td style="text-align: right;"><?= $eq['price'] ? number_format($eq['price'], 2) : '-' ?></td>
                    <td><?= $eq['purchase_date'] ?? '-' ?></td>
                    <td><?= $eq['check_date'] ?? '-' ?></td>
                    <td><?= $eq['holder_firstname'] ? htmlspecialchars($eq['holder_firstname'] . ' ' . $eq['holder_lastname']) : '-' ?>
                    </td>
                    <td><?= htmlspecialchars($eq['remark'] ?? '') ?></td>
                    <td><?= htmlspecialchars($eq['price_remark'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #f8f9fa; font-weight: bold;">
                <td colspan="7">รวมทั้งหมด <?= count($equipment) ?> รายการ</td>
                <td style="text-align: right;"><?= number_format(array_sum(array_column($equipment, 'price')), 2) ?>
                </td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
    </table>
</body>

</html>