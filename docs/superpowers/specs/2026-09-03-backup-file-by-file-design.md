# Design: Backup แบบ PHP Native ทีละไฟล์ + Incremental + Resume สำหรับ InfinityFree

วันที่: 2026-09-03
ผู้ออกแบบ: Muse Spark + เจ้าของระบบ (เลือก B + 1 + server+FTP, SHA-256, private state)

## 1. เป้าหมาย

แทนที่ระบบสำรองไฟล์แนบแบบ `ZipArchive` เดิม (`app/Controllers/AdminController.php:440 downloadFilesBackup()`) ที่สร้าง `uploads.zip` ใหญ่ไฟล์เดียวบน server ด้วยระบบ **PHP Native ทีละไฟล์** ที่เหมาะกับข้อจำกัด InfinityFree:

- `max_execution_time` 10-20s, `memory_limit` 128M, บางครั้งไม่มี `ZipArchive`
- มีรูป 1,500 ไฟล์ / 386 MB → สร้าง ZIP บน server จะ timeout/OOM และดาวน์โหลดไฟล์เดียวหลุดกลางทางต้องเริ่มใหม่หมด
- ต้องการ **Progress + Resume ปิด browser แล้วทำต่อได้ + Incremental** และกู้คืนผ่าน **FTP** ตามที่ InfinityFree แนะนำ

คงระบบ `database.sql` เดิมไว้ (mysqldump → PHP streaming fallback มีอยู่แล้ว) แล้วเชื่อมกับระบบไฟล์ใหม่ผ่าน `manifest.json`

## 2. ขอบเขต

**แยกจากของเดิม:** ของเดิม `AdminController::downloadFilesBackup()` เก็บไว้เป็น fallback สำหรับไฟล์น้อย (<100) แต่เมนูหลักจะชี้ไป `BackupController` ใหม่ — ไม่ลบของเดิม

**ไฟล์ที่สร้าง/แก้:**

1. **สร้าง** `app/Controllers/BackupController.php` — 4 endpoints หลัก (manifest, incremental, file, progress)
2. **สร้าง** `app/Views/admin/backup_filebyfile.php` — UI Progress B (`1,172 / 1,500 78%` + `Download Current File` + `Continue`)
3. **สร้าง** `backups/private/.htaccess` — ชั้น 2 กัน direct URL (นอกเหนือจาก `backups/.htaccess` ชั้น 1)
4. **แก้** `app/Views/admin/backup.php` — เพิ่มลิงก์/การ์ดไปหน้าใหม่
5. **แก้** `index.php` — เพิ่ม 5 routes ใหม่
6. **สร้าง** `docs/BACKUP.md` บทใหม่ — FTP fallback/manual recovery
7. **ไม่แตะ** `uploads/` — ห้ามเก็บ `manifest_*.json` / `progress_*.json` ใน `uploads/` เด็ดขาด (ตามข้อ 2 ที่ผู้ใช้ขอ)

**ไม่ทำ:** ZIP ฝั่ง server, hash ทุกไฟล์ทุกครั้ง, restore ผ่าน PHP upload (ใช้ FTP ตามที่เลือก)

## 3. Data Model + Manifest + Backup State (ส่วนที่ 1 — รากฐาน)

### 3.1 Storage — Private Backup State

```
backups/
├── .htaccess               (เดิม: Require all denied)
├── database_20260903_103000.sql
├── private/                (ใหม่)
│   ├── .htaccess           (Require all denied — ชั้น 2)
│   ├── manifest_20260903_103000_a1b2c3.json
│   ├── progress_20260903_103000_a1b2c3.json
│   └── latest.json         (copy ของ manifest ล่าสุด — ใช้เทียบ incremental)
└── README.md
```

- `backup_id = Ymd_His + '_' + 6char random [a-z0-9]` กันเดา, `chmod 0600`, `mkdir 0750`
- ไม่เคยส่ง `private/*` ผ่าน direct URL — ทุกการอ่านผ่าน PHP `requireLogin() + authorize(['admin'])`

### 3.2 Manifest — `manifest_{backup_id}.json`

```json
{
  "backup_id": "20260903_103000_a1b2c3",
  "created_at": "2026-09-03 10:30:00",
  "created_by": 1,
  "db_file": "database_20260903_103000.sql",
  "base_dir": "uploads",
  "total_files": 1500,
  "total_bytes": 405618432,
  "total_need_backup": 23,
  "hash_algo": "sha256",
  "incremental_from": "manifest_20260902_100000.json",
  "files": [
    {
      "path": "uploads/equipment/IMG_001.jpg",
      "size": 245812,
      "mtime": 1725265200,
      "mtime_human": "2026-09-02 10:00:00",
      "hash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
      "need_backup": true,
      "reason": "new"
    },
    {
      "path": "uploads/repairs/RP_002.jpg",
      "size": 512831,
      "mtime": 1725178800,
      "hash": null,
      "need_backup": false,
      "reason": "unchanged"
    }
  ]
}
```

- `reason`: `new | modified | unchanged | deleted`
- `hash` มีค่าเฉพาะเมื่อ `need_backup=true` และไฟล์นั้น `size/mtime` ต่างจาก `latest.json` → ค่อย `hash_file('sha256', $path)` ตรวจซ้ำ — ถ้า hash ตรง (mtime เปลี่ยนแต่เนื้อหาเดิม) → `need_backup=false`

### 3.3 Backup State — `progress_{backup_id}.json` (Server-side resume)

```json
{
  "backup_id": "20260903_103000_a1b2c3",
  "status": "in_progress",
  "current_index": 1172,
  "total_need_backup": 23,
  "started_at": "2026-09-03 10:30:00",
  "updated_at": "2026-09-03 10:35:12",
  "completed_files": ["uploads/equipment/001.jpg"],
  "failed_files": []
}
```

- อัปเดตแบบ atomic `file_put_contents($file, $json, LOCK_EX)` ทุกครั้งหลัง `GET /backup/file` สำเร็จ
- ปิด browser → กลับมา `GET /backup/progress/:id` ก็ resume จาก `current_index+1` ได้จากเครื่องไหนก็ได้

## 4. Backup Controller + Download/Resume Flow (ส่วนที่ 2)

### 4.1 Endpoints

```
POST /backup/manifest/create          body: {offset?: int, limit?: 200}
  → scanBatch 200 ไฟล์/รอบ (กัน 10s limit) → เขียน manifest + progress
  ← {backup_id, next_offset, done: bool, total_files, total_need_backup}

GET  /backup/manifest/:backup_id      → ส่ง manifest JSON
GET  /backup/manifest/latest          → ส่ง latest.json
GET  /backup/progress/:backup_id      → ส่ง progress JSON
GET  /backup/file?backup_id=...&path=uploads/equipment/001.jpg
  → verify path traversal (realpath must start with UPLOAD_PATH)
  → header + readfile + อัปเดต progress.current_index++
  → ส่ง X-Hash-SHA256 header ให้ client verify เพิ่ม

POST /backup/progress/reset           body: {backup_id}
```

- ทุก endpoint: `requireLogin()`, `authorize(['admin'])`, `validateCsrf()` (GET file ใช้ token ใน query `?token=` ที่ sign ด้วย `hash_hmac('sha256', path, CSRF)` กัน hotlink)

### 4.2 Scan Batch (กัน timeout InfinityFree)

```php
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(UPLOAD_PATH, SKIP_DOTS));
$batch = array_slice(iterator_to_array($it), $offset, 200);
foreach ($batch as $file) {
  $curr = ['path'=>rel, 'size'=>filesize, 'mtime'=>filemtime];
  // เทียบกับ latest.json (keyBy path)
  if (!isset($prev[$path])) { $need=true; $reason='new'; }
  elseif ($prev['size']!=$curr['size'] || $prev['mtime']!=$curr['mtime']) {
    $hash = hash_file('sha256', $fullPath); // เฉพาะไฟล์ที่เปลี่ยน
    $need = ($hash !== $prev['hash']); $curr['hash']=$hash;
    $reason = $need ? 'modified' : 'unchanged';
  } else { $need=false; $reason='unchanged'; $curr['hash']=$prev['hash']; }
}
```

- Client loop: `offset=0 → 200 → 400 → ...` จน `done=true` → ได้ `total_need_backup`

### 4.3 JS หน้า `backup_filebyfile.php` — แบบ B ที่ผู้ใช้เลือก

```
[START BACKUP] → POST /backup/manifest/create (offset 0) → loop batch จน done
  → แสดง ████████████░░░░ 0 / 23 + [ Download Current File ] [ Continue (auto) ]
กด Continue (auto):
  for i = current_index .. total_need_backup-1
    GET /backup/file?path=files[i].path → blob → <a download> trigger
    → await 300ms (กัน browser block popup) → update progress bar + GET /backup/progress
    → ถ้า fail (404) → push failed_files, ข้ามต่อ
ปิด browser → กลับมา GET /backup/progress/:id → resume จาก current_index+1 อัตโนมัติ
```

- ไม่ใช้ `localStorage` — state อยู่ server 100% (ตาม server+FTP)
- ไม่รวม ZIP ฝั่ง client — ทีละไฟล์จริงตาม B

## 5. MySQL Backup Integration (ส่วนที่ 3)

- ไม่แก้ logic เดิม (`AdminController::downloadBackup` mysqldump → PHP streaming)
- เมื่อสร้าง manifest เสร็จ ให้ `BackupController::createManifest` เรียก `AdminController::performSilentBackup('filebyfile')` สร้าง `backups/database_{backup_id}.sql` แล้วใส่ `db_file` ลง manifest
- UI แยก 2 การ์ด: `ดาวน์โหลด Database (.sql)` (ของเดิม) + `สำรองรูปทีละไฟล์` (ใหม่) — ไม่ปนกัน เพื่อให้ incremental รูปไม่ต้อง dump DB ซ้ำถ้าไม่จำเป็น (มี checkbox "รวม database.sql ด้วย")

## 6. FTP Fallback + Security + Docs (ส่วนที่ 4)

### 6.1 Security

- Double `Require all denied` (`backups/.htaccess` + `backups/private/.htaccess`)
- `GET /backup/file` path traversal guard: `$real = realpath(UPLOAD_PATH . $rel); if (strpos($real, realpath(UPLOAD_PATH)) !== 0) → 403`
- `backup_id` validate `preg_match('/^\d{8}_\d{6}_[a-z0-9]{6}$/')`
- ทุก POST มี `csrf_field()`, GET file ใช้ `?token=hCaptcha(hash_hmac)` หมดอายุ 10 นาที

### 6.2 FTP Fallback (ไม่ต้องเขียนโค้ดเพิ่ม)

- เพิ่มบทใหม่ใน `docs/BACKUP.md`: "วิธีที่ 4: ดาวน์โหลดผ่าน FTP (แนะนำ InfinityFree)"
  - FileZilla: `ftp://ftpupload.net` + `mirror uploads/`
  - ใช้ `manifest.json` ตรวจว่าครบไหม: `jq '.files[] | select(.need_backup)' manifest_*.json`

### 6.3 Restore

- ตามที่เลือก `server+FTP`: Restore = `FTP อัปโหลดกลับไปที่ uploads/` + `phpMyAdmin → Import database.sql`
- ไม่ทำ `POST /restore/file` — ลด attack surface, ง่ายและตรงกับคำแนะนำ InfinityFree

### 6.4 Error Handling

- ไฟล์ถูกลบระหว่าง backup → `GET /backup/file` 404 → ใส่ `failed_files` + ข้าม ไม่หยุดทั้งชุด
- Scan batch timeout → client เห็น `next_offset` → เรียกต่อ
- `hash_file` fail (permission) → `need_backup=true` แต่ `hash=null` + `reason='hash_failed'`

## 7. การทดสอบ

1. Scan 1,500 ไฟล์ batch 200 → 8 รอบ ผ่านโดยไม่ timeout (mock `max_execution_time=10`)
2. Incremental: สร้าง manifest ครั้งที่ 1 (1,500 need=1,500) → ครั้งที่ 2 เพิ่ม 23 รูปใหม่ → `total_need_backup=23`
3. Resume: ดาวน์โหลดไป 1,172 → ปิด browser → เปิดใหม่ `GET /backup/progress/:id` → ต่อจาก 1,173 ได้
4. SHA-256: เปลี่ยนไฟล์แต่คง size/mtime เดิม → hash ต่าง → `need_backup=true`
5. Security: `GET /backup/file?path=../../config/database.php` → 403
6. FTP docs: ทำตาม docs แล้วได้ `uploads/` ครบ + `database.sql` import สำเร็จ

## 8. สิ่งที่ไม่ทำ (YAGNI)

- ไม่ ZIP ฝั่ง server, ไม่ ZIP ฝั่ง client (JSZip), ไม่ restore ผ่าน PHP upload, ไม่ hash ทุกไฟล์ทุกครั้ง, ไม่เก็บ progress ใน localStorage
