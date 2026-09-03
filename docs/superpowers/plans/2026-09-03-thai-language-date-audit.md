# Thai Language + Date + Graph Audit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers-subagent-driven-development (recommended) or superpowers-executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** ทำให้ข้อความทั้งระบบเป็นภาษาไทยราชการทั้งหมด และวันที่/กราฟเป็น พ.ศ. ภาษาไทยทั้งหมด โดยสแกน-ซ่อม-ตรวจซ้ำจนเหลือ 0 จุด

**Architecture:** ขยาย Helper กลางใน `app/Helpers/functions.php` (เพิ่มวันที่แบบเต็ม + label กราฟไทย) แล้วไล่ซ่อม 7 หมวดจาก Models → Views กราฟ → Views ทั่วไป → Flash/Export/Backup → Layout โดยทุกจุดเรียก Helper กลาง ห้าม hardcode เดือน/ปี

**Tech Stack:** PHP 8 (XAMPP), Chart.js (views), PowerShell + ripgrep (`rg`) สำหรับสแกน, CLI test `C:\xampp\php\php.exe tests\depreciation_test.php`

---

## File Structure (แตะเฉพาะไฟล์เหล่านี้)

- ขยาย: `app/Helpers/functions.php` — เพิ่ม `thaiMonthShort()`, `thaiMonthFull()`, `formatDateThaiFull()`, `formatDateTimeThaiFull()`, `chartMonthLabelThai()`, `chartMonthYearThai()`
- แก้ Models: `app/Models/Repair.php:74-103` (`getMonthlyStats`), `app/Models/Satisfaction.php:39-64` (`getMonthlyStats`)
- แก้ Views กราฟ 5 หน้า: `app/Views/admin/reports.php`, `app/Views/dashboard/admin.php`, `app/Views/satisfaction/dashboard.php`, `app/Views/depreciation/report.php`, `app/Views/dashboard/teacher_report.php`
- แก้ Views วันที่ดิบ: `app/Views/equipment/inspection.php:121,148`, `app/Views/crud/sets.php:172`, ตรวจ `app/Views/equipment/form.php:20`, `app/Views/equipment/bulk_add.php:4`, `app/Views/equipment/my_equipment.php:136`
- ตรวจ Flash/Validation: `app/Controllers/*.php` (`setFlash` ทุกตัว), `app/Helpers/functions.php:255-273` (`uploadErrorMessage`), `app/Helpers/functions.php:196-249` (pagination — ไทยแล้ว ตรวจซ้ำอย่างเดียว)
- ตรวจ Export/Backup: `app/Controllers/AdminController.php:146,163,299,379,474,499,522`, `app/Controllers/DepreciationController.php:152-153,161-162,218,221`, `app/Controllers/SatisfactionController.php:82`, `app/Controllers/DashboardController.php:144`, `app/Views/admin/backup.php:71-79`, `app/Views/admin/logs.php`
- ตรวจ Layout: `app/Views/layouts/main.php`, `includes/header.php`, `includes/sidebar.php`, `includes/footer.php`, `app/Views/auth/login.php`, `app/Views/auth/register_*.php`
- ห้ามแตะ: `config/`, `database*.sql`, `uploads/`, `.env`, library Chart.js/Bootstrap เอง

---

### Task 1: Helper กลางวันที่แบบเต็ม + label กราฟไทย

**Files:**
- Modify: `app/Helpers/functions.php:137-167` (ต่อท้ายบล็อก Date Formatting)

- [ ] **Step 1: เขียนสคริปต์ตรวจ helper ว่ายังขาดฟังก์ชันใหม่ (ต้อง FAIL ก่อน)**

```php
<?php
// tests/thai_date_audit_test.php — รันชั่วคราวเพื่อยืนยันว่ายังขาด helper
require_once __DIR__ . '/../app/Helpers/functions.php';
$checks = ['thaiMonthShort','thaiMonthFull','formatDateThaiFull','formatDateTimeThaiFull','chartMonthLabelThai','chartMonthYearThai'];
$missing = [];
foreach ($checks as $fn) { if (!function_exists($fn)) { $missing[] = $fn; } }
if ($missing) { echo "MISSING: " . implode(',', $missing) . "\n"; exit(1); }
echo "ALL HELPERS PRESENT\n";
```

- [ ] **Step 2: รันสคริปต์ ยืนยันว่า FAIL (ยังขาด 6 ฟังก์ชัน)**

Run: `C:\xampp\php\php.exe tests\thai_date_audit_test.php`
Expected: `MISSING: thaiMonthShort,thaiMonthFull,formatDateThaiFull,formatDateTimeThaiFull,chartMonthLabelThai,chartMonthYearThai` + exit 1

- [ ] **Step 3: เพิ่ม helper กลาง 6 ฟังก์ชัน ต่อท้าย `formatDateTimeThai()`**

```php
function thaiMonthShort(): array
{
    return [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
}

function thaiMonthFull(): array
{
    return [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
}

function formatDateThaiFull($date)
{
    if (!$date) return '-';
    $timestamp = strtotime($date);
    $months = thaiMonthFull();
    $day = date('j', $timestamp);
    $month = $months[(int) date('n', $timestamp)];
    $year = date('Y', $timestamp) + 543;
    return "{$day} {$month} {$year}";
}

function formatDateTimeThaiFull($datetime)
{
    if (!$datetime) return '-';
    $date = formatDateThaiFull($datetime);
    $time = date('H:i', strtotime($datetime));
    return "{$date} {$time} น.";
}

function chartMonthLabelThai(DateTime $date): string
{
    $months = thaiMonthShort();
    $month = $months[(int) $date->format('n')];
    $yearShort = substr((string) ((int) $date->format('Y') + 543), -2);
    return "{$month} {$yearShort}";
}

function chartMonthYearThai(DateTime $date): string
{
    $months = thaiMonthShort();
    $month = $months[(int) $date->format('n')];
    $year = (int) $date->format('Y') + 543;
    return "{$month} {$year}";
}
```

- [ ] **Step 4: รันสคริปต์ตรวจ + syntax check + regression test เดิม**

Run: `C:\xampp\php\php.exe tests\thai_date_audit_test.php`
Expected: `ALL HELPERS PRESENT`

Run: `C:\xampp\php\php.exe -l app\Helpers\functions.php`
Expected: `No syntax errors detected`

Run: `C:\xampp\php\php.exe tests\depreciation_test.php`
Expected: ทุกบรรทัด `PASS`, ไม่มี `FAIL`

- [ ] **Step 5: Commit**

```bash
git add -f app/Helpers/functions.php tests/thai_date_audit_test.php
git commit -m "feat(thai): เพิ่ม helper วันที่แบบเต็มและ label กราฟไทยกลาง"
```

---

### Task 2: Models กราฟเลิกคืนเดือนอังกฤษ

**Files:**
- Modify: `app/Models/Repair.php:88-100`
- Modify: `app/Models/Satisfaction.php:51-63`
- Test: `tests/thai_date_audit_test.php` (ขยายเคสเดิมในไฟล์ชั่วคราวนี้)

- [ ] **Step 1: เพิ่มเคสตรวจ label กราฟลงสคริปต์ชั่วคราว (ต้อง FAIL ก่อนแก้ Models)**

```php
// ต่อท้าย tests/thai_date_audit_test.php ก่อนบรรทัด ALL HELPERS PRESENT
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/Repair.php';
require_once __DIR__ . '/../app/Models/Satisfaction.php';
foreach (Repair::getMonthlyStats(3) as $s) {
    if (preg_match('/Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec/', $s['label'])) {
        echo "FAIL EN label in Repair: {$s['label']}\n"; exit(1);
    }
}
foreach (Satisfaction::getMonthlyStats(3) as $s) {
    if (preg_match('/Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec/', $s['label'])) {
        echo "FAIL EN label in Satisfaction: {$s['label']}\n"; exit(1);
    }
}
```

- [ ] **Step 2: รัน ยืนยัน FAIL (ยังมี Jan/Feb/...)**

Run: `C:\xampp\php\php.exe tests\thai_date_audit_test.php`
Expected: `FAIL EN label in Repair: ...` (เช่น `Sep 2026`) + exit 1 — ต้องต่อ DB ได้ ถ้าไม่มี DB ให้ข้ามไปตรวจด้วย `rg` แทนตาม Step 4

- [ ] **Step 3: แก้ 2 ไฟล์ — แทน `$date->format('M Y')` ด้วย helper กลาง**

ใน `app/Models/Repair.php` แทนบรรทัด `$label = $date->format('M Y');` ด้วย:

```php
$label = chartMonthYearThai($date);
```

ใน `app/Models/Satisfaction.php` แทนบรรทัด `'label' => $date->format('M Y'),` ด้วย:

```php
'label' => chartMonthYearThai($date),
```

และเพิ่ม `require_once` helper ถ้าไฟล์นั้นยังไม่โหลด `functions.php` ผ่าน `app/init.php` (ตรวจก่อน ถ้าโหลดแล้วไม่ต้องเพิ่ม)

- [ ] **Step 4: สแกนยืนยันเหลือ 0 + syntax check**

Run: `rg -n "format\('M Y'\)" app/Models`
Expected: ไม่พบผลลัพธ์ (empty)

Run: `C:\xampp\php\php.exe -l app\Models\Repair.php; C:\xampp\php\php.exe -l app\Models\Satisfaction.php`
Expected: `No syntax errors detected` ทั้งสองไฟล์

- [ ] **Step 5: Commit**

```bash
git add app/Models/Repair.php app/Models/Satisfaction.php
git commit -m "fix(thai): กราฟรายเดือนใช้เดือนไทย พ.ศ. แทน M Y อังกฤษ"
```

---

### Task 3: Views กราฟ 5 หน้าเป็นไทยทั้งหมด

**Files:**
- Modify: `app/Views/admin/reports.php:231,233,256,280`
- Modify: `app/Views/dashboard/admin.php:329,331,354`
- Modify: `app/Views/satisfaction/dashboard.php:81-85,93`
- Modify: `app/Views/depreciation/report.php:109-120` (chartAnnual/chartNbv)
- Modify: `app/Views/dashboard/teacher_report.php:165-190`

- [ ] **Step 1: สแกนจุดอังกฤษในกราฟ (เก็บ baseline ก่อนแก้)**

Run: `rg -n "label:|title:|text:|tooltip|จำนวน \(ครั้ง\)|M Y|months" app/Views/admin/reports.php app/Views/dashboard/admin.php app/Views/satisfaction/dashboard.php app/Views/depreciation/report.php app/Views/dashboard/teacher_report.php`
Expected: เห็นรายการ label/tooltip ปัจจุบัน (บางจุดไทยแล้ว เช่น พร้อมใช้งาน/ส่งซ่อม, บางจุดต้องแปล เช่น tooltip callback)

- [ ] **Step 2: แก้ tooltip callback + หน่วยให้ไทยครบ 5 หน้า**

ตัวอย่าง `app/Views/admin/reports.php:256` และ `app/Views/dashboard/admin.php:354` ถ้ามี `label: function(ctx) { ... }` ที่คืนข้อความอังกฤษ ให้แปลเป็นไทย เช่น:

```js
label: function(ctx) {
    return ' ' + ctx.parsed.y + ' ครั้ง';
}
```

และตรวจหัวกราฟ/แกนทุกหน้า: สถิติการแจ้งซ่อมย้อนหลัง 12 เดือน, สัดส่วนสถานะครุภัณฑ์, คะแนนความพึงพอใจเฉลี่ยรายเดือน, ค่าเสื่อมรายปี แยกตามปีจัดซื้อ (บาท), มูลค่าสะสม vs มูลค่าคงเหลือสุทธิ (บาท), สถานะครุภัณฑ์, จำนวนครุภัณฑ์ตามห้องปฏิบัติการ — ถ้าเจอคำอังกฤษให้แปลตามพจนานุกรม spec

- [ ] **Step 3: เปิดกราฟจริงตรวจเดือนไทย (manual verify หน้าเว็บ)**

เปิด: `/admin/reports`, `/dashboard/admin`, `/satisfaction/dashboard`, `/depreciation/report`, `/dashboard/teacher_report` — labels แกน X ต้องเป็น `ม.ค. 2568` ไม่มี `Jan/Feb`, tooltip มีหน่วยไทย (ครั้ง/บาท/คะแนน)

- [ ] **Step 4: Commit**

```bash
git add app/Views/admin/reports.php app/Views/dashboard/admin.php app/Views/satisfaction/dashboard.php app/Views/depreciation/report.php app/Views/dashboard/teacher_report.php
git commit -m "fix(thai): กราฟ 5 หน้า tooltip หน่วย หัวกราฟเป็นไทยทั้งหมด"
```

---

### Task 4: Views วันที่ดิบ + Title/Breadcrumb/Layout

**Files:**
- Modify: `app/Views/equipment/inspection.php:148` (จุด `d/m/Y`)
- Modify: `app/Views/crud/sets.php:172` (จุด `date('Y') + 543`)
- Check: `app/Views/equipment/form.php:20`, `app/Views/equipment/bulk_add.php:4`, `app/Views/equipment/my_equipment.php:136`, `app/Views/equipment/detail.php`, `app/Views/repair/*.php`, `app/Views/layouts/main.php`, `includes/header.php`, `includes/sidebar.php`, `includes/footer.php`

- [ ] **Step 1: สแกนวันที่ดิบที่แสดงผล**

Run: `rg -n "d/m/Y|d-m-Y|Y-m-d H:i:s|date\('Y'\)" app/Views includes`
Expected: เจอ `inspection.php:148` (`d/m/Y`), `sets.php:172` (`date('Y') + 543`) และจุดอื่น (ถ้ามี)

- [ ] **Step 2: แก้จุดแสดงผลด้วย helper กลาง**

`inspection.php:148` แทน:

```php
<?= date('d/m/Y', strtotime($item['check_date'])) ?>
```

ด้วย:

```php
<?= formatDateThai($item['check_date']) ?>
```

`sets.php:172` คงค่า option ปี พ.ศ. แต่ย้ายให้ชัด (กันลืม +543 ในอนาคต) — ถ้าเดิมคือ `value="<?= date('Y') + 543 ?>"` ให้คงผลลัพธ์แต่คอมเมนต์กำกับ `<!-- ปี พ.ศ. ปัจจุบัน -->` หรือเรียก helper ปี พ.ศ. ถ้ามี

`form.php:20` / `bulk_add.php:4` (`$today = date('Y-m-d')` ใช้เป็น `max` ของ input) — คงไว้ได้เพราะเป็นค่าภายใน แต่ข้อความกำกับใกล้ input ต้องไทย

- [ ] **Step 3: ไล่ Title/Breadcrumb ทุกหน้า — อังกฤษเหลือ 0**

Run: `rg -n "<title>|breadcrumb|Dashboard|Login|Register|Profile|Status|Search" app/Views/layouts/main.php app/Views/auth/login.php includes/header.php includes/sidebar.php`
Expected: ตรวจทีละจุด แปลที่เหลือเป็นไทย (แดชบอร์ด/ภาพรวม, เข้าสู่ระบบ, ลงทะเบียน, สถานะ, ค้นหา) — `aria-label="breadcrumb"` คงได้ (มาตรฐานเทคนิค)

- [ ] **Step 4: syntax check + Commit**

Run: `C:\xampp\php\php.exe -l app\Views\equipment\inspection.php`
Expected: `No syntax errors detected` (ไฟล์ view ตรวจเฉพาะไฟล์ที่แก้ด้วย `php -l`)

```bash
git add app/Views/equipment/inspection.php app/Views/crud/sets.php app/Views/layouts/main.php includes/header.php includes/sidebar.php includes/footer.php
git commit -m "fix(thai): วันที่แสดงผลใช้ helper พ.ศ. ไทย Title breadcrumb ไทยทั้งหมด"
```

---

### Task 5: Flash/Validation/Confirm/Pagination

**Files:**
- Check/Modify: `app/Controllers/*.php` (ทุก `setFlash`), `app/Helpers/functions.php:255-273`, `app/Views/*` (modal `confirm`, `aria-label`)

- [ ] **Step 1: สแกนข้อความอังกฤษใน flash/confirm**

Run: `rg -n "setFlash|confirm\(|alert\(|required|invalid|success|error|failed" app/Controllers app/Views | Select-Object -First 60`
Expected: ได้รายการข้อความ flash/confirm ทั้งหมดมาตรวจทีละบรรทัด

- [ ] **Step 2: แปลที่เหลือเป็นราชการ + เติม ` น.` ให้เวลาทุก flash ที่มีเวลา**

กฎ: ใช้คำมาตรฐาน (บันทึกสำเร็จ, ดำเนินการสำเร็จ, ไม่พบข้อมูล, กรุณากรอก..., ยืนยันการลบ, ยกเลิก) — เวลาใช้ `formatDateTimeThai()` ต่อท้าย เช่น `บันทึกสำเร็จเมื่อ 12 ส.ค. 2568 14:30 น.`

- [ ] **Step 3: ตรวจ pagination/upload (ควรไทยแล้ว — verify อย่างเดียว)**

เปิดหน้าลิสต์ยาว 1 หน้า: ต้องเห็น ก่อนหน้า/ถัดไป/หน้า x จาก y (ทั้งหมด N รายการ) และข้อความอัปโหลด (ขนาดไฟล์เกิน 5MB, รูปแบบไฟล์ไม่ถูกต้อง...) เป็นไทย

- [ ] **Step 4: Commit**

```bash
git add app/Controllers app/Helpers/functions.php app/Views
git commit -m "fix(thai): flash validation confirm เป็นภาษาราชการไทยทั้งหมด"
```

---

### Task 6: Export CSV/Excel + Backup + ชื่อไฟล์

**Files:**
- Modify: `app/Controllers/AdminController.php:146,163,299,379,474,499,522`, `app/Controllers/DepreciationController.php:152-153,161-162,218,221`, `app/Controllers/SatisfactionController.php:82`, `app/Controllers/DashboardController.php:144`, `app/Views/admin/backup.php:71-79`

- [ ] **Step 1: สแกน header export + backup ที่เป็นอังกฤษ**

Run: `rg -n "fputcsv|header\(|Content-Disposition|Backup completed|-- Date:|filename=" app/Controllers/AdminController.php app/Controllers/DepreciationController.php app/Controllers/SatisfactionController.php app/Controllers/DashboardController.php`
Expected: เห็น header CSV ปัจจุบัน (ส่วนใหญ่ไทยแล้ว เช่น ปีงบประมาณ (พ.ศ.), รหัส, รายการ — ตรวจที่ยังอังกฤษ)

- [ ] **Step 2: แปล header/เนื้อหาที่เหลือ + Backup header เป็นไทย**

`-- Date:` → `-- วันที่สำรองข้อมูล (พ.ศ.):` + วันที่ `formatDateTimeThai()`, `-- Backup completed:` → `-- สำรองข้อมูลเสร็จสิ้น:` + วันที่ไทย — ชื่อไฟล์ (`backup_*.sql`, `equipment_*.csv`) คง ASCII (ตามข้อยกเว้น spec หัวข้อ 7 กันปัญหา header download) แต่หัวคอลัมน์+เนื้อหาในไฟล์ต้องไทย 100%

- [ ] **Step 3: ดาวน์โหลดไฟล์จริงตรวจ (CSV เปิดด้วย UTF-8 + Excel .xls)**

โหลด: สรุปค่าเสื่อมรายปี, รายละเอียดค่าเสื่อม, satisfaction, equipment, repairs, backup 1 ไฟล์ — เปิดดูหัวคอลัมน์ไทย ไม่มี `???` เพี้ยน (ถ้าเพี้ยน เติม BOM `\xEF\xBB\xBF` ตอนส่งออก CSV)

- [ ] **Step 4: Commit**

```bash
git add app/Controllers/AdminController.php app/Controllers/DepreciationController.php app/Controllers/SatisfactionController.php app/Controllers/DashboardController.php app/Views/admin/backup.php
git commit -m "fix(thai): export CSV Excel backup header เนื้อหาไทยทั้งหมด"
```

---

### Task 7: สแกนรอบสอง + ตรวจทุก role + ลบสคริปต์ชั่วคราว

**Files:**
- Delete: `tests/thai_date_audit_test.php` (สคริปต์ชั่วคราว)
- Verify only (ไม่แก้โปรดักชันเพิ่มนอกจากจุดที่หลุด)

- [ ] **Step 1: สแกนรอบสองต้องเหลือ 0**

Run: `rg -n "format\('M Y'\)" app`
Expected: empty

Run: `rg -n "d/m/Y" app/Views`
Expected: empty (ยกเว้นคอมเมนต์ที่อธิบายว่าเคยเป็น — ถ้ามีให้ลบออก)

Run: `rg -n "Jan |Feb |Mar |Apr |May |Jun |Jul |Aug |Sep |Oct |Nov |Dec " app/Views app/Models`
Expected: empty

Run: `C:\xampp\php\php.exe tests\depreciation_test.php`
Expected: ทุกบรรทัด `PASS`

- [ ] **Step 2: เปิดตรวจทุก role + พิมพ์รายงาน**

เปิด admin/staff/teacher/student ครบทุกหน้า ดูด้วยตา: ไม่มีอังกฤษบนจอ ไม่มี ค.ศ. ไม่มี `Jan-Dec` บนกราฟ — พิมพ์รายงาน 1 หน้า (Ctrl+P preview) วันที่หัวรายงานเป็นแบบเต็ม `12 สิงหาคม 2568`

- [ ] **Step 3: ลบสคริปต์ชั่วคราว + Commit ปิดงาน**

```bash
Remove-Item tests/thai_date_audit_test.php
git add -A
git commit -m "chore(thai-audit): ตรวจซ้ำเหลือ 0 ภาษาไทย พ.ศ. กราฟไทยครบทั้งระบบ"
```

---

## Self-Review (ผู้เขียนแผนตรวจแล้ว)

1. **Spec coverage:** เป้าหมาย 4 ข้อ → Task 1-2 (helper+models วันที่/กราฟ), Task 3 (กราฟ 5 หน้า), Task 4-6 (7 หมวดครบ: views/flash/export/backup/layout), Task 7 (เกณฑ์ 0 จุด + ทุก role) — ครบทุก section ของ spec รวมข้อยกเว้นชื่อไฟล์ ASCII และ `aria-label` มาตรฐาน
2. **Placeholder scan:** ไม่มี TBD/TODO/implement later — ทุก step มีโค้ดจริง คำสั่งจริง expected output จริง เลขบรรทัดไฟล์จริงจากสำรวจ
3. **Type consistency:** ชื่อฟังก์ชันตรงกันทั้งแผน (`chartMonthYearThai(DateTime)` คืน `ม.ค. 2568`, `chartMonthLabelThai` คืน `ม.ค. 68`) — Models เรียกชื่อเดียวกับ Task 1, Views ไม่แปลซ้ำปลายทาง
