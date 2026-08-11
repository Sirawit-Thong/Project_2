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

        $result = Equipment::getFiltered(
            compact('search', 'status', 'room', 'item', 'dept', 'set'),
            $page
        );

        $departments = Department::getAll();
        $rooms = Room::getAll();

        $pageTitle = $role === 'teacher' ? 'ตรวจสอบครุภัณฑ์' : 'รายการครุภัณฑ์';
        $viewPath = 'equipment/index';

        require __DIR__ . '/../Views/layouts/main.php';
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $data = $this->inputs([
                'code' => '', 'item_id' => '', 'room_id' => null,
                'status' => 'available', 'purchase_date' => null,
                'check_date' => null, 'price' => null, 'price_remark' => null,
                'holder_id' => null, 'remark' => null,
            ]);

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

            if (empty($errors)) {
                // Force price to 0 if parent item or set has price
                $parentPrices = Equipment::getParentPrices($data['item_id']);
                if ($parentPrices && ($parentPrices['item_price'] > 0 || $parentPrices['set_price'] > 0)) {
                    $data['price'] = 0;
                }

                $id = Equipment::create($data);
                $this->saveImages($id);
                logActivity(getCurrentUserId(), 'Add Equipment', 'เพิ่มครุภัณฑ์: ' . $data['code']);
                $this->flash('success', 'เพิ่มครุภัณฑ์สำเร็จ');
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

            $errors = [];
            if (empty($data['code'])) $errors[] = 'กรุณากรอกรหัสครุภัณฑ์';
            if (empty($data['item_id'])) $errors[] = 'กรุณาเลือกรายการครุภัณฑ์';
            if (!empty($data['code']) && Equipment::isCodeTaken($data['code'], $id)) {
                $errors[] = 'รหัสครุภัณฑ์นี้มีในระบบแล้ว';
            }

            if (empty($errors)) {
                // Force price to 0 if parent item or set has price
                $parentPrices = Equipment::getParentPrices($data['item_id']);
                if ($parentPrices && ($parentPrices['item_price'] > 0 || $parentPrices['set_price'] > 0)) {
                    $data['price'] = 0;
                }

                Equipment::update($id, $data);
                $this->saveImages($id);
                logActivity(getCurrentUserId(), 'Edit Equipment', 'แก้ไขครุภัณฑ์: ' . $data['code']);
                $this->flash('success', 'แก้ไขครุภัณฑ์สำเร็จ');
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
        $pageTitle = 'รายละเอียดครุภัณฑ์';
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
        $pageTitle = 'เพิ่มครุภัณฑ์จำนวนมาก';
        $viewPath = 'equipment/bulk_add';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $codes = $_POST['codes'] ?? [];
            $itemId = $_POST['item_id'] ?? null;
            $roomId = $_POST['room_id'] ?? null;
            $holderId = $_POST['holder_id'] ?? null;
            $status = $_POST['status'] ?? 'available';
            $remark = $_POST['remark'] ?? null;

            $errors = [];
            $validCodes = array_values(array_unique(array_map('trim', $codes)));

            if (empty($itemId)) $errors[] = 'กรุณาเลือกรายการครุภัณฑ์';
            if (empty($validCodes)) $errors[] = 'กรุณากรอกรหัสครุภัณฑ์อย่างน้อย 1 รายการ';

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
        $pageTitle = 'ตรวจนับประจำปี';
        $viewPath = 'equipment/inspection';
        $selectedRoom = $_GET['room_id'] ?? null;
        $equipment = [];

        if ($selectedRoom) {
            $equipment = Equipment::getByRoomForInspection($selectedRoom);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $items = $_POST['items'] ?? [];
            $updated = 0;

            foreach ($items as $eqId => $data) {
                $status = $data['status'] ?? null;
                $remark = $data['remark'] ?? null;
                $checked = isset($data['checked']);

                if ($checked && $status) {
                    Equipment::inspectionUpdate($eqId, $status, $remark, true);
                    $updated++;
                }
            }

            logActivity(getCurrentUserId(), 'Inspection', "ตรวจนับครุภัณฑ์: {$updated} รายการ");
            $this->flash('success', "บันทึกผลตรวจนับสำเร็จ {$updated} รายการ");
            $this->redirect(SITE_URL . '/equipment/inspection?room_id=' . $selectedRoom);
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function disposal()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $pageTitle = 'จำหน่ายครุภัณฑ์';
        $viewPath = 'equipment/disposal';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['action'] ?? '';
            $eqId = $_POST['equipment_id'] ?? null;

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
            $this->redirect(SITE_URL . '/equipment/disposal');
        }

        $pendingDisposal = Equipment::getDisposalList();
        $broken = Equipment::getByStatus('broken', 1, 50);
        $disposed = Equipment::getByStatus('disposed', 1, 50);

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
        foreach (['purchase' => 'purchase_images', 'current_condition' => 'current_images'] as $type => $field) {
            if (empty($_FILES[$field]['name'][0])) continue;

            foreach ($_FILES[$field]['tmp_name'] as $key => $tmpName) {
                if ($_FILES[$field]['error'][$key] !== UPLOAD_ERR_OK) continue;

                $file = [
                    'name' => $_FILES[$field]['name'][$key],
                    'type' => $_FILES[$field]['type'][$key],
                    'tmp_name' => $tmpName,
                    'size' => $_FILES[$field]['size'][$key],
                ];

                $result = uploadImage($file, 'equipment');
                if ($result['success']) {
                    Equipment::addImage($equipmentId, $result['path'], $type);
                }
            }
        }
    }
}
