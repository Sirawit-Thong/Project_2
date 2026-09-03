<?php
/**
 * CLI Test (ชั่วคราว): ตรวจ helper ภาษาไทยกลาง
 * รัน: C:\xampp\php\php.exe tests\thai_date_audit_test.php
 * ไฟล์นี้จะถูกลบใน Task 7 หลังตรวจซ้ำเสร็จ
 */
require_once __DIR__ . '/../app/Helpers/functions.php';

$failures = 0;
function check(string $name, bool $cond): void
{
    global $failures;
    if ($cond) {
        echo "PASS  {$name}\n";
    } else {
        $failures++;
        echo "FAIL  {$name}\n";
    }
}

// --- helper กลาง 6 ฟังก์ชันต้องมี ---
$required = ['thaiMonthShort', 'thaiMonthFull', 'formatDateThaiFull', 'formatDateTimeThaiFull', 'chartMonthLabelThai', 'chartMonthYearThai'];
foreach ($required as $fn) {
    check("function_exists({$fn})", function_exists($fn));
}

// --- พฤติกรรมวันที่ (ถ้า helper มี) ---
if (function_exists('formatDateThaiFull')) {
    check("formatDateThaiFull('2025-08-12') === '12 สิงหาคม 2568'", formatDateThaiFull('2025-08-12') === '12 สิงหาคม 2568');
    check("formatDateThaiFull(null) === '-'", formatDateThaiFull(null) === '-');
}
if (function_exists('formatDateTimeThaiFull')) {
    check("formatDateTimeThaiFull('2025-08-12 14:30:00') === '12 สิงหาคม 2568 14:30 น.'", formatDateTimeThaiFull('2025-08-12 14:30:00') === '12 สิงหาคม 2568 14:30 น.');
}
if (function_exists('chartMonthLabelThai')) {
    check("chartMonthLabelThai(2025-08) === 'ส.ค. 68'", chartMonthLabelThai(new DateTime('2025-08-15')) === 'ส.ค. 68');
}
if (function_exists('chartMonthYearThai')) {
    check("chartMonthYearThai(2025-08) === 'ส.ค. 2568'", chartMonthYearThai(new DateTime('2025-08-15')) === 'ส.ค. 2568');
}
// --- ของเดิมต้องไม่พัง ---
if (function_exists('formatDateThai')) {
    check("formatDateThai('2025-08-12') === '12 ส.ค. 2568'", formatDateThai('2025-08-12') === '12 ส.ค. 2568');
}

exit($failures > 0 ? 1 : 0);
