<?php
/**
 * Item Controller
 * จัดการรายการครุภัณฑ์ — CRUD
 */
class ItemController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $pageTitle = 'จัดการรายการครุภัณฑ์';
        $viewPath = 'crud/items';
        $setFilter = $_GET['set_id'] ?? null;
        $deptFilter = $_GET['dept_id'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPageOptions = [10, 20, 50, 100];
        $perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions)
            ? (int) $_GET['per_page']
            : 20;

        $result = Item::getFiltered($setFilter, $deptFilter, $page, $perPage);
        $departments = Department::getAll();
        $allSets = SetModel::getAllWithDept();
        $assetCategories = AssetCategory::getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $action = $_POST['action'] ?? '';
            $id = $_POST['id'] ?? null;

            if ($action === 'save') {
                $data = [
                    'set_id' => $_POST['set_id'] ?? null,
                    'category_id' => ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : null,
                    'name' => trim($_POST['name'] ?? ''),
                    'brand' => trim($_POST['brand'] ?? ''),
                    'model' => trim($_POST['model'] ?? ''),
                    'qty' => (int)($_POST['qty'] ?? 1),
                    'unit' => trim($_POST['unit'] ?? ''),
                    'price' => (float)($_POST['price'] ?? 0),
                    'price_remark' => trim($_POST['price_remark'] ?? ''),
                    'remark' => trim($_POST['remark'] ?? ''),
                ];

                if (empty($data['set_id']) || empty($data['name'])) {
                    $this->flash('danger', 'กรุณากรอกข้อมูลที่จำเป็น');
                    $this->redirect(SITE_URL . '/items');
                }

                if ($data['price'] > 0 && empty($data['price_remark'])) {
                    $this->flash('danger', 'กรุณาระบุหมายเหตุราคา เนื่องจากมีการใส่ราคาทั้งรายการของครุภัณฑ์');
                    $this->redirect(SITE_URL . '/items');
                }

                if (Item::parentSetHasPrice($data['set_id'])) {
                    $data['price'] = 0;
                }

                if (!empty($id) && $id !== '0') {
                    Item::update($id, $data);
                    logActivity(getCurrentUserId(), 'แก้ไขรายการครุภัณฑ์', 'แก้ไขรายการ: ' . $data['name']);
                } else {
                    Item::create($data);
                    logActivity(getCurrentUserId(), 'เพิ่มรายการครุภัณฑ์', 'เพิ่มรายการ: ' . $data['name']);
                }
                $this->flash('success', 'บันทึกสำเร็จ');
                $this->redirect(SITE_URL . '/items');
            }
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function delete($id)
    {
        $this->requireLogin();
        $this->authorize(['admin']);
        $this->validateCsrf();

        if (Item::childEquipmentCount($id) > 0) {
            $this->flash('danger', 'ไม่สามารถลบได้ มีครุภัณฑ์ที่เกี่ยวข้อง');
            $this->redirect(SITE_URL . '/items');
        }

        Item::delete($id);
        logActivity(getCurrentUserId(), 'ลบรายการครุภัณฑ์', 'ลบรายการ รหัส: ' . $id);
        $this->flash('success', 'ลบรายการสำเร็จ');
        $this->redirect(SITE_URL . '/items');
    }
}
