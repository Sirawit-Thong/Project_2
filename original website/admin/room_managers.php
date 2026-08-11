<?php
/**
 * Room Managers
 */
$pageTitle = 'กำหนดผู้รับผิดชอบห้อง/สถานที่';
require_once '../includes/header.php';
requireRole('admin');

$pdo = getDB();

if (isset($_POST['sync_holders_overwrite'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE equipment e 
            JOIN room_managers rm ON e.room_id = rm.room_id 
            SET e.holder_id = rm.user_id 
        ");
        $stmt->execute();
        $updatedRows = $stmt->rowCount();

        if ($updatedRows > 0) {
            setFlash('success', "ซิงค์และเขียนทับข้อมูลผู้ดูแลห้องเป็นผู้ถือครองครุภัณฑ์สำเร็จจำนวน $updatedRows รายการ");
        } else {
            setFlash('info', 'ไม่มีครุภัณฑ์ที่มีการเปลี่ยนแปลงผู้ถือครอง (ข้อมูลตรงกันอยู่แล้ว)');
        }
        logActivity($pdo, getCurrentUserId(), 'Sync Holders', "ซิงค์ข้อมูลผู้ถือครองครุภัณฑ์ (เขียนทับ) $updatedRows รายการ");
    } catch (PDOException $e) {
        setFlash('danger', 'เกิดข้อผิดพลาดในการซิงค์ข้อมูล: ' . $e->getMessage());
    }
    redirect('room_managers.php');
}

if (isset($_POST['sync_holders_fill'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE equipment e 
            JOIN room_managers rm ON e.room_id = rm.room_id 
            SET e.holder_id = rm.user_id 
            WHERE e.holder_id IS NULL
        ");
        $stmt->execute();
        $updatedRows = $stmt->rowCount();

        if ($updatedRows > 0) {
            setFlash('success', "ซิงค์ข้อมูลผู้ดูแลห้องเป็นผู้ถือครองครุภัณฑ์ (เฉพาะช่องว่าง) สำเร็จจำนวน $updatedRows รายการ");
        } else {
            setFlash('info', 'ไม่มีครุภัณฑ์ที่ต้องซิงค์ (ครอบครองครบแล้ว)');
        }
        logActivity($pdo, getCurrentUserId(), 'Sync Holders', "ซิงค์ข้อมูลผู้ถือครองครุภัณฑ์ (เฉพาะช่องว่าง) $updatedRows รายการ");
    } catch (PDOException $e) {
        setFlash('danger', 'เกิดข้อผิดพลาดในการซิงค์ข้อมูล: ' . $e->getMessage());
    }
    redirect('room_managers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'])) {
    $room_id = (int) ($_POST['room_id'] ?? 0);
    $user_id = (int) ($_POST['user_id'] ?? 0);

    if ($room_id && $user_id) {
        try {
            $pdo->prepare("INSERT INTO room_managers (room_id, user_id) VALUES (?, ?)")->execute([$room_id, $user_id]);
            setFlash('success', 'เพิ่มผู้ดูแลสำเร็จ');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                setFlash('danger', 'อาจารย์ท่านนี้ดูแลห้องนี้อยู่แล้ว');
            } else {
                setFlash('danger', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
            }
        }
    } else {
        setFlash('danger', 'กรุณาเลือกข้อมูลให้ครบถ้วน');
    }
    redirect('room_managers.php');
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM room_managers WHERE id = ?")->execute([(int) $_GET['delete']]);
    setFlash('success', 'ลบสำเร็จ');
    redirect('room_managers.php');
}

$managers = $pdo->query("SELECT rm.*, r.name as room_name, u.firstname, u.lastname 
    FROM room_managers rm JOIN rooms r ON rm.room_id = r.id LEFT JOIN users u ON rm.user_id = u.id ORDER BY r.name")->fetchAll();
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY name")->fetchAll();
$teachers = $pdo->query("SELECT * FROM users WHERE role IN ('admin', 'staff', 'teacher') AND status = 'approved' ORDER BY firstname")->fetchAll();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-person-badge me-2"></i>กำหนดผู้รับผิดชอบห้อง/สถานที่</h1>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" class="d-inline"
            onsubmit="return confirm('ยานยันการซิงค์ข้อมูลผู้ดูแลห้องไปยังครุภัณฑ์?\n\n(ระบบจะดึงชื่ออาจารย์ที่ดูแลห้องไปใส่เป็นผู้รับผิดชอบให้ครุภัณฑ์ทุกชิ้นในห้องนั้น *เฉพาะเครื่องที่ช่องผู้รับผิดชอบยังว่างอยู่*)');">
            <button type="submit" name="sync_holders_fill" class="btn btn-outline-primary">
                <i class="bi bi-arrow-repeat me-1"></i>ซิงค์ครุภัณฑ์ (เฉพาะที่ว่าง)
            </button>
        </form>
        <form method="POST" class="d-inline"
            onsubmit="return confirm('คำเตือน: ยืนยันการซิงค์ข้อมูลผู้ดูแลห้องไปยังครุภัณฑ์ แบบเขียนทับ?\n\n(ระบบจะนำชื่ออาจารย์ที่ดูแลห้องไปเขียนทับ *ทับข้อมูลเดิมทั้งหมด* ในช่องผู้รับผิดชอบของครุภัณฑ์ทุกชิ้นที่อยู่ในห้องนั้นๆ)');">
            <button type="submit" name="sync_holders_overwrite" class="btn btn-outline-danger">
                <i class="bi bi-exclamation-triangle me-1"></i>ซิงค์ครุภัณฑ์ (เขียนทับ)
            </button>
        </form>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg me-1"></i>เพิ่ม
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ห้อง/สถานที่</th>
                        <th>ผู้รับผิดชอบดูแล</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($managers as $m): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($m['room_name']) ?></strong></td>
                            <td>
                                <?php if ($m['firstname']): ?>
                                    <?= htmlspecialchars($m['firstname'] . ' ' . $m['lastname']) ?>
                                <?php else: ?>
                                    <span class="text-muted">ผู้ใช้ถูกลบออกจากระบบ</span>
                                <?php endif; ?>
                            </td>
                            <td><a href="room_managers.php?delete=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger"
                                    data-confirm="ลบ?"><i class="bi bi-trash"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">มอบหมายผู้รับผิดชอบห้องใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ห้อง/สถานที่</label>
                        <select class="form-select" name="room_id" required>
                            <option value="">-- เลือก --</option>

                            <?php foreach ($rooms as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ผู้รับผิดชอบดูแล</label>
                        <select class="form-select" name="user_id" required>
                            <option value="">-- เลือก --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>">
                                    <?= htmlspecialchars($t['firstname'] . ' ' . $t['lastname']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>