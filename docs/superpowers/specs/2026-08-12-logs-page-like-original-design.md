# Design: หน้าบันทึกระบบ (Logs) ให้เหมือนออริจิน

วันที่: 2026-08-12

## เป้าหมาย

ปรับหน้า `ประวัติการใช้งานระบบ (Logs)` ในเวอร์ชันที่ rebuild ใหม่ ให้มีหน้าตา/โครงสร้างตรงกับ `original website/admin/logs.php` โดย **เก็บช่องค้นหาไว้** (ตามที่ผู้ใช้เลือก)

## ขอบเขต

แก้ 2 ไฟล์:

1. `app/Views/admin/logs.php` — เขียนใหม่ให้โครงสร้างเหมือนออริจิน
2. `app/Controllers/AdminController.php` — แก้บั๊กช่องค้นหา (อ่านค่า `q` แทน `search`)

ไม่ต้องแก้ Model/DB เพราะ `SystemLog::getFiltered()` ส่ง `rows` (มี `user_name`, `created_at`, `action`, `details`, `ip_address`) และ `pagination` (`total_items`, `total_pages`, ...) ครบแล้ว

## การแก้ไข

### 1. Controller (`AdminController::logs`)

จากเดิมอ่าน `$_GET['search']` → เปลี่ยนเป็น `$_GET['q']` (ชื่อช่องค้นหาในฟอร์ม) เพื่อให้ค้นหาทำงานจริง

### 2. View (`app/Views/admin/logs.php`)

โครงสร้างใหม่ (จากบนลงล่าง):

- **หัวเรื่อง**: `.page-header` + `<h1>` ไอคอน `bi-journal-text` ข้อความ `ประวัติการใช้งานระบบ (Logs)` (แทน h4)
- **การ์ดค้นหา**: คงเดิม (ฟอร์ม GET ส่ง `q`)
- **การ์ดตาราง** (เหมือนออริจิน):
  - `.card-header`: ไอคอน `bi-list` + `รายการบันทึกเหตุการณ์ (จำนวน)` — จำนวนจาก `$pagination['total_items']` (นับหลังกรอง)
  - `.card-body p-0` → ตาราง `table table-hover table-sm mb-0`, `<thead>` ธรรมดา
  - คอลัมน์ (5): **เวลา** (width 150) / **ผู้ใช้** / **การกระทำ** / **รายละเอียด** (`hide-mobile`) / **IP** (`hide-mobile`)
  - เวลา: `<small><?= formatDateTimeThai($row['created_at']) ?></small>`
  - ผู้ใช้: `user_name` ถ้ามี, ถ้าไม่มี → `<span class="text-muted">ระบบ</span>`
  - การกระทำ: `<span class="badge bg-secondary">`
  - รายละเอียด: `<small>` ถ้า null → `-`
  - IP: `<small>`
  - ไม่มีข้อมูล → แถวว่างข้อความ `ไม่พบบันทึกระบบ`
- **Pagination**: ใส่ใน `.card-footer` ผ่าน `paginationLinks($pagination, baseUrl)` เมื่อ `total_pages > 1`
  - baseUrl = `<?= SITE_URL ?>/logs?` (หรือต่อ `&q=` เมื่อมีการค้นหา) เพราะ `paginationLinks` ต่อท้าย `&page=N`

## ข้อมูลที่ใช้

| ข้อมูล | แหล่ง |
|---|---|
| รายการ log | `$result['rows']` — แต่ละแถวมี `created_at`, `user_name`, `action`, `details`, `ip_address` |
| จำนวน/หน้า | `$result['pagination']` — `total_items`, `total_pages`, `current_page` |

## การทดสอบ

- เปิด `/logs` ในเบราว์เซอร์ (login admin) — หน้าเหมือนออริจิน + มีช่องค้นหา
- ค้นหาคำที่ตรงกับ action/details/ชื่อผู้ใช้ → ได้ผลลัพธ์กรอง และ pagination ยังเก็บ `q` ไว้
- ผู้ใช้ที่ถูกลบ/ค่า user_id null → แสดง `ระบบ`
