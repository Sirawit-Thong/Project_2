# คู่มือสำรองและกู้คืนข้อมูล (Backup & Restore Guide)

> ระบบสำรองข้อมูลสำหรับโปรเจค **ระบบแจ้งซ่อมครุภัณฑ์** — รองรับทั้ง Windows (XAMPP) และ Linux/Production

---

## สารบัญ

1. [ภาพรวม](#ภาพรวม)
2. [วิธีที่ 1: สำรองผ่านหน้าเว็บ](#วิธีที่-1-สำรองผ่านหน้าเว็บ)
3. [วิธีที่ 2: สำรองผ่านสคริปต์](#วิธีที่-2-สำรองผ่านสคริปต์)
4. [วิธีที่ 3: สำรองผ่าน phpMyAdmin](#วิธีที่-3-สำรองผ่าน-phpmyadmin)
5. [การกู้คืนข้อมูล](#การกู้คืนข้อมูล)
6. [นโยบาย Retention](#นโยบาย-retention)
7. [การตั้งค่า Cron / Task Scheduler](#การตั้งค่า-cron--task-scheduler)
8. [FAQ / แก้ปัญหา](#faq--แก้ปัญหา)

---

## ภาพรวม

| รายการ | รายละเอียด |
|---|---|
| **ฐานข้อมูล** | `equipment_db` (local) / `if0_40083938_equipment_db` (production) |
| **ไฟล์สำรอง DB** | `backups/equipment_db_YYYY-MM-DD_HHmmss.sql.gz` |
| **ไฟล์สำรอง uploads** | `backups/uploads_YYYY-MM-DD_HHmmss.zip` (รวม `uploads/equipment`, `uploads/repairs`, `uploads/.htaccess`) |
| **Log** | `backups/backup.log` และ `backups/restore.log` |
| **การป้องกัน** | `backups/.htaccess` บล็อกการเข้าถึงผ่านเว็บ (`Require all denied`, `Options -Indexes`) |
| **Git** | `backups/*` ถูก ignore ยกเว้น `.htaccess` และ `README.md`; `*.sql.gz` ทั้งโปรเจคถูก ignore |

**คุณสมบัติสคริปต์สำรอง:**

- ใช้ `mysqldump` จาก `C:\xampp\mysql\bin\mysqldump.exe` (Windows) หรือ `mysqldump` ใน PATH (Linux)
- Options: `--default-character-set=utf8mb4 --single-transaction --routines --triggers --set-gtid-purged=OFF`
- บีบอัดเป็น `.sql.gz` ด้วย `gzip` (ถ้ามี) หรือ `.NET GzipStream` / `Compress-Archive` fallback
- Zip โฟลเดอร์ uploads อัตโนมัติ
- Retention 2 ระดับ (daily 7 วัน, weekly 30 วัน)
- มี Log และ Exit Code สำหรับตรวจสอบ

---

## วิธีที่ 1: สำรองผ่านหน้าเว็บ

> เหมาะสำหรับผู้ดูแลระบบที่ต้องการสำรองแบบ manual ผ่าน browser

1. Login เข้าสู่ระบบด้วยบัญชี **Admin**
2. ไปเมนู **ตั้งค่า / สำรองข้อมูล** (หรือ URL `/admin/backup` ถ้ามี)
3. กดปุ่ม **สำรองข้อมูลเดี๋ยวนี้**
4. ระบบจะสร้างไฟล์ `equipment_db_YYYY-MM-DD_HHmmss.sql.gz` และ `uploads_YYYY-MM-DD_HHmmss.zip` ใน `backups/`
5. ดาวน์โหลดไฟล์สำรองผ่านหน้าเว็บ (ลิงก์ดาวน์โหลด) — ไฟล์จะไม่ exposed ผ่าน URL โดยตรงเนื่องจาก `.htaccess` บล็อกไว้ ต้องดาวน์โหลดผ่านสคริปต์ PHP ที่ตรวจสอบสิทธิ์

**ข้อควรระวัง:**

- การสำรองผ่านเว็บจะใช้เวลาตามขนาด DB (ถ้า DB ใหญ่ >100MB อาจ timeout — แนะนำใช้สคริปต์แทน)
- ตรวจสอบ `backups/backup.log` หากสำรองล้มเหลว

---

## วิธีที่ 2: สำรองผ่านสคริปต์

### 2.1 Windows (PowerShell) — `scripts/backup.ps1`

**ตำแหน่ง:** `C:\xampp\htdocs\Project_2\scripts\backup.ps1`

**การใช้งานพื้นฐาน:**

```powershell
# รันด้วยค่าเริ่มต้น (DB=equipment_db, BackupDir=backups, KeepDays=7)
powershell -ExecutionPolicy Bypass -File scripts\backup.ps1

# หรือเรียกตรงๆ ถ้า ExecutionPolicy อนุญาต
.\scripts\backup.ps1

# กำหนดพารามิเตอร์เอง
.\scripts\backup.ps1 -DBName equipment_db -BackupDir backups -KeepDays 7

# กำหนด Host/User/Pass และ KeepWeeklyDays
.\scripts\backup.ps1 -DBName equipment_db -DBHost localhost -DBUser root -DBPass "secret" -KeepDays 7 -KeepWeeklyDays 30

# กำหนด path mysqldump เอง
.\scripts\backup.ps1 -MysqldumpPath "C:\xampp\mysql\bin\mysqldump.exe" -DBName equipment_db
```

**พารามิเตอร์ทั้งหมด:**

| พารามิเตอร์ | ค่าเริ่มต้น | คำอธิบาย |
|---|---|---|
| `-DBName` | `equipment_db` | ชื่อฐานข้อมูล |
| `-DBHost` | `localhost` | Host |
| `-DBUser` | `root` | User |
| `-DBPass` | `""` (ว่าง) | รหัสผ่าน |
| `-BackupDir` | `backups` | โฟลเดอร์เก็บไฟล์ (relative หรือ absolute) |
| `-KeepDays` | `7` | จำนวนวันเก็บไฟล์ daily |
| `-KeepWeeklyDays` | `30` | จำนวนวันเก็บไฟล์ weekly (Monday) |
| `-MysqldumpPath` | `C:\xampp\mysql\bin\mysqldump.exe` | Path ไปยัง mysqldump |

**การทำงานภายใน:**

1. ตรวจสอบ `mysqldump.exe` และสร้าง `backups/` ถ้ายังไม่มี
2. รัน `mysqldump --default-character-set=utf8mb4 --single-transaction --routines --triggers --set-gtid-purged=OFF equipment_db > equipment_db_YYYY-MM-DD_HHmmss.sql`
3. บีบอัดเป็น `.sql.gz` (ลอง `gzip` → fallback `.NET GzipStream` → fallback `Compress-Archive`)
4. Zip `uploads/equipment + uploads/repairs + uploads/.htaccess` เป็น `uploads_YYYY-MM-DD_HHmmss.zip`
5. ทำ Retention (ลบไฟล์เกิน 7 วัน, เกิน 30 วันลบแม้ weekly)
6. เขียน Log ไป `backups/backup.log` และคืน Exit Code `0` (สำเร็จ) หรือ `1` (ล้มเหลว)

**ตัวอย่าง Log:**

```
[2026-09-03 12:00:00] [INFO] === Backup started: DB=equipment_db Host=localhost BackupDir=C:\xampp\htdocs\Project_2\backups KeepDays=7 ===
[2026-09-03 12:00:01] [INFO] Database dump created: equipment_db_2026-09-03_120000.sql (123456 bytes)
[2026-09-03 12:00:02] [INFO] Compressed with GzipStream: equipment_db_2026-09-03_120000.sql.gz
[2026-09-03 12:00:02] [INFO] Uploads zipped: uploads_2026-09-03_120000.zip (7890 bytes)
[2026-09-03 12:00:02] [INFO] === Backup completed successfully: equipment_db_2026-09-03_120000.sql.gz (45678 bytes) ===
```

---

### 2.2 Linux / Production (Bash) — `scripts/backup.sh`

**ตำแหน่ง:** `scripts/backup.sh`

**การใช้งาน:**

```bash
# ค่าเริ่มต้น
./scripts/backup.sh
# หรือ
bash scripts/backup.sh

# กำหนด DB, BackupDir, KeepDays แบบ positional
./scripts/backup.sh equipment_db ./backups 7

# กำหนดผ่าน environment variables
DB_NAME=equipment_db BACKUP_DIR=./backups KEEP_DAYS=7 ./scripts/backup.sh
DB_HOST=localhost DB_USER=root DB_PASS=secret ./scripts/backup.sh equipment_db ./backups 7

# กำหนด KEEP_WEEKLY_DAYS
KEEP_WEEKLY_DAYS=30 ./scripts/backup.sh equipment_db ./backups 7
```

**พารามิเตอร์:**

| ตัวแปร / Argument | ค่าเริ่มต้น | คำอธิบาย |
|---|---|---|
| `DB_NAME` / `$1` | `equipment_db` | ชื่อ DB |
| `BACKUP_DIR` / `$2` | `backups` | โฟลเดอร์เก็บไฟล์ |
| `KEEP_DAYS` / `$3` | `7` | วันเก็บ daily |
| `KEEP_WEEKLY_DAYS` | `30` | วันเก็บ weekly |
| `DB_HOST` | `localhost` | Host |
| `DB_USER` | `root` | User |
| `DB_PASS` | `""` | Password |
| `MYSQLDUMP_PATH` | `mysqldump` | Path ไปยัง mysqldump |

**การทำงานภายใน (เหมือน PowerShell):**

- `mysqldump --default-character-set=utf8mb4 --single-transaction --routines --triggers --set-gtid-purged=OFF`
- `gzip -c` → fallback `tar`
- `zip -r` สำหรับ uploads → fallback `tar -czf`
- `find -mtime +7 / +30 -delete` แบบ 2-tier (keep Monday)
- Log ไป `backups/backup.log`

**ตั้งสิทธิ์ให้รันได้:**

```bash
chmod +x scripts/backup.sh scripts/restore.sh
```

---

## วิธีที่ 3: สำรองผ่าน phpMyAdmin

> เหมาะสำหรับสำรองครั้งคราวหรือเมื่อไม่มีสิทธิ์รันสคริปต์

1. เปิด `http://localhost/phpmyadmin` (XAMPP) หรือ phpMyAdmin ของโฮสต์
2. เลือกฐานข้อมูล `equipment_db` ทางซ้าย
3. ไปแท็บ **Export** (ส่งออก)
4. เลือก:
   - **Export method:** `Custom` (กำหนดเอง)
   - **Format:** `SQL`
   - **Tables:** เลือกทั้งหมด (Select all)
   - **Output:** `Save output to a file` + `Compression: gzipped`
   - **Options:**
     - ✅ `Add DROP TABLE` / `IF NOT EXISTS`
     - ✅ `Enclose table and column names with backquotes`
     - **Charset:** `utf8mb4`
5. กด **Go** เพื่อดาวน์โหลดไฟล์ `.sql` หรือ `.sql.gz`
6. นำไฟล์ไปเก็บใน `backups/` เอง (และอย่าลืมสำรอง `uploads/` แยกด้วย — zip โฟลเดอร์ `uploads/equipment` และ `uploads/repairs` เอง)

**สำหรับ uploads ผ่าน phpMyAdmin:**

- phpMyAdmin สำรองได้เฉพาะ DB — ต้อง zip `uploads/` เองด้วย File Manager หรือ `Compress` ใน Control Panel

---

## วิธีที่ 4: ดาวน์โหลดผ่าน FTP (แนะนำ InfinityFree)
- FileZilla: Host ftpupload.net, User/Pass จาก InfinityFree
- ดาวน์โหลด: `mirror uploads/` หรือลาก `uploads/` ลงเครื่อง
- ตรวจครบ: `jq '.files | length' backups/private/manifest_*.json`
- Restore: FTP อัปโหลดกลับไปที่ `htdocs/uploads/` + phpMyAdmin Import `database.sql`

---

## การกู้คืนข้อมูล

### ⚠️ คำเตือน

- การกู้คืนจะ **เขียนทับ** ข้อมูลปัจจุบันทั้งหมดใน DB ปลายทาง
- ควรสำรองข้อมูลปัจจุบันก่อนกู้คืนเสมอ
- ทดสอบไฟล์สำรองบน DB ทดสอบก่อนกู้คืนบน production

### 5.1 กู้คืนผ่านสคริปต์ PowerShell — `scripts/restore.ps1`

**ตำแหน่ง:** `scripts/restore.ps1`

**คำสั่งพื้นฐาน:**

```powershell
# แบบ interactive (มี confirmation prompt ให้พิมพ์ YES)
.\scripts\restore.ps1 -BackupFile backups\equipment_db_2026-09-03_120000.sql.gz

# แบบ force (ข้าม confirmation - สำหรับ automation)
.\scripts\restore.ps1 -BackupFile backups\equipment_db_2026-09-03_120000.sql.gz -Force

# กำหนด DB ปลายทาง
.\scripts\restore.ps1 -BackupFile backups\equipment_db_2026-09-03_120000.sql.gz -DBName equipment_db -Force

# กำหนด Host/User/Pass
.\scripts\restore.ps1 -BackupFile backups\equipment_db_2026-09-03_120000.sql -DBHost localhost -DBUser root -DBPass "secret" -Force

# รองรับทั้ง .sql และ .sql.gz
.\scripts\restore.ps1 -BackupFile backups\equipment_db_2026-09-03_120000.sql -Force

# ใช้ --force แบบ bash-style ก็ได้ (alias)
.\scripts\restore.ps1 backups\equipment_db_2026-09-03_120000.sql.gz --force
```

**พารามิเตอร์:**

| พารามิเตอร์ | ค่าเริ่มต้น | คำอธิบาย |
|---|---|---|
| `-BackupFile` (Positional 0) | **required** | Path ไปยังไฟล์ `.sql` หรือ `.sql.gz` |
| `-DBName` | `equipment_db` | DB ปลายทาง |
| `-DBHost` | `localhost` | Host |
| `-DBUser` | `root` | User |
| `-DBPass` | `""` | Password |
| `-MysqlPath` | `C:\xampp\mysql\bin\mysql.exe` | Path ไปยัง mysql.exe |
| `-Force` / `--force` | `false` | ข้าม confirmation prompt |

**การทำงาน:**

1. ตรวจสอบไฟล์มีอยู่และไม่ว่าง, ตรวจสอบนามสกุล `.sql` / `.sql.gz`
2. ถ้าไม่มี `-Force` จะให้พิมพ์ `YES` เพื่อยืนยัน
3. ถ้าเป็น `.sql.gz` จะ decompress ด้วย `gzip -dc` → fallback `.NET GzipStream` ไปไฟล์ temp
4. รัน `mysql --host=... --user=... --default-character-set=utf8mb4 equipment_db < dump.sql`
5. เขียน Log ไป `backups/restore.log` และคืน Exit Code

**ตัวอย่าง:**

```powershell
PS> .\scripts\restore.ps1 -BackupFile backups\equipment_db_2026-09-03_120000.sql.gz
⚠️  คำเตือน: การกู้คืนจะเขียนทับข้อมูลในฐานข้อมูล 'equipment_db' ทั้งหมด!
   ไฟล์: C:\xampp\htdocs\Project_2\backups\equipment_db_2026-09-03_120000.sql.gz
   ขนาด: 45678 bytes
   DB: equipment_db @ localhost (user: root)
พิมพ์ 'YES' เพื่อยืนยันการกู้คืน หรือกด Enter เพื่อยกเลิก: YES
[2026-09-03 12:10:00] [INFO] Restoring to database 'equipment_db' from 'C:\Users\...\Temp\restore_20260903_121000.sql'...
✅ กู้คืนข้อมูลสำเร็จ: backups\equipment_db_2026-09-03_120000.sql.gz -> equipment_db
```

---

### 5.2 กู้คืนผ่าน Command Line (Manual)

**Windows (XAMPP):**

```powershell
# 1) ไฟล์ .sql (plain)
C:\xampp\mysql\bin\mysql.exe --host=localhost --user=root --default-character-set=utf8mb4 equipment_db < backups\equipment_db_2026-09-03_120000.sql

# 2) ไฟล์ .sql.gz (ต้อง decompress ก่อน)
# วิธี A: ใช้ gzip (ถ้ามี)
gzip -dc backups\equipment_db_2026-09-03_120000.sql.gz | C:\xampp\mysql\bin\mysql.exe --host=localhost --user=root --default-character-set=utf8mb4 equipment_db

# วิธี B: ใช้ PowerShell GzipStream (ถ้าไม่มี gzip)
# ดูสคริปต์ restore.ps1 เป็นตัวอย่าง, หรือใช้ 7-Zip:
# "C:\Program Files\7-Zip\7z.exe" x -so backups\equipment_db_2026-09-03_120000.sql.gz | C:\xampp\mysql\bin\mysql.exe --host=localhost --user=root --default-character-set=utf8mb4 equipment_db

# 3) ถ้ามีรหัสผ่าน
C:\xampp\mysql\bin\mysql.exe --host=localhost --user=root --password=secret --default-character-set=utf8mb4 equipment_db < backups\equipment_db_2026-09-03_120000.sql

# 4) กู้คืน uploads (แตก zip ทับโฟลเดอร์เดิม)
Expand-Archive -Path backups\uploads_2026-09-03_120000.zip -DestinationPath . -Force
# หรือแตกเฉพาะ uploads
Expand-Archive -Path backups\uploads_2026-09-03_120000.zip -DestinationPath C:\xampp\htdocs\Project_2 -Force
```

**Linux / Production:**

```bash
# .sql
mysql --host=localhost --user=root --default-character-set=utf8mb4 equipment_db < backups/equipment_db_2026-09-03_120000.sql

# .sql.gz
gzip -dc backups/equipment_db_2026-09-03_120000.sql.gz | mysql --host=localhost --user=root --default-character-set=utf8mb4 equipment_db
# หรือ
gunzip < backups/equipment_db_2026-09-03_120000.sql.gz | mysql --host=localhost --user=root --default-character-set=utf8mb4 equipment_db

# มีรหัสผ่าน (แนะนำใช้ MYSQL_PWD เพื่อไม่ให้โผล่ใน ps)
MYSQL_PWD=secret mysql --host=localhost --user=root --default-character-set=utf8mb4 equipment_db < backups/equipment_db_2026-09-03_120000.sql

# กู้คืน uploads
unzip -o backups/uploads_2026-09-03_120000.zip -d ./
# หรือถ้าเป็น tar.gz
tar -xzf backups/uploads_2026-09-03_120000.tar.gz -C ./
```

---

### 5.3 กู้คืนผ่าน phpMyAdmin

1. เปิด phpMyAdmin → เลือกฐานข้อมูล `equipment_db`
2. ไปแท็บ **Import** (นำเข้า)
3. **File to import:** เลือกไฟล์ `.sql` หรือ `.sql.gz` (ถ้าไฟล์ใหญ่เกิน `upload_max_filesize` ให้เพิ่มใน `php.ini` หรือใช้สคริปต์แทน)
4. **Character set:** `utf8mb4`
5. **Format:** `SQL`
6. กด **Go**
7. รอจนขึ้น `Import has been successfully finished.`
8. สำหรับ `uploads` — อัปโหลดไฟล์ zip ผ่าน File Manager แล้วแตกไฟล์ทับ `uploads/`

> **Tip:** ถ้าไฟล์ `.sql.gz` ใหญ่เกิน limit ของ phpMyAdmin (เช่น 50MB) ให้ decompress เป็น `.sql` แล้ว import แบบแบ่งส่วน หรือใช้ `mysql` command line

---

## นโยบาย Retention

ระบบใช้ **2-tier retention** เพื่อประหยัดพื้นที่แต่ยังเก็บ weekly snapshot ไว้

| ระดับ | อายุไฟล์ | การจัดการ | ตัวอย่าง |
|---|---|---|---|
| **Daily** | `0–7 วัน` | **เก็บทั้งหมด** | ไฟล์ทุกวันใน 7 วันล่าสุดจะถูกเก็บไว้ |
| **Daily เกินกำหนด** | `>7 วัน` และ **ไม่ใช่ Monday** | **ลบอัตโนมัติ** | ไฟล์วันอังคาร–อาทิตย์ที่อายุ 8 วันจะถูกลบ |
| **Weekly** | `>7 วัน` และ **เป็น Monday** | **เก็บต่อ** จนถึง 30 วัน | ไฟล์วันจันทร์อายุ 15 วันจะยังถูกเก็บ |
| **Weekly เกินกำหนด** | `>30 วัน` | **ลบอัตโนมัติ** แม้เป็น Monday | ไฟล์วันจันทร์อายุ 31 วันจะถูกลบ |
| **Log** | ไม่จำกัด | **ไม่ถูกลบ** | `backup.log`, `restore.log` จะไม่ถูกลบโดย retention |

**สรุปตาราง:**

```
อายุไฟล์        | วันจันทร์ (Weekly) | วันอื่นๆ (Daily)
----------------|-------------------|------------------
0–7 วัน         | เก็บ              | เก็บ
8–30 วัน        | เก็บ              | ลบ
>30 วัน         | ลบ                | ลบ
```

**ค่าพารามิเตอร์:**

- `KeepDays = 7` (default, ปรับได้ผ่าน `-KeepDays` / `KEEP_DAYS`)
- `KeepWeeklyDays = 30` (default, ปรับได้ผ่าน `-KeepWeeklyDays` / `KEEP_WEEKLY_DAYS`)

**ตัวอย่างการทำงาน:**

```
สมมติวันนี้คือ 2026-09-03 (พุธ)
- 2026-09-02 (อังคาร, อายุ 1 วัน)  → เก็บ
- 2026-08-26 (อังคาร, อายุ 8 วัน)  → ลบ (เกิน 7 วัน, ไม่ใช่จันทร์)
- 2026-08-25 (จันทร์, อายุ 9 วัน)  → เก็บ (เกิน 7 วันแต่เป็นจันทร์, ยังไม่เกิน 30)
- 2026-08-01 (จันทร์, อายุ 33 วัน) → ลบ (เกิน 30 วัน)
```

**การปรับ Retention:**

```powershell
# Windows: เก็บ daily 14 วัน, weekly 60 วัน
.\scripts\backup.ps1 -KeepDays 14 -KeepWeeklyDays 60

# Linux
KEEP_DAYS=14 KEEP_WEEKLY_DAYS=60 ./scripts/backup.sh
```

---

## การตั้งค่า Cron / Task Scheduler

### Windows Task Scheduler

1. เปิด **Task Scheduler** → **Create Basic Task**
2. Name: `Equipment Backup Daily`
3. Trigger: **Daily** เวลา `02:00`
4. Action: **Start a program**
   - Program: `powershell.exe`
   - Arguments: `-ExecutionPolicy Bypass -File "C:\xampp\htdocs\Project_2\scripts\backup.ps1" -DBName equipment_db -BackupDir "C:\xampp\htdocs\Project_2\backups" -KeepDays 7`
5. Finish → ตรวจสอบใน `backups/backup.log`

**ทดสอบรันทันที:**

```powershell
powershell -ExecutionPolicy Bypass -File C:\xampp\htdocs\Project_2\scripts\backup.ps1; echo "Exit code: $LASTEXITCODE"
```

### Linux Cron

```bash
# เปิด crontab
crontab -e

# สำรองทุกวันเวลา 02:00
0 2 * * * /path/to/Project_2/scripts/backup.sh equipment_db /path/to/Project_2/backups 7 >> /path/to/Project_2/backups/backup.log 2>&1

# หรือแบบใช้ env
0 2 * * * DB_NAME=equipment_db BACKUP_DIR=/path/to/Project_2/backups KEEP_DAYS=7 /path/to/Project_2/scripts/backup.sh >> /path/to/Project_2/backups/backup.log 2>&1
```

---

## FAQ / แก้ปัญหา

**Q: mysqldump: Got error: 1045 Access denied?**  
A: ตรวจสอบ `DB_USER` / `DB_PASS` ใน `config/database.php` แล้วส่งให้สคริปต์: `.\scripts\backup.ps1 -DBUser if0_40083938 -DBPass secret -DBHost sql103.infinityfree.com`

**Q: ไฟล์ .sql.gz ขนาด 0 bytes?**  
A: ดู `backups/backup.log` และดู error ของ mysqldump; มักเกิดจากสิทธิ์ DB หรือ `mysqldump.exe` path ผิด

**Q: gzip: command not found?**  
A: บน Windows สคริปต์จะ fallback เป็น `.NET GzipStream` อัตโนมัติ — ไม่ต้องติดตั้ง gzip เพิ่ม; บน Linux ติดตั้งด้วย `sudo apt install gzip`

**Q: อยากเก็บ backup นานกว่า 7 วัน?**  
A: ปรับ `-KeepDays` เช่น `.\scripts\backup.ps1 -KeepDays 14` หรือแก้ `KEEP_DAYS` ใน cron

**Q: จะย้ายไฟล์สำรองไปเก็บที่อื่น (เช่น D:\backups หรือ S3)?**  
A: ใช้ `-BackupDir` ชี้ไปที่อื่นได้เลย: `.\scripts\backup.ps1 -BackupDir D:\backups`, หรือตั้ง cron ให้ `rsync`/`rclone` sync โฟลเดอร์ `backups/` ไปที่อื่นหลังสำรองเสร็จ

**Q: จะ restore แล้วขึ้น `ERROR 1049 Unknown database`?**  
A: สร้าง DB ก่อน: `mysql -u root -e "CREATE DATABASE equipment_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`

---

## โครงสร้างไฟล์ที่เกี่ยวข้อง

```
Project_2/
├── backups/
│   ├── .htaccess          # Require all denied + Options -Indexes
│   ├── README.md          # คำอธิบายโฟลเดอร์
│   ├── backup.log         # log สำรอง
│   ├── restore.log        # log กู้คืน
│   ├── equipment_db_*.sql.gz
│   └── uploads_*.zip
├── scripts/
│   ├── backup.ps1         # Windows PowerShell
│   ├── backup.sh          # Linux Bash
│   └── restore.ps1        # Windows Restore
├── docs/
│   └── BACKUP.md          # เอกสารนี้
└── .gitignore             # backups/*, !backups/.htaccess, *.sql.gz
```

---

*อัปเดตล่าสุด: 2026-09-03 — ดู `scripts/backup.ps1` และ `scripts/backup.sh` สำหรับ options ล่าสุด*
