<?php
/**
 * Equipment Controller
 * จัดการครุภัณฑ์ — list, add, edit, detail, bulk-add, inspection, disposal
 */
class EquipmentController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $role = getCurrentRole();

        if ($role === 'teacher') {
            $this->redirect(SITE_URL . '/equipment/my');
        }

        if (!in_array($role, ['admin', 'staff', 'teacher'])) {
            ErrorHandler::page403();
        }

        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $room = $_GET['room'] ?? '';
        $item = $_GET['item'] ?? '';
        $dept = $_GET['dept'] ?? '';
        $set = $_GET['set'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));

        $perPageOptions = [10, 20, 50, 100];
        $perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions, true)
            ? (int) $_GET['per_page'] : 20;

        $result = Equipment::getFiltered(
            compact('search', 'status', 'room', 'item', 'dept', 'set'),
            $page,
            $perPage
        );

        $departments = Department::getAll();
        $rooms = Room::getAll();
        $sets = SetModel::getAllWithDept();
        $items = Item::getAllForDropdown();

        $pageTitle = $role === 'teacher' ? 'ตรวจสอบครุภัณฑ์' : 'ทะเบียนครุภัณฑ์';
        $viewPath = 'equipment/index';

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function delete($id)
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);
        $this->validateCsrf();

        $equipment = Equipment::find($id);
        if (!$equipment) ErrorHandler::page404();

        if (Equipment::hasRepairHistory($id)) {
            $this->flash('danger', 'ไม่สามารถลบได้ เนื่องจากมีประวัติการซ่อม');
            $this->redirect(SITE_URL . '/equipment');
        }

        $images = Equipment::getImages($id);
        Equipment::deleteWithImages($id);
        foreach ($images as $img) {
            $filepath = UPLOAD_PATH . $img['path'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        logActivity(getCurrentUserId(), 'Delete Equipment', 'ลบครุภัณฑ์ ID: ' . $id);
        $this->flash('success', 'ลบครุภัณฑ์สำเร็จ');
        $this->redirect(SITE_URL . '/equipment');
    }

    public function add()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $departments = Department::getAll();
        $rooms = Room::getAll();
        $holders = User::getHolders();
        $allItems = Item::getAllForDropdown();
        $allSets = SetModel::getAllWithDept();
        $managersByRoom = [];
        foreach (RoomManager::getAllWithRoomAndUser() as $rm) {
            $managersByRoom[$rm['room_id']][] = $rm;
        }
        $pageTitle = 'เพิ่มครุภัณฑ์';
        $viewPath = 'equipment/form';
        $equipment = null;
        $existingImages = [];
        $preselectedItemId = $_GET['item_id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $data = $this->inputs([
                'code' => '', 'item_id' => '', 'room_id' => null,
                'status' => 'available', 'purchase_date' => null,
                'check_date' => null, 'price' => null, 'price_remark' => null,
                'holder_id' => null, 'remark' => null,
            ]);

            // Normalize FK + nullable fields กัน SQLSTATE 1452 / ''. ใช้ null แทนค่าว่าง
            $data['room_id'] = !empty($data['room_id']) ? (int) $data['room_id'] : null;
            if ($data['room_id'] !== null && !Model::find('rooms', $data['room_id'])) {
                $data['room_id'] = null;
            }
            $data['holder_id'] = !empty($data['holder_id']) ? (int) $data['holder_id'] : null;
            if ($data['holder_id'] !== null && !Model::find('users', $data['holder_id'])) {
                $data['holder_id'] = null;
            }
            $data['purchase_date'] = !empty($data['purchase_date']) ? $data['purchase_date'] : null;
            $data['check_date'] = !empty($data['check_date']) ? $data['check_date'] : null;
            $data['price'] = ($data['price'] !== '' && $data['price'] !== null) ? $data['price'] : null;
            $data['price_remark'] = !empty($data['price_remark']) ? $data['price_remark'] : null;
            $data['remark'] = !empty($data['remark']) ? $data['remark'] : null;
            if (!empty($data['item_id'])) {
                $data['item_id'] = (int) $data['item_id'];
            }

            $errors = [];
            if (empty($data['code'])) $errors[] = 'กรุณากรอกรหัสครุภัณฑ์';
            if (empty($data['item_id'])) $errors[] = 'กรุณาเลือกรายการครุภัณฑ์';
            if (!empty($data['code']) && Equipment::isCodeTaken($data['code'])) {
                $errors[] = 'รหัสครุภัณฑ์นี้มีในระบบแล้ว';
            }

            // Check quantity limit (only for new equipment)
            if (!empty($data['item_id'])) {
                $limit = Equipment::checkQtyLimit($data['item_id']);
                if ($limit['qty'] > 0 && $limit['exceeded']) {
                    $errors[] = 'ไม่สามารถเพิ่มได้ รายการ "' . $limit['name'] . '" มีจำนวน ' . $limit['qty'] . ' ชิ้น ลงทะเบียนครบแล้ว';
                }
            }

            // Validate dates: ไม่เกินวันนี้ + check_date ไม่ก่อน purchase_date
            $today = date('Y-m-d');
            if (!empty($data['purchase_date'])) {
                if ($data['purchase_date'] > $today) {
                    $errors[] = 'วันที่จัดซื้อต้องไม่เกินวันนี้ (' . $today . ')';
                }
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['purchase_date']) || strtotime($data['purchase_date']) === false) {
                    $errors[] = 'รูปแบบวันที่จัดซื้อไม่ถูกต้อง';
                }
            }
            if (!empty($data['check_date'])) {
                if ($data['check_date'] > $today) {
                    $errors[] = 'วันที่ตรวจเช็คต้องไม่เกินวันนี้ (' . $today . ')';
                }
                if (!empty($data['purchase_date']) && $data['check_date'] < $data['purchase_date']) {
                    $errors[] = 'วันที่ตรวจเช็คต้องไม่ก่อนวันที่จัดซื้อ (' . $data['purchase_date'] . ')';
                }
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['check_date']) || strtotime($data['check_date']) === false) {
                    $errors[] = 'รูปแบบวันที่ตรวจเช็คไม่ถูกต้อง';
                }
            }

            if (empty($errors)) {
                // Force price to 0 if parent item or set has price
                $parentPrices = Equipment::getParentPrices($data['item_id']);
                if ($parentPrices && ($parentPrices['item_price'] > 0 || $parentPrices['set_price'] > 0)) {
                    $data['price'] = 0;
                }

                $id = Equipment::create($data);
                $uploadErrors = $this->saveImages($id);
                logActivity(getCurrentUserId(), 'Add Equipment', 'เพิ่มครุภัณฑ์: ' . $data['code']);
                if (!empty($uploadErrors)) {
                    $this->flash('warning', 'เพิ่มครุภัณฑ์สำเร็จ แต่บางรูปไม่ถูกบันทึก:<br>' . implode('<br>', array_slice($uploadErrors, 0, 5)));
                } else {
                    $this->flash('success', 'เพิ่มครุภัณฑ์สำเร็จ');
                }
                $this->redirect(SITE_URL . '/equipment');
            }

            $this->flash('danger', implode('<br>', $errors));
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function edit($id)
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $equipment = Equipment::find($id);
        if (!$equipment) ErrorHandler::page404();

        $departments = Department::getAll();
        $rooms = Room::getAll();
        $holders = User::getHolders();
        $allItems = Item::getAllForDropdown();
        $allSets = SetModel::getAllWithDept();
        $managersByRoom = [];
        foreach (RoomManager::getAllWithRoomAndUser() as $rm) {
            $managersByRoom[$rm['room_id']][] = $rm;
        }
        $pageTitle = 'แก้ไขครุภัณฑ์';
        $viewPath = 'equipment/form';
        $existingImages = Equipment::getImages($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $data = $this->inputs([
                'code' => '', 'item_id' => '', 'room_id' => null,
                'status' => 'available', 'purchase_date' => null,
                'check_date' => null, 'price' => null, 'price_remark' => null,
                'holder_id' => null, 'remark' => null,
            ]);

            // Normalize FK + nullable fields กัน SQLSTATE 1452 เหมือน add()
            $data['room_id'] = !empty($data['room_id']) ? (int) $data['room_id'] : null;
            if ($data['room_id'] !== null && !Model::find('rooms', $data['room_id'])) {
                $data['room_id'] = null;
            }
            $data['holder_id'] = !empty($data['holder_id']) ? (int) $data['holder_id'] : null;
            if ($data['holder_id'] !== null && !Model::find('users', $data['holder_id'])) {
                $data['holder_id'] = null;
            }
            $data['purchase_date'] = !empty($data['purchase_date']) ? $data['purchase_date'] : null;
            $data['check_date'] = !empty($data['check_date']) ? $data['check_date'] : null;
            $data['price'] = ($data['price'] !== '' && $data['price'] !== null) ? $data['price'] : null;
            $data['price_remark'] = !empty($data['price_remark']) ? $data['price_remark'] : null;
            $data['remark'] = !empty($data['remark']) ? $data['remark'] : null;
            if (!empty($data['item_id'])) {
                $data['item_id'] = (int) $data['item_id'];
            }

            $errors = [];
            if (empty($data['code'])) $errors[] = 'กรุณากรอกรหัสครุภัณฑ์';
            if (empty($data['item_id'])) $errors[] = 'กรุณาเลือกรายการครุภัณฑ์';
            if (!empty($data['code']) && Equipment::isCodeTaken($data['code'], $id)) {
                $errors[] = 'รหัสครุภัณฑ์นี้มีในระบบแล้ว';
            }

            // Validate dates: ไม่เกินวันนี้ + check_date ไม่ก่อน purchase_date
            $today = date('Y-m-d');
            if (!empty($data['purchase_date'])) {
                if ($data['purchase_date'] > $today) {
                    $errors[] = 'วันที่จัดซื้อต้องไม่เกินวันนี้ (' . $today . ')';
                }
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['purchase_date']) || strtotime($data['purchase_date']) === false) {
                    $errors[] = 'รูปแบบวันที่จัดซื้อไม่ถูกต้อง';
                }
            }
            if (!empty($data['check_date'])) {
                if ($data['check_date'] > $today) {
                    $errors[] = 'วันที่ตรวจเช็คต้องไม่เกินวันนี้ (' . $today . ')';
                }
                if (!empty($data['purchase_date']) && $data['check_date'] < $data['purchase_date']) {
                    $errors[] = 'วันที่ตรวจเช็คต้องไม่ก่อนวันที่จัดซื้อ (' . $data['purchase_date'] . ')';
                }
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['check_date']) || strtotime($data['check_date']) === false) {
                    $errors[] = 'รูปแบบวันที่ตรวจเช็คไม่ถูกต้อง';
                }
            }

            if (empty($errors)) {
                // Force price to 0 if parent item or set has price
                $parentPrices = Equipment::getParentPrices($data['item_id']);
                if ($parentPrices && ($parentPrices['item_price'] > 0 || $parentPrices['set_price'] > 0)) {
                    $data['price'] = 0;
                }

                Equipment::update($id, $data);
                $uploadErrors = $this->saveImages($id);
                logActivity(getCurrentUserId(), 'Edit Equipment', 'แก้ไขครุภัณฑ์: ' . $data['code']);
                if (!empty($uploadErrors)) {
                    $this->flash('warning', 'แก้ไขครุภัณฑ์สำเร็จ แต่บางรูปไม่ถูกบันทึก:<br>' . implode('<br>', array_slice($uploadErrors, 0, 5)));
                } else {
                    $this->flash('success', 'แก้ไขครุภัณฑ์สำเร็จ');
                }
                $this->redirect(SITE_URL . '/equipment/' . $id);
            }

            $this->flash('danger', implode('<br>', $errors));
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function detail($id)
    {
        $this->requireLogin();
        $role = getCurrentRole();

        if ($role === 'teacher') {
            $equipment = Equipment::getDetail($id, getCurrentUserId());
        } else {
            $this->authorize(['admin', 'staff']);
            $equipment = Equipment::getDetail($id);
        }

        if (!$equipment) ErrorHandler::page404();

        $images = Equipment::getImages($id);
        $repairHistory = Equipment::getRepairHistory($id, 10);
        $pageTitle = 'รายละเอียดข้อมูลครุภัณฑ์';
        $viewPath = 'equipment/detail';

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function bulkAdd()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $departments = Department::getAll();
        $rooms = Room::getAll();
        $holders = User::getHolders();
        $allItems = Item::getAllForDropdown();
        $allSets = SetModel::getAllWithDept();
        $managersByRoom = [];
        foreach (RoomManager::getAllWithRoomAndUser() as $rm) {
            $managersByRoom[$rm['room_id']][] = $rm;
        }
        $pageTitle = 'เพิ่มครุภัณฑ์หลายรายการ';
        $viewPath = 'equipment/bulk_add';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $codes = $_POST['codes'] ?? [];
            if (!is_array($codes)) {
                $codes = [$codes];
            }
            $itemId = $_POST['item_id'] ?? null;
            $roomId = $_POST['room_id'] ?? null;
            $holderId = $_POST['holder_id'] ?? null;
            $status = $_POST['status'] ?? 'available';
            $remark = $_POST['remark'] ?? null;
            $purchaseDate = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
            $price = ($_POST['price'] ?? '') !== '' ? $_POST['price'] : null;

            // Normalize FK กัน 1452 เหมือน add()
            $itemId = !empty($itemId) ? (int) $itemId : null;
            $roomId = !empty($roomId) ? (int) $roomId : null;
            if ($roomId !== null && !Model::find('rooms', $roomId)) {
                $roomId = null;
            }
            $holderId = !empty($holderId) ? (int) $holderId : null;
            if ($holderId !== null && !Model::find('users', $holderId)) {
                $holderId = null;
            }
            if (!empty($remark)) {
                $remark = trim($remark);
            } else {
                $remark = null;
            }

            $errors = [];
            $validCodes = array_values(array_unique(array_map('trim', $codes)));

            if (empty($itemId)) $errors[] = 'กรุณาเลือกรายการครุภัณฑ์';
            if (empty($validCodes)) $errors[] = 'กรุณากรอกรหัสครุภัณฑ์อย่างน้อย 1 รายการ';

            // เช็คจำนวนคงเหลือเทียบกับจำนวนที่กำหนด (qty) ของรายการ
            if ($itemId && !empty($validCodes)) {
                $itemInfo = null;
                foreach ($allItems as $it) {
                    if ((int) $it['id'] === (int) $itemId) { $itemInfo = $it; break; }
                }
                if ($itemInfo && (int) $itemInfo['qty'] > 0) {
                    $existingCount = (int) ($itemInfo['existing_count'] ?? 0);
                    $remaining = (int) $itemInfo['qty'] - $existingCount;
                    if (count($validCodes) > $remaining) {
                        $errors[] = 'ไม่สามารถเพิ่มได้ รายการ "' . $itemInfo['name'] . '" มีจำนวน ' . $itemInfo['qty'] . ' ชิ้น ลงทะเบียนแล้ว ' . $existingCount . ' ชิ้น เพิ่มได้อีก ' . $remaining . ' ชิ้น (คุณกำลังเพิ่ม ' . count($validCodes) . ' ชิ้น)';
                    }
                }
            }

            // Validate purchase_date ไม่เกินวันนี้
            if (!empty($purchaseDate)) {
                $today = date('Y-m-d');
                if ($purchaseDate > $today) {
                    $errors[] = 'วันที่จัดซื้อต้องไม่เกินวันนี้ (' . $today . ')';
                }
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchaseDate) || strtotime($purchaseDate) === false) {
                    $errors[] = 'รูปแบบวันที่จัดซื้อไม่ถูกต้อง';
                }
            }

            $added = 0;
            $skipped = 0;
            if (empty($errors)) {
                // เช็ค code ซ้ำในระบบครั้งเดียว แล้ว insert ทั้งหมดใน transaction เดียว
                $existingSet = array_flip(Equipment::getExistingCodes($validCodes));
                $rows = [];
                foreach ($validCodes as $code) {
                    if (isset($existingSet[$code])) {
                        $skipped++;
                        continue;
                    }
                    $rows[] = [
                        'code' => $code,
                        'item_id' => $itemId,
                        'room_id' => $roomId ?: null,
                        'holder_id' => $holderId ?: null,
                        'status' => $status,
                        'remark' => $remark,
                        'purchase_date' => $purchaseDate,
                        'price' => $price !== null ? (float) $price : null,
                    ];
                }

                if (!empty($rows)) {
                    $added = Equipment::bulkCreate($rows);
                }

                logActivity(getCurrentUserId(), 'Bulk Add Equipment', 'เพิ่มครุภัณฑ์จำนวนมาก: ' . $added . ' รายการ');
                $msg = "เพิ่มครุภัณฑ์สำเร็จ {$added} รายการ";
                if ($skipped > 0) {
                    $msg .= " (ข้าม {$skipped} รายการที่ซ้ำกัน)";
                }
                $this->flash('success', $msg);
                $this->redirect(SITE_URL . '/equipment');
            }

            $this->flash('danger', implode('<br>', $errors));
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function inspection()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $rooms = Room::getAll();
        $pageTitle = 'ระบบตรวจนับครุภัณฑ์ประจำปี';
        $viewPath = 'equipment/inspection';
        $selectedRoom = $_GET['room_id'] ?? null;
        $currentYear = date('Y');
        $equipment = [];
        $stats = ['total' => 0, 'inspected' => 0, 'pending' => 0];

        if ($selectedRoom) {
            $equipment = Equipment::getByRoomForInspection($selectedRoom);

            foreach ($equipment as $item) {
                $stats['total']++;
                $isInspected = $item['check_date'] && date('Y', strtotime($item['check_date'])) == $currentYear;
                if ($isInspected) {
                    $stats['inspected']++;
                } else {
                    $stats['pending']++;
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $selectedRoom = $_POST['room_id'] ?? $selectedRoom;

            $inspectedIds = $_POST['inspected'] ?? [];
            $statuses = $_POST['status'] ?? [];
            $remarks = $_POST['remark'] ?? [];
            $allIds = $_POST['item_ids'] ?? [];

            $updated = 0;
            foreach ($allIds as $id) {
                $status = $statuses[$id] ?? 'available';
                $remark = $remarks[$id] ?? '';
                $isInspected = in_array($id, $inspectedIds);

                if ($isInspected) {
                    Equipment::inspectionUpdate($id, $status, $remark, true);
                    $updated++;
                } else {
                    Equipment::inspectionUpdate($id, $status, $remark, false);
                }
            }

            logActivity(getCurrentUserId(), 'Inspection', "ตรวจนับครุภัณฑ์: {$updated} รายการ");
            $this->flash('success', 'บันทึกข้อมูลการตรวจนับเรียบร้อยแล้ว');
            $this->redirect(SITE_URL . '/equipment/inspection?room_id=' . $selectedRoom);
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }

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

    public function disposal()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $pageTitle = 'บริหารจัดการจำหน่ายครุภัณฑ์ออก';
        $viewPath = 'equipment/disposal';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['action'] ?? '';
            $eqId = $_POST['equipment_id'] ?? null;
            $returnTab = $_POST['tab'] ?? 'pending';

            if ($eqId) {
                switch ($action) {
                    case 'propose':
                        Equipment::updateStatus($eqId, 'pending_disposal');
                        logActivity(getCurrentUserId(), 'Propose Disposal', 'เสนอจำหน่ายครุภัณฑ์ ID: ' . $eqId);
                        break;
                    case 'dispose':
                        Equipment::dispose($eqId);
                        logActivity(getCurrentUserId(), 'Dispose', 'จำหน่ายครุภัณฑ์ ID: ' . $eqId);
                        break;
                    case 'restore':
                        Equipment::updateStatus($eqId, 'available');
                        logActivity(getCurrentUserId(), 'Restore Equipment', 'กู้คืนครุภัณฑ์ ID: ' . $eqId);
                        break;
                }
            }
            $this->redirect(SITE_URL . '/equipment/disposal?tab=' . urlencode($returnTab));
        }

        $tab = $_GET['tab'] ?? 'pending';

        $statusMap = [
            'pending' => 'pending_disposal',
            'broken' => 'broken',
            'disposed' => 'disposed',
        ];

        if (!array_key_exists($tab, $statusMap)) {
            $tab = 'pending';
        }
        $activeStatus = $statusMap[$tab];

        $perPageOptions = [10, 20, 50, 100];
        $perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions)
            ? (int) $_GET['per_page'] : 20;

        $counts = [];
        foreach ($statusMap as $key => $status) {
            $counts[$key] = EquipmentStats::countByStatus($status);
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $orderMap = ['pending' => 'code', 'broken' => 'code', 'disposed' => 'updated_at'];
        $orderDir = $tab === 'disposed' ? 'DESC' : 'ASC';

        $result = Equipment::getByStatus($activeStatus, $page, $perPage, $orderMap[$tab], $orderDir);

        $items = $result['equipment'];
        $pagination = $result['pagination'];

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function deleteImage($id)
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);
        $this->validateCsrf();

        $imageId = (int)($_POST['image_id'] ?? 0);
        if (!$imageId) {
            $this->flash('danger', 'ไม่พบรูปภาพ');
            $this->redirect(SITE_URL . '/equipment/edit/' . $id);
        }

        $path = Equipment::deleteImage($imageId, $id);
        if ($path) {
            $filepath = UPLOAD_PATH . $path;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            logActivity(getCurrentUserId(), 'Delete Equipment Image', 'ลบรูปครุภัณฑ์ ID: ' . $id . ', Image ID: ' . $imageId);
            $this->flash('success', 'ลบรูปภาพสำเร็จ');
        } else {
            $this->flash('danger', 'ไม่พบรูปภาพ');
        }

        $this->redirect(SITE_URL . '/equipment/edit/' . $id);
    }

    private function saveImages($equipmentId)
    {
        $errors = [];
        $typeLabels = ['purchase' => 'ภาพตอนซื้อ', 'current_condition' => 'ภาพสภาพปัจจุบัน'];
        // ดึง code มาทำ prefix ให้ชื่อไฟล์บอกได้ว่าเป็นของครุภัณฑ์ชิ้นไหน
        $eq = Equipment::find($equipmentId);
        $codeSlug = '';
        if ($eq && !empty($eq['code'])) {
            $codeSlug = preg_replace('/[^a-zA-Z0-9]+/', '-', $eq['code']);
            $codeSlug = trim($codeSlug, '-');
            $codeSlug = substr($codeSlug, 0, 20);
        }

        foreach (['purchase' => 'purchase_images', 'current_condition' => 'current_images'] as $type => $field) {
            if (empty($_FILES[$field]['name'][0])) continue;

            foreach ($_FILES[$field]['tmp_name'] as $key => $tmpName) {
                if ($_FILES[$field]['error'][$key] !== UPLOAD_ERR_OK) {
                    $errors[] = $typeLabels[$type] . ' รูปที่ ' . ($key + 1) . ': ' . uploadErrorMessage($_FILES[$field]['error'][$key]);
                    continue;
                }

                $file = [
                    'name' => $_FILES[$field]['name'][$key],
                    'type' => $_FILES[$field]['type'][$key],
                    'tmp_name' => $tmpName,
                    'size' => $_FILES[$field]['size'][$key],
                    'error' => $_FILES[$field]['error'][$key],
                ];

                // prefix จำง่าย: EQ{ID}_{code}_{type} เช่น EQ885_7440-001_purchase
                $prefix = 'EQ' . $equipmentId . ($codeSlug ? '_' . $codeSlug : '') . '_' . $type;
                $result = uploadImage($file, 'equipment', $prefix);
                if ($result['success']) {
                    Equipment::addImage($equipmentId, $result['path'], $type);
                } else {
                    $errors[] = $typeLabels[$type] . ' รูปที่ ' . ($key + 1) . ': ' . $result['error'];
                }
            }
        }
        return $errors;
    }
}
