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
}
