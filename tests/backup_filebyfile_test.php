<?php
function test_private_dir_exists_and_denied() {
    $dir = __DIR__ . '/../backups/private';
    assert(is_dir($dir), 'private dir must exist');
    $ht = file_get_contents($dir . '/.htaccess');
    assert(strpos($ht, 'Require all denied') !== false, '.htaccess must deny');
    echo "PASS private dir\n";
}
test_private_dir_exists_and_denied();

function test_scan_batch_200() {
    require_once __DIR__ . '/../app/Controllers/BackupController.php';
    $c = new BackupController();
    $base = __DIR__ . '/../uploads/test_scan';
    @mkdir($base, 0755, true);
    for($i=0;$i<5;$i++) file_put_contents("$base/f$i.jpg", "data$i");
    $res = $c->scanBatchForTest($base, 0, 2);
    assert(count($res['files'])===2, 'batch 2');
    assert($res['next_offset']===2, 'next 2');
    assert($res['done']===false, 'not done');
    array_map('unlink', glob("$base/*")); rmdir($base);
    echo "PASS scanBatch\n";
}
test_scan_batch_200();

function test_incremental_sha256() {
    require_once __DIR__ . '/../app/Controllers/BackupController.php';
    $c = new BackupController();
    $prev = ['a.jpg'=>['size'=>10,'mtime'=>100,'hash'=>hash('sha256','hello')]];
    $curr = ['path'=>'uploads/a.jpg','size'=>10,'mtime'=>100];
    $res = $c->isNeedBackupForTest($curr, $prev, __DIR__.'/tmp_a.jpg');
    assert($res['need_backup']===false && $res['reason']==='unchanged', 'unchanged skip');
    file_put_contents(__DIR__.'/tmp_a.jpg','hello');
    $curr2 = ['path'=>'uploads/a.jpg','size'=>10,'mtime'=>101];
    $res2 = $c->isNeedBackupForTest($curr2, $prev, __DIR__.'/tmp_a.jpg');
    assert($res2['need_backup']===false, 'mtime diff but hash same -> skip');
    file_put_contents(__DIR__.'/tmp_a.jpg','world');
    $curr3 = ['path'=>'uploads/a.jpg','size'=>10,'mtime'=>102];
    $res3 = $c->isNeedBackupForTest($curr3, $prev, __DIR__.'/tmp_a.jpg');
    assert($res3['need_backup']===true && $res3['reason']==='modified', 'modified');
    unlink(__DIR__.'/tmp_a.jpg');
    echo "PASS incremental\n";
}
test_incremental_sha256();

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
function test_traversal_blocked() {
    $c = new BackupController();
    // สร้างไฟล์ dummy ชั่วคราวใน uploads เพื่อให้ realpath ผ่าน (ไม่ทับ .htaccess)
    $okPath = 'uploads/equipment/test_dummy_traversal_001.jpg';
    @mkdir(dirname(__DIR__.'/../'.$okPath), 0755, true);
    file_put_contents(__DIR__.'/../'.$okPath, 'dummy');
    $ok = $c->isPathAllowedForTest($okPath);
    $bad = $c->isPathAllowedForTest('../../config/database.php');
    $bad2 = $c->isPathAllowedForTest('uploads/../config/database.php');
    assert($ok===true, 'allowed '.$okPath);
    assert($bad===false, 'blocked traversal');
    assert($bad2===false, 'blocked dotdot');
    @unlink(__DIR__.'/../'.$okPath);
    echo "PASS traversal\n";
}
test_traversal_blocked();
function test_view_exists(){
    assert(is_file(__DIR__.'/../app/Views/admin/backup_filebyfile.php'), 'view must exist');
    $c = file_get_contents(__DIR__.'/../app/Views/admin/backup_filebyfile.php');
    assert(strpos($c,'progress')!==false, 'has progress');
    echo "PASS view\n";
}
test_view_exists();
function test_docs_has_ftp(){
    $c=file_get_contents(__DIR__.'/../docs/BACKUP.md');
    assert(strpos($c,'วิธีที่ 4')!==false || strpos($c,'FTP')!==false, 'docs must have FTP');
    echo "PASS docs\n";
}
test_docs_has_ftp();