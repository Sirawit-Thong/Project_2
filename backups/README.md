# Backups

โฟลเดอร์นี้ใช้เก็บไฟล์สำรองข้อมูล (database dumps และไฟล์ uploads)

## รายละเอียด

- ไฟล์สำรองทั้งหมดจะถูกเก็บในโฟลเดอร์นี้ (`backups/`)
- ไฟล์ถูก **gitignore** ไว้ด้วยกฎ `backups/*` ใน `.gitignore` — จะไม่ถูก commit ขึ้น Git
- ยกเว้นไฟล์ `backups/.htaccess` และ `backups/README.md` ที่ถูก allow ด้วย `!backups/.htaccess` และ `!backups/README.md` เพื่อเก็บโครงสร้างและการป้องกันไว้ใน repo
- ห้ามลบไฟล์ `.htaccess` เพราะใช้ป้องกันการเข้าถึงไฟล์สำรองผ่านเว็บ (Require all denied + Options -Indexes)

## ประเภทไฟล์ที่เก็บ

- `equipment_db_YYYY-MM-DD_HHmmss.sql.gz` — ดัมพ์ฐานข้อมูลที่บีบอัดแล้ว
- `uploads_YYYY-MM-DD_HHmmss.zip` — ไฟล์อัปโหลด (uploads/equipment, uploads/repairs)
- `backup.log` — log การสำรองข้อมูล

## การสำรองข้อมูล

ดูคู่มือฉบับเต็มที่ `docs/BACKUP.md`

รันสคริปต์สำรอง:

```powershell
# Windows (PowerShell)
.\scripts\backup.ps1
.\scripts\backup.ps1 -DBName equipment_db -BackupDir backups -KeepDays 7

# Linux / Production (Bash)
./scripts/backup.sh
./scripts/backup.sh equipment_db ./backups 7
```

## ความปลอดภัย

- โฟลเดอร์นี้ถูกบล็อกการเข้าถึงผ่านเว็บด้วย `Require all denied` ใน `.htaccess`
- ห้ามนำไฟล์สำรองไปไว้ในที่ที่เข้าถึงผ่านเว็บได้
