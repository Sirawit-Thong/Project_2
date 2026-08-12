# Design: หน้าตรวจสอบและยืนยันสภาพครุภัณฑ์ (อาจารย์) ให้เหมือนออริจิน

วันที่: 2026-08-12

## เป้าหมาย

สร้างหน้า `ตรวจสอบและยืนยันสภาพครุภัณฑ์` ให้อาจารย์ (ตรงกับ `original website/teacher/my_equipment.php`) ในโปรเจกต์ rebuild ใหม่ และให้เป็นหน้าเดียวที่อาจารย์ใช้ตรวจสอบครุภัณฑ์ในความดูแล — **แทนที่** หน้ารายการทะเบียนเดิมทั้งหมด

## ปัญหาปัจจุบัน

- Sidebar อาจารย์: "ตรวจสอบครุภัณฑ์ที่รับผิดชอบ" ชี้ไป `/equipment` ซึ่งเป็นตารางทะเบียนรวมแบบ admin — ไม่ใช่หน้าเดิม
- โปรเจกต์ยังไม่มีหน้า `my_equipment` เลย

## ขอบเขต

ไฟล์ที่สร้าง/แก้:

1. **สร้าง** `app/Views/equipment/my_equipment.php` — port จากต้นฉบับ
2. **แก้** `app/Controllers/EquipmentController.php` — เพิ่ม method `myEquipment()`
3. **แก้** `app/Controllers/EquipmentController.php::index()` — redirect อาจารย์ไป `/equipment/my`
4. **แก้** `index.php` — เพิ่ม routes `GET/POST /equipment/my`
5. **แก้** `includes/sidebar.php` — ลิงก์อาจารย์ชี้ `/equipment/my`
6. **แก้** `app/Views/dashboard/teacher_report.php` — ลิงก์ห้องชี้ `/equipment/my?room=`
7. **แก้** `app/Views/equipment/detail.php` — breadcrumb อาจารย์ชี้ `/equipment/my`

Model มีของพร้อมใช้แล้ว (ไม่แก้): `RoomManager::getManagedRoomCount`, `RoomManager::isOwner`, `Equipment::hasNonManagedEquipment`, `Equipment::getNonManagedByHolder`, `Equipment::getByRoomWithItems`, `Equipment::check`, `Equipment::updateStatus`, `Equipment::getForTeacherExport`, route `/teacher/export?room=` มีอยู่แล้ว

## Controller — `EquipmentController::myEquipment()`

### GET
- `requireLogin()` + `authorize(['teacher'])`
- `$managedRooms` = `RoomManager::getManagedRoomCount($userId)` → รายการ `{id, name, eq_count}` เรียงตามชื่อห้อง
- ถ้า `Equipment::hasNonManagedEquipment($userId)` → append `['id' => 'other', 'name' => 'อื่นๆ (ไม่ได้อยู่ในห้องที่รับผิดชอบ)']`
- `$selectedRoom = $_GET['room'] ?? ''`
- ถ้า `$selectedRoom === 'other'`: `$equipment = Equipment::getNonManagedByHolder($userId)`, `$selectedRoomName = 'อื่นๆ (ไม่ได้อยู่ในห้องที่รับผิดชอบ)'`
- ถ้าเป็นเลขห้อง: verify `RoomManager::isOwner($userId, $roomId)` → ถ้าใช่ `$selectedRoomName = ชื่อห้อง`, `$equipment = Equipment::getByRoomWithItems($roomId)`; ถ้าไม่ใช่ → ปล่อยว่าง (ไม่มีสิทธิ์)
- คำนวณ `$eqStats` = `total`, `available`, `broken`, `inspected` (ตรวจปีนี้ = `check_date` อยู่ในปีปัจจุบัน)
- `$pageTitle = 'ตรวจสอบและยืนยันสภาพครุภัณฑ์'`, `$viewPath = 'equipment/my_equipment'`

### POST
- `validateCsrf()` แล้วจึงประมวลผล
- อ่าน `equipment_id`, `check_status` (`ok`/`broken`), `remark`
- **Verify สิทธิ์**: ห้องที่เลือกจริง (ไม่ใช่ `other`) → ต้อง `RoomManager::isOwner` ของห้องนั้น + ครุภัณฑ์อยู่ในห้องนั้น; `other` → ต้อง `holder_id = $userId` → ถ้าไม่ผ่าน → flash danger + redirect กลับ ไม่บันทึก
- บันทึก: `Equipment::check($id, $remark)` (update `check_date` = วันนี้ + `remark`)
- ถ้า `check_status === 'broken'` → `Equipment::updateStatus($id, 'broken')`
- `logActivity($userId, 'Teacher Equipment Check', "ตรวจสอบครุภัณฑ์ ID: $id")`
- `flash('success', 'บันทึกการตรวจสอบสำเร็จ')` + redirect กลับ `my_equipment.php?room=...` (ประยุกต์เป็น `/equipment/my?room=`)

## View — `app/Views/equipment/my_equipment.php`

port จากต้นฉบับโดยปรับเป็น MVC:

- หัวเรื่อง `.page-header` + `<h1>` `bi-clipboard-check` + "ตรวจสอบและยืนยันสภาพครุภัณฑ์" + breadcrumb (แดชบอร์ด / ตรวจสอบครุภัณฑ์)
- ถ้าไม่มีห้องที่ดูแล → `alert-warning` "คุณยังไม่ได้รับมอบหมายให้ดูแลห้องปฏิบัติการใด..."
- **การ์ดเลือกห้อง**: dropdown `name="room"` `onchange="this.form.submit()"` + ปุ่ม "ล้างค่า" เมื่อเลือกแล้ว (GET ไป `/equipment/my`)
- **สถิติ 4 การ์ด** (`col-md-3 col-6`): ครุภัณฑ์ทั้งหมด (primary) / พร้อมใช้งาน (success) / ชำรุด (danger) / ตรวจสอบแล้ว (info + progress bar + %)
- **ตาราง**: `#`, รหัสครุภัณฑ์ (badge), รายการ (ชื่อ + ยี่ห้อ/รุ่น), สถานะ (badge), ตรวจสอบล่าสุด (hide-mobile, `formatDateThai`, ✓เขียวถ้าตรวจปีนี้ / warning icon), ดำเนินการ:
  - ลิงก์ดูรายละเอียด → `/equipment/{id}` (`btn-outline-primary` + `bi-eye`)
  - ปุ่ม "ตรวจสอบ" (success) → เปิด modal
- **Card header**: "รายการครุภัณฑ์ — ห้อง X" + ปุ่ม "ส่งออก Excel" → `/teacher/export?room=` + `urlencode($selectedRoom)` (`btn-success btn-sm` + `bi-file-earmark-excel`)
- **Modal ยืนยันผล** (`#checkModal`): header `bg-success text-white` "ยืนยันผลการตรวจสอบสภาพครุภัณฑ์" + ข้อมูลครุภัณฑ์ (badge รหัส + ชื่อ) + radio การ์ด 2 ใบ (`พร้อมใช้งาน` success / `ชำรุด/เสียหาย` danger) + textarea หมายเหตุ + ปุ่ม ยกเลิก / บันทึกผลการตรวจสอบ
- **JS**: `show.bs.modal` event → ใส่ค่า `data-id/code/name/remark` ลง modal, reset radio เป็น `ok`
- form POST ไป `/equipment/my` + `csrf_field()`
- ใช้ `sanitize()`, `getStatusBadgeClass()`, `translateEquipmentStatus()`, `formatDateThai()`

## การแทนที่ (ตามที่ผู้ใช้เลือก)

1. `sidebar.php` อาจารย์: `href="/equipment"` → `href="/equipment/my"`
2. `EquipmentController::index()`: ถ้า `$role === 'teacher'` → `redirect(SITE_URL . '/equipment/my')`
3. `teacher_report.php`: ลิงก์ `/equipment?room=` → `/equipment/my?room=`
4. `equipment/detail.php`: breadcrumb สำหรับอาจารย์ `href="/equipment"` → `href="/equipment/my"` (label คงเดิม "ตรวจสอบครุภัณฑ์")

## Routes

ลงทะเบียนใน `index.php` ก่อน `/equipment/{id}`:

```php
$router->get('/equipment/my', 'EquipmentController@myEquipment');
$router->post('/equipment/my', 'EquipmentController@myEquipment');
```

(ตัว Router เช็ค exact match ก่อนอยู่แล้ว แต่วางไว้ก่อนเพื่อความชัดเจน)

## ข้อมูลที่ใช้

| ข้อมูล | แหล่ง |
|---|---|
| ห้องที่ดูแล | `RoomManager::getManagedRoomCount($userId)` |
| มีครุภัณฑ์นอกห้องดูแลหรือไม่ | `Equipment::hasNonManagedEquipment($userId)` |
| รายการครุภัณฑ์ (ห้องจริง) | `Equipment::getByRoomWithItems($roomId)` |
| รายการครุภัณฑ์ (อื่นๆ) | `Equipment::getNonManagedByHolder($userId)` |
| บันทึกผลตรวจ | `Equipment::check($id, $remark)` + `Equipment::updateStatus($id, 'broken')` |
| Excel export | `/teacher/export?room=` (มีอยู่แล้ว) |

## การทดสอบ

- login อาจารย์ (`teacher@rmutsb.ac.th` ดูแลห้อง 6102) → กด "ตรวจสอบครุภัณฑ์ที่รับผิดชอบ" ไปหน้าใหม่ ไม่ใช่ `/equipment`
- เลือกห้อง → เห็นสถิติ + ตารางครุภัณฑ์
- กด "ตรวจสอบ" → modal → เลือก "ชำรุด/เสียหาย" + หมายเหตุ → บันทึก → ตรวจใน detail/DB ว่า `check_date`=วันนี้, `remark` เซฟ, สถานะ = `broken`
- กรณี "พร้อมใช้งาน" → บันทึก `check_date` + `remark` แต่สถานะคงเดิม
- กรณีไม่มีสิทธิ์ (ครุภัณฑ์ห้องอื่น) → ปฏิเสธ + flash
- ผู้ใช้ที่ไม่มีห้องดูแล (Test Teacher) → เห็น alert แจ้งเตือน
- ลิงก์ breadcrumb/รายงานชี้ถูกต้อง
