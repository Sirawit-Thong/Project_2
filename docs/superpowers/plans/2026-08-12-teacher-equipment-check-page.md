# หน้าตรวจสอบและยืนยันสภาพครุภัณฑ์ (อาจารย์) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** สร้างหน้า `/equipment/my` ("ตรวจสอบและยืนยันสภาพครุภัณฑ์") ให้อาจารย์ ตามต้นฉบับ `original website/teacher/my_equipment.php` และแทนที่หน้ารายการทะเบียนเดิมสำหรับอาจารย์ทั้งหมด

**Architecture:** เพิ่ม method `myEquipment()` ใน `EquipmentController` + view ใหม่ `app/Views/equipment/my_equipment.php` (port จากต้นฉบับ) โดยใช้ model method ที่มีอยู่แล้ว (`RoomManager::getManagedRoomCount`, `RoomManager::isOwner`, `Equipment::hasNonManagedEquipment`, `getNonManagedByHolder`, `getByRoomWithItems`, `check`, `updateStatus`) และ route `/teacher/export?room=` ที่มีอยู่แล้ว ลงทะเบียน routes ใหม่ใน `index.php` แล้วเปลี่ยนลิงก์นำทางอาจารย์ให้ชี้มาที่หน้าใหม่

**Tech Stack:** PHP 8.2 (MVC แบบ homegrown: `app/Controllers`, `app/Models`, `app/Views`), Bootstrap 5.3.2, Bootstrap Icons, MySQL

## Global Constraints

- Lint PHP ด้วย: `& "C:\xampp\php\php.exe" -l <file>` (ไม่มี framework สำหรับ lint/test อัตโนมัติ)
- Routes ลงทะเบียนใน `index.php` เท่านั้น (ดู `app/Core/Router.php` — เช็ค exact match ก่อน แล้วค่อย pattern)
- ห้ามแก้ `app/Models/*` และ `/teacher/export` (มีของครบแล้ว)
- ใช้ helper ที่มีอยู่แล้ว: `sanitize()`, `getStatusBadgeClass()`, `translateEquipmentStatus()`, `formatDateThai()`, `csrf_field()`, `logActivity($userId, $action, $details)`, `SITE_URL`
- ภาษา UI = ไทย, ตามสไตล์ view อื่น (ดู `app/Views/equipment/inspection.php` เป็นตัวอย่าง)
- ไฟล์ PHP ใหม่/แก้ ต้องเก็บ docblock หัวไฟล์และ comment ตามสไตล์เดิมของไฟล์นั้นๆ

---

### Task 1: Controller — method `myEquipment()` + teacher redirect ใน `index()`

**Files:**
- Modify: `app/Controllers/EquipmentController.php` — เพิ่ม method `myEquipment()` ต่อท้าย method `inspection()` (จบที่บรรทัด ~371) ก่อน `disposal()` และเพิ่ม redirect ใน `index()`

**Interfaces:**
- Consumes: `RoomManager::getManagedRoomCount($userId)` → `[{id, name, eq_count}]`; `RoomManager::isOwner($userId, $roomId)` → bool; `Equipment::hasNonManagedEquipment($userId)` → bool; `Equipment::getNonManagedByHolder($userId)` → rows; `Equipment::getByRoomWithItems($roomId)` → rows; `Equipment::check($id, $remark)`; `Equipment::updateStatus($id, 'broken')`; `getCurrentUserId()`; `logActivity()`
- Produces: `$managedRooms` (array `{id, name, eq_count}` + `{id:'other', name:'...'}`), `$selectedRoom`, `$selectedRoomName`, `$equipment`, `$eqStats{total,available,broken,inspected}`, `$currentYear` — ให้กับ view `equipment/my_equipment`

- [ ] **Step 1: เพิ่ม redirect ของอาจารย์ใน `index()`**

ใน `index()` หลัง `$role = getCurrentRole();` (บรรทัด 11) และก่อน check `if (!in_array($role, ['admin', 'staff', 'teacher']))` (บรรทัด 13) ให้แทรก:

```php
        if ($role === 'teacher') {
            $this->redirect(SITE_URL . '/equipment/my');
        }
```

- [ ] **Step 2: เพิ่ม method `myEquipment()`**

ต่อท้าย method `inspection()` (ก่อน `public function disposal()`) วางโค้ดนี้ทั้ง block:

```php
    public function myEquipment()
    {
        $this->requireLogin();
        $this->authorize(['teacher']);

        $userId = getCurrentUserId();
        $managedRooms = RoomManager::getManagedRoomCount($userId);

        if (Equipment::hasNonManagedEquipment($userId)) {
            $managedRooms[] = [
                'id' => 'other',
                'name' => 'อื่นๆ (ไม่ได้อยู่ในห้องที่รับผิดชอบ)',
            ];
        }

        $selectedRoom = $_GET['room'] ?? '';
        $currentYear = date('Y');
        $equipment = [];
        $selectedRoomName = '';
        $eqStats = ['total' => 0, 'available' => 0, 'broken' => 0, 'inspected' => 0];

        if ($selectedRoom !== '') {
            if ($selectedRoom === 'other') {
                $selectedRoomName = 'อื่นๆ (ไม่ได้อยู่ในห้องที่รับผิดชอบ)';
                $equipment = Equipment::getNonManagedByHolder($userId);
            } elseif (RoomManager::isOwner($userId, (int) $selectedRoom)) {
                foreach ($managedRooms as $r) {
                    if ((string) $r['id'] === (string) $selectedRoom) {
                        $selectedRoomName = $r['name'];
                        break;
                    }
                }
                $equipment = Equipment::getByRoomWithItems((int) $selectedRoom);
            }
        }

        foreach ($equipment as $item) {
            $eqStats['total']++;
            if ($item['status'] === 'available') $eqStats['available']++;
            if ($item['status'] === 'broken') $eqStats['broken']++;
            if ($item['check_date'] && date('Y', strtotime($item['check_date'])) == $currentYear) {
                $eqStats['inspected']++;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $eqId = (int) ($_POST['equipment_id'] ?? 0);
            $checkStatus = $_POST['check_status'] ?? '';
            $remark = trim($_POST['remark'] ?? '');

            $hasAccess = $this->teacherCanAccessEquipment($eqId, $userId, $selectedRoom);

            if ($hasAccess && $eqId > 0) {
                Equipment::check($eqId, $remark);
                if ($checkStatus === 'broken') {
                    Equipment::updateStatus($eqId, 'broken');
                }
                logActivity($userId, 'Teacher Equipment Check', "ตรวจสอบครุภัณฑ์ ID: $eqId");
                $this->flash('success', 'บันทึกการตรวจสอบสำเร็จ');
            } else {
                $this->flash('danger', 'คุณไม่มีสิทธิ์ตรวจสอบครุภัณฑ์นี้');
            }

            $this->redirect(SITE_URL . '/equipment/my?room=' . urlencode($selectedRoom));
        }

        $pageTitle = 'ตรวจสอบและยืนยันสภาพครุภัณฑ์';
        $viewPath = 'equipment/my_equipment';

        require __DIR__ . '/../Views/layouts/main.php';
    }
```

- [ ] **Step 3: เพิ่ม private helper `teacherCanAccessEquipment()`**

วางต่อท้าย method `myEquipment()` (ก่อน `disposal()`):

```php
    private function teacherCanAccessEquipment($eqId, $userId, $selectedRoom)
    {
        if ($selectedRoom === 'other') {
            $sql = "SELECT id FROM equipment WHERE id = ? AND holder_id = ?";
            return Model::fetchOne($sql, [$eqId, $userId]) !== null;
        }
        $sql = "SELECT e.id FROM equipment e
            JOIN rooms r ON e.room_id = r.id
            JOIN room_managers rm ON rm.room_id = r.id
            WHERE e.id = ? AND rm.user_id = ?";
        return Model::fetchOne($sql, [$eqId, $userId]) !== null;
    }
```

- [ ] **Step 4: Lint**

Run: `& "C:\xampp\php\php.exe" -l "app\Controllers\EquipmentController.php"`
Expected: `No syntax errors detected in app\Controllers\EquipmentController.php`

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/EquipmentController.php
git commit -m "feat: Add myEquipment controller method for teacher equipment check page"
```

---

### Task 2: View — `app/Views/equipment/my_equipment.php`

**Files:**
- Create: `app/Views/equipment/my_equipment.php`

**Interfaces:**
- Consumes: `$managedRooms`, `$selectedRoom`, `$selectedRoomName`, `$equipment`, `$eqStats`, `$currentYear` (จาก Task 1)
- Produces: หน้า HTML+JS ที่ form POST ไป `/equipment/my?room=<selected>` พร้อม `csrf_field()`

- [ ] **Step 1: สร้างไฟล์ view**

สร้าง `app/Views/equipment/my_equipment.php` ด้วยเนื้อหานี้ (port จาก `original website/teacher/my_equipment.php` — เปลี่ยนเป็น MVC):

```php
<?php
/**
 * Teacher Equipment Check
 * ตรวจสอบและยืนยันสภาพครุภัณฑ์ (สำหรับอาจารย์)
 *
 * Variables from controller:
 *   $managedRooms, $selectedRoom, $selectedRoomName, $equipment, $eqStats, $currentYear
 */
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-clipboard-check me-2"></i>ตรวจสอบและยืนยันสภาพครุภัณฑ์</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">ตรวจสอบครุภัณฑ์</li>
            </ol>
        </nav>
    </div>
</div>

<?php if (empty($managedRooms)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        คุณยังไม่ได้รับมอบหมายให้ดูแลห้องปฏิบัติการใด กรุณาติดต่อเจ้าหน้าที่ดูแลระบบ
    </div>
<?php else: ?>

    <!-- Room Selection -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= SITE_URL ?>/equipment/my" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">เลือกห้องปฏิบัติการที่ต้องการตรวจสอบ</label>
                    <select name="room" class="form-select" onchange="this.form.submit()">
                        <option value="">-- กรุณาเลือกห้อง --</option>
                        <?php foreach ($managedRooms as $room): ?>
                            <option value="<?= sanitize($room['id']) ?>"
                                <?= $selectedRoom === (string) $room['id'] ? 'selected' : '' ?>>
                                <?php if ((string) $room['id'] === 'other'): ?>
                                    *** <?= sanitize($room['name']) ?>
                                <?php else: ?>
                                    <?= sanitize($room['name']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <?php if ($selectedRoom !== ''): ?>
                        <a href="<?= SITE_URL ?>/equipment/my" class="btn btn-outline-secondary">ล้างค่า</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selectedRoom !== '' && !empty($equipment)): ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-6">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title"><i class="bi bi-pc-display me-1"></i>ครุภัณฑ์ทั้งหมด</h6>
                        <h2 class="display-4 fw-bold"><?= $eqStats['total'] ?></h2>
                        <p class="mb-0">รายการ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title"><i class="bi bi-check-circle me-1"></i>พร้อมใช้งาน</h6>
                        <h2 class="display-4 fw-bold"><?= $eqStats['available'] ?></h2>
                        <p class="mb-0">รายการ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-danger text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title"><i class="bi bi-x-circle me-1"></i>ชำรุด</h6>
                        <h2 class="display-4 fw-bold"><?= $eqStats['broken'] ?></h2>
                        <p class="mb-0">รายการ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title"><i class="bi bi-calendar-check me-1"></i>ตรวจสอบแล้ว</h6>
                        <h2 class="display-4 fw-bold"><?= $eqStats['inspected'] ?></h2>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar bg-white"
                                style="width: <?= $eqStats['total'] > 0 ? ($eqStats['inspected'] / $eqStats['total']) * 100 : 0 ?>%">
                            </div>
                        </div>
                        <p class="mb-0 mt-1">
                            <?= $eqStats['total'] > 0 ? number_format(($eqStats['inspected'] / $eqStats['total']) * 100, 1) : 0 ?>%
                            ของทั้งหมด
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipment List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>รายการครุภัณฑ์ — ห้อง
                    <?= sanitize($selectedRoomName) ?>
                </h5>
                <a href="<?= SITE_URL ?>/teacher/export?room=<?= urlencode($selectedRoom) ?>" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel me-1"></i>ส่งออก Excel
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="hide-mobile">#</th>
                                <th>รหัสครุภัณฑ์</th>
                                <th>รายการ</th>
                                <th>สถานะ</th>
                                <th class="hide-mobile">ตรวจสอบล่าสุด</th>
                                <th style="width: 200px;">ดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $n = 1;
                            foreach ($equipment as $eq):
                                $isInspectedThisYear = ($eq['check_date'] && date('Y', strtotime($eq['check_date'])) == $currentYear);
                                ?>
                                <tr>
                                    <td class="hide-mobile"><?= $n++ ?></td>
                                    <td>
                                        <span class="badge bg-secondary font-monospace"><?= sanitize($eq['code'] ?? '-') ?></span>
                                    </td>
                                    <td>
                                        <strong><?= sanitize($eq['item_name']) ?></strong>
                                        <?php if ($eq['brand'] || $eq['model']): ?>
                                            <div class="text-muted small">
                                                <?= sanitize($eq['brand'] ?? '') ?>
                                                <?= sanitize($eq['model'] ?? '') ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadgeClass($eq['status']) ?>">
                                            <?= translateEquipmentStatus($eq['status']) ?>
                                        </span>
                                    </td>
                                    <td class="hide-mobile">
                                        <?php if ($eq['check_date']): ?>
                                            <span class="<?= $isInspectedThisYear ? 'text-success fw-bold' : 'text-muted' ?>">
                                                <i class="bi bi-check-circle me-1"></i><?= formatDateThai($eq['check_date']) ?>
                                            </span>
                                            <?php if (!$isInspectedThisYear): ?>
                                                <i class="bi bi-exclamation-circle text-warning" title="ยังไม่ได้ตรวจปีนี้"></i>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-danger">- ไม่เคยตรวจ -</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= SITE_URL ?>/equipment/<?= $eq['id'] ?>" class="btn btn-sm btn-outline-primary"
                                            title="ดูรายละเอียด">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                                            data-bs-target="#checkModal" data-id="<?= $eq['id'] ?>"
                                            data-code="<?= sanitize($eq['code'] ?? '-') ?>"
                                            data-name="<?= sanitize($eq['item_name']) ?>"
                                            data-remark="<?= sanitize($eq['remark'] ?? '') ?>">
                                            <i class="bi bi-check2-circle me-1"></i>ตรวจสอบ
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Check Modal -->
        <div class="modal fade" id="checkModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="<?= SITE_URL ?>/equipment/my?room=<?= urlencode($selectedRoom) ?>">
                        <?= csrf_field() ?>
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title"><i class="bi bi-clipboard-check me-2"></i>ยืนยันผลการตรวจสอบสภาพครุภัณฑ์
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="equipment_id" id="modalEquipmentId">

                            <div class="mb-3">
                                <label class="form-label fw-bold">ครุภัณฑ์</label>
                                <div id="modalEquipmentInfo" class="form-control-plaintext">
                                    <span class="badge bg-secondary font-monospace" id="modalCodeBadge"></span>
                                    <span class="fw-bold ms-2" id="modalNameText"></span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">ผลการตรวจสอบ</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="check_status" value="ok" id="statusOk"
                                            checked>
                                        <label class="btn btn-outline-success w-100 py-3" for="statusOk">
                                            <i class="bi bi-check-circle fs-4 d-block mb-1"></i>
                                            <span class="fw-bold">พร้อมใช้งาน</span>
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="check_status" value="broken"
                                            id="statusBroken">
                                        <label class="btn btn-outline-danger w-100 py-3" for="statusBroken">
                                            <i class="bi bi-x-circle fs-4 d-block mb-1"></i>
                                            <span class="fw-bold">ชำรุด / เสียหาย</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">หมายเหตุ</label>
                                <textarea class="form-control" name="remark" id="modalRemark" rows="3"
                                    placeholder="บันทึกหมายเหตุเพิ่มเติม..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg me-1"></i>บันทึกผลการตรวจสอบ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.getElementById('checkModal').addEventListener('show.bs.modal', function (e) {
                const btn = e.relatedTarget;
                document.getElementById('modalEquipmentId').value = btn.dataset.id;
                document.getElementById('modalCodeBadge').textContent = btn.dataset.code;
                document.getElementById('modalNameText').textContent = btn.dataset.name;
                document.getElementById('modalRemark').value = btn.dataset.remark;
                document.getElementById('statusOk').checked = true;
            });
        </script>

    <?php elseif ($selectedRoom !== ''): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-search display-1 text-muted"></i>
            <h4 class="mt-3">ไม่พบครุภัณฑ์ในห้อง <?= sanitize($selectedRoomName ?: $selectedRoom) ?></h4>
            <p>กรุณาเลือกห้องอื่น หรือติดต่อเจ้าหน้าที่เพื่อเพิ่มครุภัณฑ์</p>
        </div>
    <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-arrow-up-circle display-1"></i>
            <h4 class="mt-3">กรุณาเลือกห้องเพื่อเริ่มการตรวจสอบ</h4>
        </div>
    <?php endif; ?>

<?php endif; ?>
```

- [ ] **Step 2: Lint**

Run: `& "C:\xampp\php\php.exe" -l "app\Views\equipment\my_equipment.php"`
Expected: `No syntax errors detected in app\Views\equipment\my_equipment.php`

- [ ] **Step 3: Commit**

```bash
git add app/Views/equipment/my_equipment.php
git commit -m "feat: Add teacher equipment check view matching original design"
```

---

### Task 3: Routes — ลงทะเบียน `/equipment/my`

**Files:**
- Modify: `index.php` — เพิ่ม routes ก่อน `$router->get('/equipment/{id}', 'EquipmentController@detail');` (บรรทัด 40)

**Interfaces:**
- Consumes: `EquipmentController@myEquipment` (Task 1)
- Produces: URL ที่เข้าถึงได้ `GET/POST /equipment/my`

- [ ] **Step 1: เพิ่ม routes**

แก้ block ระหว่าง `/equipment/bulk-add` (บรรทัด 38-39) และ `/equipment/{id}` (บรรทัด 40):

```php
$router->get('/equipment/my', 'EquipmentController@myEquipment');
$router->post('/equipment/my', 'EquipmentController@myEquipment');
```

ให้เป็นดังนี้ (วาง `my` routes ระหว่างบรรทัด 39 กับ 40):

```php
$router->get('/equipment/bulk-add', 'EquipmentController@bulkAdd');
$router->post('/equipment/bulk-add', 'EquipmentController@bulkAdd');
$router->get('/equipment/my', 'EquipmentController@myEquipment');
$router->post('/equipment/my', 'EquipmentController@myEquipment');
$router->get('/equipment/{id}', 'EquipmentController@detail');
```

- [ ] **Step 2: Lint**

Run: `& "C:\xampp\php\php.exe" -l "index.php"`
Expected: `No syntax errors detected in index.php`

- [ ] **Step 3: ตรวจเบื้องต้นผ่าน HTTP (ไม่ต้อง login)**

Run: `(Invoke-WebRequest -Uri "http://localhost/P/equipment/my" -MaximumRedirection 0 -UseBasicParsing).Headers.Location`
Expected: URL ชี้ไป `/login` (เพราะยังไม่ login — แสดงว่า route ไปถึง controller แล้วไม่ใช่ 404)

หมายเหตุ: ถ้าขึ้น error หน้า `403/404` ให้ตรวจว่ามีไฟล์ route ซ้ำกันหรือตัว Router จับ `/equipment/{id}` ก่อน

- [ ] **Step 4: Commit**

```bash
git add index.php
git commit -m "feat: Register /equipment/my routes for teacher equipment check"
```

---

### Task 4: ลิงก์นำทางอาจารย์ — sidebar, teacher report, detail breadcrumb

**Files:**
- Modify: `includes/sidebar.php` (บรรทัด 150) — เปลี่ยน href ของ "ตรวจสอบครุภัณฑ์ที่รับผิดชอบ"
- Modify: `app/Views/dashboard/teacher_report.php` (บรรทัด 114) — ลิงก์ห้อง
- Modify: `app/Views/equipment/detail.php` (บรรทัด 15) — breadcrumb สำหรับอาจารย์

**Interfaces:**
- Consumes: route `/equipment/my` (Task 3)
- Produces: อาจารย์ทุกจุดนำทางไปยังหน้าใหม่

- [ ] **Step 1: แก้ `includes/sidebar.php`**

เปลี่ยนบรรทัด 150 จาก:

```php
<a href="<?= SITE_URL ?>/equipment" class="nav-link <?= isSidebarActive('/equipment') ?>">
```

เป็น:

```php
<a href="<?= SITE_URL ?>/equipment/my" class="nav-link <?= isSidebarActive('/equipment') ?>">
```

(ไม่ต้องแก้ `isSidebarActive('/equipment')` เพราะ `/equipment/my` ขึ้นต้นด้วย `/equipment` → active อยู่แล้ว)

- [ ] **Step 2: แก้ `app/Views/dashboard/teacher_report.php`**

เปลี่ยนบรรทัด 114 จาก:

```php
<a href="<?= SITE_URL ?>/equipment?room=<?= urlencode($room['room_id']) ?>"
```

เป็น:

```php
<a href="<?= SITE_URL ?>/equipment/my?room=<?= urlencode($room['room_id']) ?>"
```

- [ ] **Step 3: แก้ `app/Views/equipment/detail.php`**

เปลี่ยนบรรทัด 15 จาก:

```php
<li class="breadcrumb-item"><a href="<?= SITE_URL ?>/equipment"><?= $listLabel ?></a></li>
```

เป็น:

```php
<li class="breadcrumb-item"><a href="<?= SITE_URL ?><?= $role === 'teacher' ? '/equipment/my' : '/equipment' ?>"><?= $listLabel ?></a></li>
```

($role ถูกกำหนดไว้แล้วที่บรรทัด 3 ของไฟล์นี้)

- [ ] **Step 4: Lint 3 ไฟล์**

Run (ทีละไฟล์):
- `& "C:\xampp\php\php.exe" -l "includes\sidebar.php"`
- `& "C:\xampp\php\php.exe" -l "app\Views\dashboard\teacher_report.php"`
- `& "C:\xampp\php\php.exe" -l "app\Views\equipment\detail.php"`
Expected: ทุกไฟล์ `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add includes/sidebar.php app/Views/dashboard/teacher_report.php app/Views/equipment/detail.php
git commit -m "feat: Point teacher navigation to /equipment/my"
```

---

### Task 5: ทดสอบแบบ end-to-end ผ่านเบราว์เซอร์

**Files:** ไม่แก้โค้ด — แค่ทดสอบ

- [ ] **Step 1: login เป็นอาจารย์**

เปิด `http://localhost/P/login` ในเบราว์เซอร์ (ใช้ chrome-devtools)
- เข้าสู่ระบบด้วยบัญชีอาจารย์ที่ดูแลห้อง (เช่น `teacher@rmutsb.ac.th` ดูแลห้อง 6102 — ถ้าไม่รู้รหัส ให้ถามผู้ใช้)
- หลัง login แล้วให้อยู่ใน session อาจารย์

- [ ] **Step 2: ตรวจ redirect จาก `/equipment`**

นำทางไป `http://localhost/P/equipment` → ควรถูก redirect ไป `/equipment/my`

- [ ] **Step 3: ตรวจ sidebar**

คลิกเมนู sidebar "ตรวจสอบครุภัณฑ์ที่รับผิดชอบ" → ควรไป `/equipment/my` และเมนูมีสถานะ active

- [ ] **Step 4: เลือกห้อง + ตรวจสอบหน้า**

- หน้าแรกเห็น dropdown "เลือกห้องปฏิบัติการที่ต้องการตรวจสอบ" + ข้อความ "กรุณาเลือกห้องเพื่อเริ่มการตรวจสอบ"
- เลือกห้อง 6102 → เห็นการ์ดสถิติ 4 ใบ + ตารางครุภัณฑ์ + ปุ่ม "ตรวจสอบ" + ปุ่ม "ส่งออก Excel"
- กดไอคอนตา → ไปหน้า `/equipment/{id}` ได้ และ breadcrumb ชี้กลับ `/equipment/my`

- [ ] **Step 5: ทดสอบ POST — "พร้อมใช้งาน"**

- กดปุ่ม "ตรวจสอบ" แถวแรก → modal เปิด แสดงรหัส/ชื่อถูกต้อง
- เลือก "พร้อมใช้งาน" + ใส่หมายเหตุ "ตรวจตามปกติ" → กด "บันทึกผลการตรวจสอบ"
- หลัง redirect ควรเห็น flash "บันทึกการตรวจสอบสำเร็จ"
- ตรวจ DB: `SELECT check_date, remark FROM equipment_db.equipment WHERE id = <id>` → `check_date` = วันนี้, `remark` = "ตรวจตามปกติ", สถานะยังเป็นค่าเดิม

- [ ] **Step 6: ทดสอบ POST — "ชำรุด/เสียหาย"**

- กด "ตรวจสอบ" แถวแรก → เลือก "ชำรุด / เสียหาย" + หมายเหตุ → บันทึก
- ตรวจ DB: `SELECT status, check_date, remark FROM equipment_db.equipment WHERE id = <id>` → `status` = `broken`, `check_date` = วันนี้

- [ ] **Step 7: ตรวจสิทธิ์**

- ดัด URL POST ด้วย `?room=999` (ห้องที่อาจารย์ไม่ได้ดูแล) → ระบบต้องไม่บันทึก + flash "คุณไม่มีสิทธิ์ตรวจสอบครุภัณฑ์นี้"
- ตรวจ DB ว่า `check_date` ของครุภัณฑ์นั้นไม่เปลี่ยน

- [ ] **Step 8: ตรวจสอบผู้ใช้ที่ไม่มีห้องดูแล**

- login อาจารย์ที่ไม่มี `room_managers` (เช่น `test.teacher@rmutsb.ac.th`) → เห็น `alert-warning` "คุณยังไม่ได้รับมอบหมายให้ดูแลห้องปฏิบัติการใด..."

หมายเหตุ: ถ้าขั้นไหนพัง ให้ใช้ `skills/superpowers/debug-mantra` หรือ `systematic-debugging` ก่อนแก้

---

## Self-Review Notes

- Spec ครอบคลุมครบ: controller (Task 1), view (Task 2), routes (Task 3), ลิงก์แทนที่ sidebar/report/detail (Task 4), ทดสอบ (Task 5)
- POST form พา `?room=` ไปด้วย (Task 2 view action) — ตาม spec
- ใช้เฉพาะ model method ที่มีอยู่แล้ว ไม่แก้ model
- ไม่มี placeholder — โค้ดครบทุกขั้น
