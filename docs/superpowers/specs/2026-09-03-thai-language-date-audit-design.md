# Spec: ตรวจสอบภาษาไทยทั้งหมด + วันที่ พ.ศ. + กราฟไทย (รวมภาษาราชการ)

วันที่: 2026-09-03
สถานะ: รอผู้ใช้รีวิว
แนวทาง: สแกนอัตโนมัติ + ผูก Helper กลาง (อนุมัติแล้ว)

## 1. เป้าหมาย

1. ข้อความที่ผู้ใช้เห็นทั้งระบบเป็นภาษาไทยทั้งหมด ไม่เหลือภาษาอังกฤษตกค้าง ยกเว้นชื่อตัวแปร/โค้ด/คีย์เทคนิคหลังบ้าน
2. วันที่ทั้งหมดแสดงเป็น พ.ศ. ภาษาไทย ไม่มี ค.ศ. หรือเดือนอังกฤษ (Jan, Feb, M Y) หลงเหลือ
3. กราฟ Chart.js ทั้งหมด (labels, tooltip, แกน, หัวกราฟ, หน่วย) เป็นภาษาไทย
4. สำนวนเป็นภาษาราชการไทย: สุภาพ ทางการ สะกดตามพจนานุกรมราชบัณฑิตยสถาน

เกณฑ์สำเร็จ:
- สแกนรอบสองด้วย `rg` ต้องเหลือ 0 จุด (อังกฤษตกค้าง 0, `format('M Y')` 0, `d/m/Y` ที่แสดงผล 0, `date('Y-m-d')` ที่แสดงผลโดยไม่ผ่าน helper 0)
- เปิดตรวจทุกบทบาท (admin, staff, teacher, student) ครบทุกหน้า ไม่มีอังกฤษ/ค.ศ. บนจอ
- ไฟล์ CSV/Excel ที่ดาวน์โหลด หัวคอลัมน์และเนื้อหาตัวอย่างเป็นไทยทั้งหมด
- กราฟ 5+ จุด (admin/reports, dashboard/admin, satisfaction/dashboard, depreciation/report, teacher_report) แสดงเดือนไทย

## 2. ขอบเขต 7 หมวด (อนุมัติแล้ว รวมกราฟ)

1. Views (~60 ไฟล์ใน `app/Views/`): หัวข้อ, ป้าย, ปุ่ม, ตาราง, breadcrumb, title, aria-label, placeholder, modal, dropdown
2. Flash/Validation/Confirm: `setFlash`, `flashMessage`, ข้อความ validate ใน Controllers, `confirm()` / modal ยืนยันลบ, `uploadErrorMessage`
3. CSV/Excel Export: header และเนื้อหาใน `AdminController` (equipment/repairs/users), `DepreciationController` (summary/detail/my), `SatisfactionController`, `DashboardController` (.xls)
4. ชื่อไฟล์ดาวน์โหลด: `backup_*.sql`, `equipment_*.csv`, `depreciation_*.csv`, `satisfaction_*.csv` — หมายเหตุ: ชื่อไฟล์เทคนิคคง ASCII ได้ แต่ข้อความในไฟล์ต้องไทย (ข้อยกเว้นดูหัวข้อ 6)
5. Backup header/Log: `-- Date:`, `-- Backup completed:` ใน `AdminController`, หน้า `admin/backup.php` (`$tableLabels`), หน้า `admin/logs.php` + `SystemLog::log`
6. Layout/โครง: `app/Views/layouts/main.php`, `includes/header.php`, `includes/sidebar.php`, `includes/footer.php`, `<title>`, breadcrumb ทุกหน้า
7. กราฟ (เพิ่มตามคำขอ): `admin/reports.php` (monthlyChart/statusChart), `dashboard/admin.php` (repairChart/deptChart), `satisfaction/dashboard.php` (chartSatisfaction), `depreciation/report.php` (chartAnnual/chartNbv), `dashboard/teacher_report.php` (statusChart/roomChart) — รวม labels/tooltip/แกน/หัวกราฟ/หน่วย/วันที่

นอกขอบเขต: โค้ด PHP/ตัวแปร/ชื่อตาราง DB/คีย์ status (`available`, `pending`) หลังบ้าน, log ไฟล์เซิร์ฟเวอร์ดิบ, ชื่อ library (Chart.js, Bootstrap)

## 3. มาตรฐานภาษาราชการ (อนุมัติแล้ว)

- ใช้ภาษาราชการ สุภาพ ชัดเจน เช่น บัญชีผู้ใช้, รหัสผ่าน, เข้าสู่ระบบ, ลงทะเบียน, ดำเนินการ, บันทึก, ยกเลิก, ยืนยัน, อนุมัติ, ไม่อนุมัติ, รอดำเนินการ, พร้อมใช้งาน, ส่งซ่อม, ซ่อมไม่ได้, จำหน่ายออก, รอจำหน่ายออก, แจ้งซ่อม, ครุภัณฑ์, ห้องปฏิบัติการ, สาขาวิชา, ปีงบประมาณ (พ.ศ.), มูลค่า (บาท)
- ห้ามทับศัพท์อังกฤษบน UI: Dashboard → แดชบอร์ด/ภาพรวม, Login → เข้าสู่ระบบ, Logout → ออกจากระบบ, Register → ลงทะเบียน, Email → อีเมล, Password → รหัสผ่าน, Status → สถานะ, Search → ค้นหา, Save → บันทึก, Cancel → ยกเลิก, Delete → ลบ, Edit → แก้ไข, Close → ปิด, Backup → สำรองข้อมูล, Report → รายงาน, Export → ส่งออก, Total → รวมทั้งหมด, Next/Previous → ถัดไป/ก่อนหน้า
- พจนานุกรมกลาง: สร้างตารางคำแปลใน spec นี้เป็นแหล่งเดียว (Single Source) แล้วไล่แก้ให้ตรงกันทั้งระบบ ห้ามแปลคำเดียวกันหลายแบบ (เช่น อย่ามีทั้ง ผู้ใช้งาน/ผู้ใช้/ยูสเซอร์ ปนกัน — เลือก บัญชีผู้ใช้/ผู้ใช้ ให้คงที่)
- ตัวเลข: คั่นหลักพัน (`number_format`), สกุลเงินใช้ `formatCurrency()` (ลงท้าย บาท), จำนวนรายการใช้ รายการ/ครั้ง/ชิ้น ให้ถูกบริบท
- ข้อยกเว้นที่ยอมรับอังกฤษได้: ชื่อไฟล์เทคนิค ASCII, นามสกุลไฟล์ (.csv/.xls/.sql), คำย่อมาตรฐานในโค้ดเท่านั้น — บนจอผู้ใช้ต้องไทย

## 4. มาตรฐานวันที่ (อนุมัติแล้ว: ใช้สองแบบ)

ฐานเดิม: `formatDateThai()` (12 ส.ค. 2568) และ `formatDateTimeThai()` (12 ส.ค. 2568 14:30 น.) ใน `app/Helpers/functions.php:141-167` — เดือนย่อ ม.ค.–ธ.ค. + ปี +543 ถูกต้องแล้ว ให้ใช้เป็นฐานและห้ามเขียนแปลงวันที่แบบ ad-hoc ที่อื่น

- แบบย่อ (ตาราง/การ์ด/กราฟ/tooltip/flash): `12 ส.ค. 2568`, มีเวลาเป็น `12 ส.ค. 2568 14:30 น.` — ใช้ `formatDateThai()` / `formatDateTimeThai()`
- แบบเต็ม (หัวรายงาน/หน้ารายละเอียด/เอกสารพิมพ์): `12 สิงหาคม 2568` — เพิ่ม helper ใหม่ `formatDateThaiFull()` (มกราคม–ธันวาคม) + `formatDateTimeThaiFull()` ในขั้นตอนแผน
- กราฟ: แกน/label/tooltip ใช้เดือนไทยย่อ + ปี พ.ศ. เช่น `ส.ค. 68` หรือ `ส.ค. 2568` (เลือกแบบเดียวทั้งระบบในขั้นตอนแผน), ปีบนกราฟค่าเสื่อมใช้ `ปี พ.ศ. 2568`, tooltip มีหน่วย บาท/ครั้ง/คะแนน เป็นไทย
- ห้าม: `date('d/m/Y')` แสดงผล (เจอใน `equipment/inspection.php:148`), `$date->format('M Y')` (เจอใน `Repair.php:93`, `Satisfaction.php:58` — ออกมาเป็น Jan 2025), `DATE_FORMAT ... '%Y-%m'` ที่ส่ง label ดิบไปกราฟโดยไม่แปล, `date('Y-m-d H:i:s')` ใน Backup header ที่ผู้ใช้เห็น
- อนุญาต: `date('Y-m-d')` เฉพาะค่าภายใน (query, `max` ของ input, สร้างชื่อไฟล์) ที่ไม่ได้แสดงผลโดยตรง

## 5. สถาปัตยกรรม Helper กลาง

- `app/Helpers/functions.php` เป็นจุดเดียวสำหรับแปลงวันที่/เงิน:
  - มีแล้ว: `formatDateThai`, `formatDateTimeThai`, `formatCurrency`, `translateRole/UserStatus/EquipmentStatus/RepairStatus`, `uploadErrorMessage`, `paginationLinks` (ไทยแล้ว: ก่อนหน้า/ถัดไป/หน้า x จาก y)
  - เพิ่มในขั้นตอนแผน (ไม่ทำใน spec นี้): `formatDateThaiFull`, `formatDateTimeThaiFull`, `chartMonthLabelThai(DateTime $d)` / `chartMonthYearThai()` คืน `ม.ค. 68` หรือ `ส.ค. 2568` แบบเดียว, `thaiMonthShort()` / `thaiMonthFull()` ตารางเดือนกลาง
- Models ส่ง label ไทยสำเร็จรูป: `Repair::getMonthlyStats()` และ `Satisfaction::getMonthlyStats()` เลิกคืน `format('M Y')` ให้คืน label ไทยจาก helper แทน — Views กราฟแค่ `json_encode` ต่อ ไม่ต้องแปลปลายทาง
- Views ห้ามคำนวณปีเอง (`date('Y') + 543` ใน `crud/sets.php:172` ให้ย้ายผ่าน helper ในขั้นตอนแผนเพื่อกันลืม +543)

## 6. กระบวนการตรวจสอบ (อนุมัติแล้ว: 3 ขั้น)

ขั้นที่ 1 — สแกนอัตโนมัติ ออกรายงาน audit:
- `rg -n "format\('M Y'|d/m/Y|date\('Y-m-d H:i:s'\)|Jan|Feb|..." app/ includes/ config/` หาจุดวันที่อังกฤษ
- `rg -n "[A-Za-z]{3,}" app/Views --glob '*.php'` แล้วกรองคำอังกฤษบน UI (ยกเว้นตัวแปร/`<?= $... ?>`)
- ลิสต์ไฟล์ export/backup/graph ครบ 7 หมวด ออกรายงานเป็นเช็กลิสต์รายไฟล์

ขั้นที่ 2 — ซ่อมเป็นชุดตาม 7 หมวด ผูก helper กลาง:
- ลำดับ: Helper กลางก่อน → Models (2 ไฟล์กราฟ) → Views กราฟ (5 หน้า) → Views ตาราง/ฟอร์ม → Flash/Validation → Export/Backup → Layout/Title
- ทุกจุดที่แก้ต้องเรียก helper กลาง ห้าม hardcode เดือน/ปี

ขั้นที่ 3 — ตรวจซ้ำ (Verification):
- สแกนรอบสองต้องเหลือ 0
- เปิดเบราว์เซอร์ทุก role + พิมพ์รายงาน + ดาวน์โหลด CSV เปิดดูจริง
- เช็คสะกดราชการ (คำซ้ำซ้อน, สระ, วรรณยุกต์) รอบสุดท้าย

## 7. ความเสี่ยงและข้อยกเว้น

- ชื่อไฟล์ดาวน์โหลดถ้าแปลไทยล้วนอาจมีปัญหาภาษาไทยใน header download บางเบราว์เซอร์ — คงชื่อไฟล์ ASCII แต่เนื้อหาไทยครบ (ตัดสินใจในขั้นตอนแผน)
- Chart.js tooltip callback เดิมมีอังกฤษ (`label: function(context)`) ใน `admin/reports.php:256`, `dashboard/admin.php:354` — ต้องแปล
- `aria-label="breadcrumb"` เป็นมาตรฐานเทคนิคคงอังกฤษได้ แต่ข้อความที่อ่านออกเสียงต้องไทย
- ไฟล์ `README.md` อ่านเป็น binary ในเครื่องมือปัจจุบัน — ตรวจสอบ encoding (UTF-8) ในขั้นตอนแผน กันภาษาไทยเพี้ยนเป็น `????`

## 8. ไฟล์อ้างอิงหลัก

- `app/Helpers/functions.php:141-167` (helper วันที่/เงิน), `:255-273` (upload ไทยแล้ว), `:196-249` (pagination ไทยแล้ว)
- `app/Models/Repair.php:74-103` (จุด `format('M Y')`), `app/Models/Satisfaction.php:39-64` (จุดเดียวกัน)
- `app/Views/equipment/inspection.php:121,148` (จุด `d/m/Y`), `app/Views/equipment/form.php:20`, `bulk_add.php:4` (ใช้ `date('Y-m-d')` ภายใน — ตรวจว่าไม่แสดงผลดิบ)
- กราฟ: `app/Views/admin/reports.php:118-290`, `app/Views/dashboard/admin.php:215-390`, `app/Views/satisfaction/dashboard.php:35-95`, `app/Views/depreciation/report.php:39-120`, `app/Views/dashboard/teacher_report.php:139-190`
