<?php
$pageTitle = 'แจ้งซ่อมครุภัณฑ์';
?>

<div class="page-header">
    <h1><i class="bi bi-exclamation-circle me-2"></i>แจ้งซ่อมครุภัณฑ์</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/repairs/mine">รายการซ่อมของฉัน</a></li>
            <li class="breadcrumb-item active">แจ้งซ่อมใหม่</li>
        </ol>
    </nav>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil-square me-2"></i>แบบฟอร์มแจ้งซ่อม
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    กรุณาเลือกครุภัณฑ์ที่ต้องการซ่อม และอธิบายปัญหาให้ชัดเจน พร้อมแนบรูปภาพถ้าหากมี
                </div>

                <form method="POST" action="<?= SITE_URL ?>/repairs/submit" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="mb-4">
                        <label for="equipment_id" class="form-label fw-semibold">
                            <i class="bi bi-pc-display me-1"></i>ครุภัณฑ์ที่ต้องการซ่อม <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="equipment_id" name="equipment_id" required>
                            <option value="">-- เลือกครุภัณฑ์ --</option>
                            <?php foreach ($equipment as $eq): ?>
                                <option value="<?= $eq['id'] ?>" <?= (($_POST['equipment_id'] ?? '') == $eq['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($eq['code']) ?> — <?= htmlspecialchars($eq['name']) ?>
                                    <?php if (!empty($eq['room'])): ?>
                                        (<?= htmlspecialchars($eq['room']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">พิมพ์รหัสหรือชื่อครุภัณฑ์เพื่อค้นหา</div>
                    </div>

                    <div class="mb-4">
                        <label for="issue" class="form-label fw-semibold">
                            <i class="bi bi-chat-left-text me-1"></i>รายละเอียดปัญหา <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="issue" name="issue" rows="5" required
                            placeholder="กรุณาระบุปัญหาที่พบ เช่น จอไม่แสดงผล, เปิดไม่ติด, คีย์บอร์ดพิมพ์ไม่ได้..."
                        ><?= htmlspecialchars($_POST['issue'] ?? '') ?></textarea>
                        <div class="form-text">อธิบายปัญหาให้ละเอียดเพื่อความรวดเร็วในการซ่อม</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-images me-1"></i>รูปภาพประกอบ (ไม่บังคับ)
                        </label>
                        <input type="file" class="form-control d-none" id="images" name="images[]" multiple
                            accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewImages(this)">
                        <div class="text-center p-4 border border-2 border-dashed rounded-3" id="dropZone"
                            style="cursor: pointer; border-style: dashed !important;"
                            onclick="document.getElementById('images').click();">
                            <i class="bi bi-cloud-arrow-up fs-1 text-primary d-block mb-2"></i>
                            <p class="mb-1 text-muted">คลิกเพื่อเลือกรูปภาพ หรือลากไฟล์มาวางที่นี่</p>
                            <small class="text-muted">รองรับ JPG, PNG, GIF, WEBP — ขนาดไม่เกิน 5MB ต่อไฟล์</small>
                        </div>
                        <div id="imagePreview" class="row g-2 mt-3"></div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= SITE_URL ?>/repairs/mine" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>ย้อนกลับ
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send me-1"></i>แจ้งซ่อม
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewImages(input) {
    const container = document.getElementById('imagePreview');
    container.innerHTML = '';
    const files = input.files;
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (!file.type.startsWith('image/')) continue;
        const col = document.createElement('div');
        col.className = 'col-6 col-md-4 col-lg-3';
        const card = document.createElement('div');
        card.className = 'position-relative';
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.className = 'img-fluid rounded';
        img.style.cssText = 'width:100%; height:120px; object-fit:cover;';
        const name = document.createElement('small');
        name.className = 'text-muted d-block text-truncate mt-1';
        name.textContent = file.name;
        card.appendChild(img);
        card.appendChild(name);
        col.appendChild(card);
        container.appendChild(col);
    }
}

const dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('border-primary', 'bg-light');
});
dropZone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('border-primary', 'bg-light');
});
dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('border-primary', 'bg-light');
    const input = document.getElementById('images');
    input.files = e.dataTransfer.files;
    previewImages(input);
});
</script>


