<?php
/**
 * Depreciation Controller
 * ระบบค่าเสื่อมราคา — index (รายชิ้น), settings (เกณฑ์ admin),
 * report/export (สรุป+กราฟ+CSV), my/myExport (มุมมองอาจารย์)
 */
class DepreciationController extends Controller
{
    private const FILTER_KEYS = ['dept_id', 'category_id', 'status', 'year'];

    private function getFilters(): array
    {
        $filters = [];
        foreach (self::FILTER_KEYS as $key) {
            $filters[$key] = trim($_GET[$key] ?? '');
        }
        return $filters;
    }

    /**
     *  คำนวณค่าเสื่อมราคารายชิ้น (admin/staff)
     */
    public function index()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $filters = $this->getFilters();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPageOptions = [10, 20, 50, 100];
        $perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions)
            ? (int) $_GET['per_page'] : 20;

        $allRows = DepreciationReport::getEquipmentRows($filters);
        $pagination = Model::paginate(count($allRows), $page, $perPage);
        $rows = array_slice($allRows, $pagination['offset'], $pagination['per_page']);
        $totals = DepreciationReport::totals($allRows);

        $departments = Department::getAll();
        $categories = AssetCategory::getAll();
        $years = DepreciationReport::getDistinctYears();

        $pageTitle = 'คำนวณค่าเสื่อมราคาครุภัณฑ์';
        $viewPath = 'depreciation/index';
        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * 1.3.2.9-2: ตั้งค่าหมวดหมู่ + เกณฑ์อายุการใช้งาน/%ค่าเสื่อม (admin)
     */
    public function settings()
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['action'] ?? '';
            $userId = getCurrentUserId();

            if ($action === 'save_category') {
                $id = (int) ($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $remark = trim($_POST['remark'] ?? '');
                if ($name === '') {
                    $this->flash('danger', 'กรุณาระบุชื่อหมวดหมู่');
                } elseif ($id > 0) {
                    AssetCategory::updateCategory($id, $name, $remark);
                    logActivity($userId, 'Update Asset Category', 'แก้ไขหมวดหมู่ ID: ' . $id . ' -> ' . $name);
                    $this->flash('success', 'บันทึกหมวดหมู่สำเร็จ');
                } else {
                    AssetCategory::createCategory($name, $remark);
                    logActivity($userId, 'Add Asset Category', 'เพิ่มหมวดหมู่: ' . $name);
                    $this->flash('success', 'เพิ่มหมวดหมู่สำเร็จ');
                }
            } elseif ($action === 'save_setting') {
                $bulk = $_POST['setting'] ?? [];
                $saved = 0;
                foreach ($bulk as $categoryId => $cfg) {
                    $categoryId = (int) $categoryId;
                    $life = (int) ($cfg['useful_life_years'] ?? 0);
                    $rate = (float) ($cfg['dep_rate'] ?? 0);
                    $method = in_array($cfg['method'] ?? '', DepreciationSetting::METHODS, true)
                        ? $cfg['method'] : DepreciationSetting::METHODS[0];
                    if ($categoryId > 0 && $life > 0) {
                        DepreciationSetting::upsert($categoryId, $life, $rate, $method, $userId);
                        $saved++;
                    }
                }
                logActivity($userId, 'Update Depreciation Settings', 'บันทึกเกณฑ์ค่าเสื่อม ' . $saved . ' หมวดหมู่');
                $this->flash('success', 'บันทึกเกณฑ์ค่าเสื่อม ' . $saved . ' หมวดหมู่สำเร็จ');
            } elseif ($action === 'delete_category') {
                $id = (int) ($_POST['id'] ?? 0);
                AssetCategory::deleteCategory($id);
                logActivity($userId, 'Delete Asset Category', 'ลบหมวดหมู่ ID: ' . $id);
                $this->flash('success', 'ลบหมวดหมู่สำเร็จ (รายการที่ผูกไว้จะไม่ระบุหมวด)');
            }

            $this->redirect(SITE_URL . '/depreciation/settings');
        }

        $categories = AssetCategory::getAll();
        $itemCounts = [];
        foreach ($categories as $c) {
            $itemCounts[$c['id']] = AssetCategory::countItems((int) $c['id']);
        }
        $settingsByCat = [];
        foreach (DepreciationSetting::getAllWithCategory() as $s) {
            $settingsByCat[(int) $s['category_id']] = $s;
        }

        $pageTitle = 'ตั้งค่าเกณฑ์ค่าเสื่อมราคา';
        $viewPath = 'depreciation/settings';
        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * 1.3.2.10-1: รายงานสรุป + กราฟค่าเสื่อมรายปี (admin/staff)
     */
    public function report()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $filters = $this->getFilters();
        $rows = DepreciationReport::getEquipmentRows($filters);
        $byYear = DepreciationReport::summarizeByYear($rows);
        $byCategory = DepreciationReport::summarizeByCategory($rows);
        $totals = DepreciationReport::totals($rows);

        $pageTitle = 'รายงานค่าเสื่อมราคาครุภัณฑ์';
        $viewPath = 'depreciation/report';
        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * 1.3.2.10-1: Export CSV รายงานสรุปประจำปี (type=summary|detail)
     */
    public function export()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);
        $type = $_GET['type'] ?? 'detail';
        $rows = DepreciationReport::getEquipmentRows($this->getFilters());
        logActivity(getCurrentUserId(), 'Export Depreciation Report', 'type: ' . $type . ' (' . count($rows) . ' รายการ)');

        if ($type === 'summary') {
            $data = DepreciationReport::summarizeByYear($rows);
            $header = ['ปีงบประมาณ (พ.ศ.)', 'จำนวนชิ้น', 'ราคาต้นทุนรวม', 'ค่าเสื่อมรายปีรวม', 'ค่าเสื่อมสะสมรวม', 'มูลค่าคงเหลือสุทธิรวม'];
            $filename = 'depreciation_summary_' . date('Y-m-d') . '.csv';
            $lineFn = fn($r) => [
                $r['year'], $r['count'], number_format($r['total_cost'], 2),
                number_format($r['total_annual'], 2), number_format($r['total_accumulated'], 2),
                number_format($r['total_nbv'], 2),
            ];
        } else {
            $data = $rows;
            $header = ['รหัส', 'รายการ', 'หมวดหมู่', 'ชุด', 'ปีจัดซื้อ (พ.ศ.)', 'สาขา', 'ห้อง', 'ผู้ถือครอง', 'สถานะ', 'ราคาต้นทุน', 'อายุการใช้งาน (ปี)', '% ค่าเสื่อม', 'วิธีคิด', 'ค่าเสื่อม/ปี', 'ผ่านมา (ปี)', 'ค่าเสื่อมสะสม', 'มูลค่าคงเหลือ', 'หมายเหตุ'];
            $filename = 'depreciation_detail_' . date('Y-m-d') . '.csv';
            $lineFn = function ($r) {
                return [
                    $r['code'], $r['item_name'], $r['category_name'] ?? '-', $r['set_name'],
                    $r['set_year'], $r['dept_name'] ?? '-', $r['room_name'] ?? '-',
                    trim(($r['holder_firstname'] ?? '') . ' ' . ($r['holder_lastname'] ?? '')),
                    translateEquipmentStatus($r['status']), number_format((float) $r['price'], 2),
                    $r['useful_life_years'] ?? '-',
                    $r['dep_rate'] !== null ? rtrim(rtrim(number_format((float) $r['dep_rate'], 2), '0'), '.') . '%' : '-',
                    $r['method'] === 'declining_balance' ? 'ลดยอดคงเหลือ' : 'เส้นตรง',
                    $r['dep_ok'] ? number_format($r['annual_dep'], 2) : '-',
                    $r['dep_ok'] ? $r['years_elapsed'] : '-',
                    $r['dep_ok'] ? number_format($r['accumulated'], 2) : '-',
                    $r['dep_ok'] ? number_format($r['nbv'], 2) : '-',
                    $r['dep_ok'] ? '' : translateDepReason($r['dep_reason']),
                ];
            };
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM สำหรับ Excel
        $output = fopen('php://output', 'w');
        fputcsv($output, $header);
        foreach ($data as $row) {
            fputcsv($output, $lineFn($row));
        }
        fclose($output);
        exit;
    }
}
