<?php
if (!class_exists('Controller')) {
    require_once __DIR__ . '/../Core/Controller.php';
}
class BackupController extends Controller {
    private $privateDir;
    public function __construct() {
        $this->privateDir = dirname(__DIR__, 2) . '/backups/private';
        if (!is_dir($this->privateDir)) mkdir($this->privateDir, 0750, true);
    }
    public function index() { $this->requireLogin(); $this->authorize(['admin']); $pageTitle='สำรองรูปทีละไฟล์'; $viewPath='admin/backup_filebyfile'; require __DIR__.'/../Views/layouts/main.php'; }
    public function scanBatchForTest($base, $offset, $limit) {
        $all = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
        foreach($it as $f) if($f->isFile()) $all[] = $f->getPathname();
        sort($all);
        $batch = array_slice($all, $offset, $limit);
        $files = array_map(fn($p)=>['path'=>str_replace('\\','/', substr($p, strlen(dirname(__DIR__,2))+1)), 'size'=>filesize($p), 'mtime'=>filemtime($p)], $batch);
        return ['files'=>$files, 'next_offset'=>$offset+count($batch), 'done'=>($offset+count($batch) >= count($all)), 'total'=>count($all)];
    }
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
    public function saveProgressForTest($id,$data){ $f=$this->privateDir."/progress_{$id}.json"; file_put_contents($f, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX); }
    public function loadProgressForTest($id){ $f=$this->privateDir."/progress_{$id}.json"; return json_decode(file_get_contents($f), true); }
    public function updateProgressForTest($id,$idx){ $p=$this->loadProgressForTest($id); $p['current_index']=$idx; $p['updated_at']=date('Y-m-d H:i:s'); $this->saveProgressForTest($id,$p); }
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
        $progFile = $this->privateDir."/progress_{$backup_id}.json";
        if (is_file($progFile)) { $p=json_decode(file_get_contents($progFile),true); $p['current_index']++; $p['updated_at']=date('Y-m-d H:i:s'); file_put_contents($progFile, json_encode($p,JSON_UNESCAPED_UNICODE), LOCK_EX); }
        header('Content-Type: '.mime_content_type($full));
        header('Content-Disposition: attachment; filename="'.basename($full).'"');
        header('X-Hash-SHA256: '.hash_file('sha256',$full));
        readfile($full); exit;
    }
}
