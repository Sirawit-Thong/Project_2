# Backup File-by-File PHP Native Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers-subagent-driven-development (recommended) or superpowers-executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** สร้างระบบสำรองรูปทีละไฟล์แบบ PHP Native สำหรับ InfinityFree ที่ scan uploads ทีละ 200 ไฟล์/รอบ, ทำ incremental ด้วย size+mtime → SHA-256 เฉพาะไฟล์ที่เปลี่ยน, เก็บ manifest/progress ใน backups/private/ (private, resume ได้แม้ปิด browser) และดาวน์โหลดทีละไฟล์พร้อม progress B ตาม spec 2026-09-03

**Architecture:** แยก `BackupController` ใหม่ (5 endpoints) + `private/` state + `backup_filebyfile.php` view + JS loop, เชื่อม `database.sql` เดิมผ่าน `performSilentBackup`, หน้า `admin/backup.php` เดิมเพิ่มลิงก์ไปหน้าใหม่, `index.php` เพิ่ม routes, `docs/BACKUP.md` เพิ่ม FTP fallback — ไม่ ZIP บน server

**Tech Stack:** PHP 8.2 (homegrown MVC `app/Controllers`, `app/Models`, `app/Core`), MySQL/MariaDB 10.4, Bootstrap 5.3, JS vanilla, `backups/private/.htaccess` double deny, `SHA-256` via `hash_file()`

---

## File Structure

**Create:**
- `app/Controllers/BackupController.php` — 5 endpoints + helpers (scanBatch, incremental, progress)
- `app/Views/admin/backup_filebyfile.php` — UI B (progress 1,172/1,500 + Download Current + Continue)
- `backups/private/.htaccess` — `Require all denied` ชั้น 2
- `tests/backup_filebyfile_test.php` — TDD สำหรับ incremental + path traversal

**Modify:**
- `app/Views/admin/backup.php` — เพิ่มการ์ด/ลิงก์ไป `/backup/filebyfile`
- `index.php` — เพิ่ม 5 routes (`/backup/filebyfile`, `/backup/manifest/*`, `/backup/file`, `/backup/progress/*`)
- `docs/BACKUP.md` — เพิ่มบท "วิธีที่ 4: FTP (แนะนำ InfinityFree)" + file-by-file

**Keep (ไม่แตะ):**
- `app/Controllers/AdminController.php` (downloadBackup เดิม), `uploads/`, `backups/.htaccess` ชั้น 1

---

### Task 1: Private Storage Infrastructure

**Files:**
- Create: `backups/private/.htaccess`
- Create: `backups/private/README.md` (optional)
- Test: `tests/backup_filebyfile_test.php` (initial)

- [ ] **Step 1: Write failing test for private dir guard**

```php
// tests/backup_filebyfile_test.php
<?php
function test_private_dir_exists_and_denied() {
    $dir = __DIR__ . '/../backups/private';
    assert(is_dir($dir), 'private dir must exist');
    $ht = file_get_contents($dir . '/.htaccess');
    assert(strpos($ht, 'Require all denied') !== false, '.htaccess must deny');
    echo "PASS private dir\n";
}
test_private_dir_exists_and_denied();
```

- [ ] **Step 2: Run test to verify it fails**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php`
Expected: FAIL `private dir must exist` (ยังไม่สร้าง)

- [ ] **Step 3: Create private dir + .htaccess**

```bash
mkdir backups/private
```

File `backups/private/.htaccess`:
```
# Private backup state - deny all web access (layer 2)
Options -Indexes
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>
<FilesMatch "\.(json|sql|log)$">
    <IfModule mod_authz_core.c>Require all denied</IfModule>
    <IfModule !mod_authz_core.c>Deny from all</IfModule>
</FilesMatch>
```

- [ ] **Step 4: Run test to verify it passes**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php`
Expected: `PASS private dir`

- [ ] **Step 5: Commit**

```bash
git add backups/private/.htaccess tests/backup_filebyfile_test.php
git commit -m "feat(backup-file): private storage backups/private/.htaccess layer 2"
```

---

### Task 2: BackupController Scaffolding + Scan Batch

**Files:**
- Create: `app/Controllers/BackupController.php`
- Modify: `index.php` (add routes)
- Test: `tests/backup_filebyfile_test.php`

- [ ] **Step 1: Write failing test for scanBatch**

```php
// tests/backup_filebyfile_test.php — add
function test_scan_batch_200() {
    require_once __DIR__ . '/../app/Controllers/BackupController.php';
    $c = new BackupController();
    // สร้างไฟล์ dummy 5 ไฟล์ใน uploads/test_scan/
    $base = __DIR__ . '/../uploads/test_scan';
    @mkdir($base, 0755, true);
    for($i=0;$i<5;$i++) file_put_contents("$base/f$i.jpg", "data$i");
    $res = $c->scanBatchForTest($base, 0, 2); // offset 0 limit 2
    assert(count($res['files'])===2, 'batch 2');
    assert($res['next_offset']===2, 'next 2');
    assert($res['done']===false, 'not done');
    // cleanup
    array_map('unlink', glob("$base/*")); rmdir($base);
    echo "PASS scanBatch\n";
}
test_scan_batch_200();
```

- [ ] **Step 2: Run test to verify it fails**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php`
Expected: FAIL `Class BackupController not found`

- [ ] **Step 3: Create BackupController minimal**

```php
// app/Controllers/BackupController.php
<?php
class BackupController extends Controller {
    private $privateDir;
    public function __construct() {
        $this->privateDir = dirname(__DIR__, 2) . '/backups/private';
        if (!is_dir($this->privateDir)) mkdir($this->privateDir, 0750, true);
    }
    public function index() { $this->requireLogin(); $this->authorize(['admin']); $pageTitle='สำรองรูปทีละไฟล์'; $viewPath='admin/backup_filebyfile'; require __DIR__.'/../Views/layouts/main.php'; }
    // helper for test
    public function scanBatchForTest($base, $offset, $limit) {
        $all = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
        foreach($it as $f) if($f->isFile()) $all[] = $f->getPathname();
        sort($all);
        $batch = array_slice($all, $offset, $limit);
        $files = array_map(fn($p)=>['path'=>str_replace('\\','/', substr($p, strlen(dirname(__DIR__,2))+1)), 'size'=>filesize($p), 'mtime'=>filemtime($p)], $batch);
        return ['files'=>$files, 'next_offset'=>$offset+count($batch), 'done'=>($offset+count($batch) >= count($all)), 'total'=>count($all)];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php`
Expected: PASS

- [ ] **Step 5: Add routes in index.php**

```php
// index.php หลัง admin routes
$router->get('/backup/filebyfile', 'BackupController@index');
$router->post('/backup/manifest/create', 'BackupController@createManifest');
$router->get('/backup/manifest/{id}', 'BackupController@getManifest');
$router->get('/backup/progress/{id}', 'BackupController@getProgress');
$router->get('/backup/file', 'BackupController@downloadFile');
```

- [ ] **Step 6: Commit**

```bash
git add app/Controllers/BackupController.php index.php tests/backup_filebyfile_test.php
git commit -m "feat(backup-file): BackupController scaffolding + scanBatch 200 + routes"
```

---

### Task 3: Incremental Detection (size+mtime → SHA-256)

**Files:**
- Modify: `app/Controllers/BackupController.php`
- Test: `tests/backup_filebyfile_test.php`

- [ ] **Step 1: Write failing test for incremental**

```php
function test_incremental_sha256() {
    require_once __DIR__ . '/../app/Controllers/BackupController.php';
    $c = new BackupController();
    $prev = ['a.jpg'=>['size'=>10,'mtime'=>100,'hash'=>hash('sha256','hello')]];
    $curr = ['path'=>'uploads/a.jpg','size'=>10,'mtime'=>100]; // เหมือนเดิม
    $res = $c->isNeedBackupForTest($curr, $prev, __DIR__.'/tmp_a.jpg');
    assert($res['need_backup']===false && $res['reason']==='unchanged', 'unchanged skip');
    // เปลี่ยน mtime แต่เนื้อหาเดิม
    file_put_contents(__DIR__.'/tmp_a.jpg','hello');
    $curr2 = ['path'=>'uploads/a.jpg','size'=>10,'mtime'=>101];
    $res2 = $c->isNeedBackupForTest($curr2, $prev, __DIR__.'/tmp_a.jpg');
    assert($res2['need_backup']===false, 'mtime diff but hash same -> skip');
    // เปลี่ยนเนื้อหา
    file_put_contents(__DIR__.'/tmp_a.jpg','world');
    $curr3 = ['path'=>'uploads/a.jpg','size'=>10,'mtime'=>102];
    $res3 = $c->isNeedBackupForTest($curr3, $prev, __DIR__.'/tmp_a.jpg');
    assert($res3['need_backup']===true && $res3['reason']==='modified', 'modified');
    unlink(__DIR__.'/tmp_a.jpg');
    echo "PASS incremental\n";
}
test_incremental_sha256();
```

- [ ] **Step 2: Run test to verify it fails**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php`
Expected: FAIL `isNeedBackupForTest not found`

- [ ] **Step 3: Implement isNeedBackup**

```php
// in BackupController
public function isNeedBackupForTest($curr, $prevMap, $fullPath) {
    $key = basename($curr['path']);
    if (!isset($prevMap[$key])) return ['need_backup'=>true,'reason'=>'new','hash'=>hash_file('sha256',$fullPath)];
    $p = $prevMap[$key];
    if ($p['size'] !== $curr['size'] || $p['mtime'] !== $curr['mtime']) {
        $hash = is_file($fullPath) ? hash_file('sha256',$fullPath) : null;
        if ($hash !== $p['hash']) return ['need_backup'=>true,'reason'=>'modified','hash'=>$hash];
        return ['need_backup'=>false,'reason'=>'unchanged','hash'=>$hash];
    }
    return ['need_backup'=>false,'reason'=>'unchanged','hash'=>$p['hash']];
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/BackupController.php tests/backup_filebyfile_test.php
git commit -m "feat(backup-file): incremental size+mtime -> SHA256"
```

---

### Task 4: Progress Tracking + Resume (Server-side)

**Files:**
- Modify: `app/Controllers/BackupController.php`
- Test: `tests/backup_filebyfile_test.php`

- [ ] **Step 1: Write failing test for progress**

```php
function test_progress_resume() {
    $c = new BackupController();
    $id = '20260903_103000_abc123';
    $c->saveProgressForTest($id, ['current_index'=>5,'total'=>10,'status'=>'in_progress']);
    $p = $c->loadProgressForTest($id);
    assert($p['current_index']===5, 'resume 5');
    $c->updateProgressForTest($id, 6);
    $p2 = $c->loadProgressForTest($id);
    assert($p2['current_index']===6, 'resume 6');
    unlink(__DIR__."/../backups/private/progress_{$id}.json");
    echo "PASS progress\n";
}
test_progress_resume();
```

- [ ] **Step 2: Run test to verify it fails**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php`
Expected: FAIL `saveProgressForTest not found`

- [ ] **Step 3: Implement progress helpers**

```php
public function saveProgressForTest($id,$data){ $f=$this->privateDir."/progress_{$id}.json"; file_put_contents($f, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX); }
public function loadProgressForTest($id){ $f=$this->privateDir."/progress_{$id}.json"; return json_decode(file_get_contents($f), true); }
public function updateProgressForTest($id,$idx){ $p=$this->loadProgressForTest($id); $p['current_index']=$idx; $p['updated_at']=date('Y-m-d H:i:s'); $this->saveProgressForTest($id,$p); }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/BackupController.php tests/backup_filebyfile_test.php
git commit -m "feat(backup-file): progress JSON server-side resume"
```

---

### Task 5: Single File Download + Security

**Files:**
- Modify: `app/Controllers/BackupController.php`
- Test: `tests/backup_filebyfile_test.php`

- [ ] **Step 1: Write failing test for path traversal**

```php
function test_traversal_blocked() {
    $c = new BackupController();
    $ok = $c->isPathAllowedForTest('uploads/equipment/001.jpg');
    $bad = $c->isPathAllowedForTest('../../config/database.php');
    $bad2 = $c->isPathAllowedForTest('uploads/../config/database.php');
    assert($ok===true, 'allowed');
    assert($bad===false, 'blocked traversal');
    assert($bad2===false, 'blocked dotdot');
    echo "PASS traversal\n";
}
test_traversal_blocked();
```

- [ ] **Step 2: Run test to verify it fails**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php`
Expected: FAIL `isPathAllowedForTest not found`

- [ ] **Step 3: Implement downloadFile + guard**

```php
public function isPathAllowedForTest($rel){
    $base = realpath(dirname(__DIR__,2).'/uploads');
    $target = realpath(dirname(__DIR__,2).'/'.$rel);
    if (!$base || !$target) return false;
    return strpos($target, $base)===0;
}
public function downloadFile(){
    $this->requireLogin(); $this->authorize(['admin']);
    $backup_id = $_GET['backup_id'] ?? '';
    $path = $_GET['path'] ?? '';
    if (!preg_match('/^\d{8}_\d{6}_[a-z0-9]{6}$/',$backup_id)) { http_response_code(400); echo json_encode(['error'=>'invalid backup_id']); exit; }
    if (!$this->isPathAllowedForTest($path)) { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }
    $full = dirname(__DIR__,2).'/'.$path;
    if (!is_file($full)) { http_response_code(404); echo json_encode(['error'=>'not found']); exit; }
    // update progress
    $progFile = $this->privateDir."/progress_{$backup_id}.json";
    if (is_file($progFile)) { $p=json_decode(file_get_contents($progFile),true); $p['current_index']++; $p['updated_at']=date('Y-m-d H:i:s'); file_put_contents($progFile, json_encode($p,JSON_UNESCAPED_UNICODE), LOCK_EX); }
    header('Content-Type: '.mime_content_type($full));
    header('Content-Disposition: attachment; filename="'.basename($full).'"');
    header('X-Hash-SHA256: '.hash_file('sha256',$full));
    readfile($full); exit;
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/BackupController.php tests/backup_filebyfile_test.php
git commit -m "feat(backup-file): single file download + traversal guard + SHA256 header"
```

---

### Task 6: View + JS Progress UI (แบบ B)

**Files:**
- Create: `app/Views/admin/backup_filebyfile.php`
- Modify: `app/Views/admin/backup.php` (add card)

- [ ] **Step 1: Write failing test for view exists**

```php
function test_view_exists(){
    assert(is_file(__DIR__.'/../app/Views/admin/backup_filebyfile.php'), 'view must exist');
    $c = file_get_contents(__DIR__.'/../app/Views/admin/backup_filebyfile.php');
    assert(strpos($c,'progress')!==false, 'has progress');
    echo "PASS view\n";
}
test_view_exists();
```

- [ ] **Step 2: Run test to verify it fails**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php`
Expected: FAIL `view must exist`

- [ ] **Step 3: Create backup_filebyfile.php**

```php
<!-- page-header + 2 cards: Manifest + Progress -->
<div class="page-header"><h1><i class="bi bi-files me-2"></i>สำรองรูปทีละไฟล์</h1></div>
<div class="card mb-3"><div class="card-body">
  <button id="btnStart" class="btn btn-primary">เริ่ม Scan</button>
  <div id="progressWrap" class="d-none">
    <div class="progress"><div id="bar" class="progress-bar" style="width:0%">0%</div></div>
    <small id="label">0 / 0</small>
    <button id="btnCurrent" class="btn btn-outline-primary">Download Current File</button>
    <button id="btnContinue" class="btn btn-success">Continue</button>
  </div>
</div></div>
<script>
let backup_id=null, files=[], idx=0;
document.getElementById('btnStart').onclick=async()=>{
  let offset=0, done=false;
  while(!done){
    let r=await fetch(SITE_URL+'/backup/manifest/create',{method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':csrf}, body:JSON.stringify({offset,limit:200})});
    let j=await r.json(); backup_id=j.backup_id; files=j.files; done=j.done; offset=j.next_offset;
  }
  document.getElementById('progressWrap').classList.remove('d-none');
  loop();
};
async function loop(){
  for(; idx<files.length; idx++){
    let p=files[idx].path;
    let r=await fetch(SITE_URL+'/backup/file?backup_id='+backup_id+'&path='+encodeURIComponent(p));
    if(!r.ok) continue;
    let blob=await r.blob(); let a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=p.split('/').pop(); a.click();
    await new Promise(r=>setTimeout(r,300));
    document.getElementById('bar').style.width=Math.round((idx+1)/files.length*100)+'%';
    document.getElementById('label').textContent=(idx+1)+' / '+files.length;
  }
}
</script>
```

- [ ] **Step 4: Run test to verify it passes**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php`
Expected: PASS

- [ ] **Step 5: Add card in backup.php**

```php
// app/Views/admin/backup.php หลัง alert-info เพิ่ม:
<div class="card mb-4"><div class="card-body text-center">
  <i class="bi bi-files fs-1 text-warning"></i>
  <p>สำรองรูปจำนวนมากแบบทีละไฟล์ (InfinityFree)</p>
  <a href="<?= SITE_URL ?>/backup/filebyfile" class="btn btn-warning">ไปหน้าสำรองทีละไฟล์</a>
</div></div>
```

- [ ] **Step 6: Commit**

```bash
git add app/Views/admin/backup_filebyfile.php app/Views/admin/backup.php
git commit -m "feat(backup-file): view file-by-file B + JS loop 300ms"
```

---

### Task 7: Docs + Final Verification

**Files:**
- Modify: `docs/BACKUP.md`
- Test: `tests/backup_filebyfile_test.php` (full)

- [ ] **Step 1: Write failing test for docs**

```php
function test_docs_has_ftp(){
    $c=file_get_contents(__DIR__.'/../docs/BACKUP.md');
    assert(strpos($c,'วิธีที่ 4')!==false || strpos($c,'FTP')!==false, 'docs must have FTP');
    echo "PASS docs\n";
}
test_docs_has_ftp();
```

- [ ] **Step 2: Run test to verify it fails**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php`
Expected: FAIL if docs not yet updated

- [ ] **Step 3: Update docs/BACKUP.md add chapter 4**

```markdown
## วิธีที่ 4: ดาวน์โหลดผ่าน FTP (แนะนำ InfinityFree)
- FileZilla: Host ftpupload.net, User/Pass จาก InfinityFree
- ดาวน์โหลด: `mirror uploads/` หรือลาก `uploads/` ลงเครื่อง
- ตรวจครบ: `jq '.files | length' backups/private/manifest_*.json`
- Restore: FTP อัปโหลดกลับไปที่ `htdocs/uploads/` + phpMyAdmin Import `database.sql`
```

- [ ] **Step 4: Run all tests to verify they pass**

Run: `C:\xampp\php\php.exe tests/backup_filebyfile_test.php && C:\xampp\php\php.exe tests/depreciation_test.php`
Expected: ALL PASS

- [ ] **Step 5: Commit**

```bash
git add docs/BACKUP.md tests/backup_filebyfile_test.php
git commit -m "docs(backup): FTP fallback chapter + file-by-file tests PASS"
```

