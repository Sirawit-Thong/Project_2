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
const SITE_URL = "<?= SITE_URL ?>";
const csrf = "<?= csrf_token() ?>";
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
    document.getElementById('bar').textContent=Math.round((idx+1)/files.length*100)+'%';
    document.getElementById('label').textContent=(idx+1)+' / '+files.length;
  }
}
document.getElementById('btnCurrent').onclick=()=>{
  if(files[idx]) window.location.href=SITE_URL+'/backup/file?backup_id='+backup_id+'&path='+encodeURIComponent(files[idx].path);
};
document.getElementById('btnContinue').onclick=()=>{
  loop();
};
</script>
