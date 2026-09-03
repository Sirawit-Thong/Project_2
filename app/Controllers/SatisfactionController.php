<?php
/**
 * Satisfaction Controller
 * ระบบความพึงพอใจ — submit (ผู้แจ้งซ่อมให้คะแนน), dashboard/export (admin/staff)
 */
class SatisfactionController extends Controller
{
    /**
     * 1.3.2.11-1: ผู้แจ้งซ่อม (teacher/student) ให้คะแนนหลังสถานะเป็น completed
     * หมายเหตุ: param ต้องชื่อ $id ให้ตรงกับ {id} ใน route — Router ส่งค่าแบบ named arguments (PHP 8)
     */
    public function submit($id)
    {
        $this->requireLogin();
        $this->authorize(['teacher', 'student']);
        $this->validateCsrf();

        $repairId = (int) $id;
        $backUrl = SITE_URL . '/repairs/' . $repairId;

        $repair = Repair::find($repairId);
        if (!$repair || $repair['status'] !== 'completed') {
            ErrorHandler::page404();
        }
        if ((int) $repair['user_id'] !== (int) getCurrentUserId()) {
            $this->flash('danger', 'ท่านไม่มีสิทธิ์ประเมินใบแจ้งซ่อมนี้');
            $this->redirect($backUrl);
        }
        if (Satisfaction::getByRepairId($repairId)) {
            $this->flash('warning', 'ท่านได้ประเมินใบแจ้งซ่อมนี้ไปแล้ว');
            $this->redirect($backUrl);
        }

        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if (mb_strlen($comment) > 1000) {
            $comment = mb_substr($comment, 0, 1000);
        }

        if ($rating < 1 || $rating > 5) {
            $this->flash('danger', 'กรุณาเลือกคะแนนความพึงพอใจ 1-5');
            $this->redirect($backUrl);
        }

        if (Satisfaction::createSurvey($repairId, getCurrentUserId(), $rating, $comment)) {
            logActivity(getCurrentUserId(), 'ประเมินความพึงพอใจ', 'ประเมินใบซ่อม รหัส: ' . $repairId . ' คะแนน: ' . $rating);
            $this->flash('success', 'ขอบคุณสำหรับการประเมิน ข้อมูลของท่านช่วยปรับปรุงคุณภาพการบริการบำรุงรักษาครุภัณฑ์');
        } else {
            $this->flash('warning', 'ท่านได้ประเมินใบแจ้งซ่อมนี้ไปแล้ว');
        }
        $this->redirect($backUrl);
    }

    /**
     * 1.3.2.11-2: Dashboard สรุปคะแนนเฉลี่ยรายเดือน (admin/staff)
     */
    public function dashboard()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $monthly = Satisfaction::getMonthlyStats(12);
        $overall = Satisfaction::getOverall();
        $responseRate = Satisfaction::responseRate();
        $completed = Satisfaction::completedRepairCount();
        $recent = Satisfaction::getRecent(20);

        $pageTitle = 'สรุปความพึงพอใจงานซ่อมบำรุง';
        $viewPath = 'satisfaction/dashboard';
        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function export()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $rows = Satisfaction::getAllForExport();
        logActivity(getCurrentUserId(), 'ส่งออกผลประเมินความพึงพอใจ', count($rows) . ' รายการ');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="satisfaction_' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ลำดับ', 'ใบซ่อม #', 'ครุภัณฑ์', 'รายการ', 'ผู้ประเมิน', 'บทบาท', 'คะแนน', 'ความคิดเห็น', 'วันที่ประเมิน']);
        foreach ($rows as $r) {
            fputcsv($output, array_map('csvSafe', [
                $r['id'], $r['repair_id'], $r['eq_code'], $r['item_name'],
                trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? '')),
                translateRole($r['role'] ?? ''),
                $r['rating'], $r['comment'], formatDateTimeThai($r['created_at']),
            ]));
        }
        fclose($output);
        exit;
    }
}
