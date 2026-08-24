<?php
/**
 * Satisfaction Controller
 * ระบบความพึงพอใจ — submit (ผู้แจ้งซ่อมให้คะแนน), dashboard/export (admin/staff)
 */
class SatisfactionController extends Controller
{
    /**
     * 1.3.2.11-1: ผู้แจ้งซ่อม (teacher/student) ให้คะแนนหลังสถานะเป็น completed
     */
    public function submit($repairId)
    {
        $this->requireLogin();
        $this->authorize(['teacher', 'student']);
        $this->validateCsrf();

        $repairId = (int) $repairId;
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
            logActivity(getCurrentUserId(), 'Submit Satisfaction Survey', 'ประเมินใบซ่อม ID: ' . $repairId . ' คะแนน: ' . $rating);
            $this->flash('success', 'ขอบคุณสำหรับการประเมิน ข้อมูลของท่านช่วยปรับปรุงคุณภาพการบริการบำรุงรักษาครุภัณฑ์');
        } else {
            $this->flash('warning', 'ท่านได้ประเมินใบแจ้งซ่อมนี้ไปแล้ว');
        }
        $this->redirect($backUrl);
    }
}
