<?php
$pageTitle = 'ส่งรายการแจ้งซ่อมบำรุงครุภัณฑ์';
?>

<div class="page-header">
    <h1><i class="bi bi-wrench me-2"></i>ส่งรายการแจ้งซ่อมบำรุงครุภัณฑ์</h1>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil-square me-2"></i>แบบฟอร์มแจ้งซ่อมบำรุงครุภัณฑ์</div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= SITE_URL ?>/repairs/submit" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">ค้นหาครุภัณฑ์ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control mb-2" id="equipmentSearch"
                            placeholder="พิมพ์รหัสครุภัณฑ์ ชื่อ หรือห้อง เพื่อค้นหา...">
                        <select class="form-select" name="equipment_id" id="equipmentSelect" required>
                            <option value="">-- เลือกครุภัณฑ์ --</option>
                            <?php foreach ($equipment as $eq): ?>
                                <option value="<?= $eq['id'] ?>"
                                    data-search="<?= htmlspecialchars(strtolower(($eq['code'] ?? '') . ' ' . $eq['name'] . ' ' . ($eq['brand'] ?? '') . ' ' . ($eq['room'] ?? ''))) ?>"
                                    <?= ((int)($_POST['equipment_id'] ?? 0) === (int) $eq['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($eq['code'] ?? $eq['name']) ?> -
                                    <?= htmlspecialchars($eq['name']) ?> (<?= htmlspecialchars($eq['room'] ?? '-') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text" id="equipmentCount">พบ <?= count($equipment) ?> รายการ</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">รายละเอียดความขัดข้อง/ปัญหาที่พบ <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" name="issue" rows="4" required
                            placeholder="อธิบายอาการเสียหรือปัญหาที่พบ..."><?= htmlspecialchars($_POST['issue'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">แนบภาพถ่ายประกอบ (ถ้ามี)</label>
                        <input type="file" class="form-control" id="imageInput" accept="image/*" multiple>
                        <div class="form-text">สามารถแนบได้หลายรูป รองรับ JPG, PNG, GIF กดเลือกหลายครั้งเพื่อเพิ่มรูป
                        </div>
                        <div id="imagePreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                        <div id="imageInputsContainer"></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>ส่งคำร้อง</button>
                        <a href="<?= SITE_URL ?>/" class="btn btn-outline-secondary">ยกเลิก</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>คำแนะนำการใช้งาน</div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>พิมพ์ค้นหาครุภัณฑ์ด้วยรหัส ชื่อ หรือห้อง</li>
                    <li>เลือกครุภัณฑ์ที่ต้องการแจ้งซ่อม</li>
                    <li>อธิบายอาการเสียให้ละเอียด</li>
                    <li>แนบรูปภาพเพื่อให้เจ้าหน้าที่เข้าใจปัญหา</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('equipmentSearch');
        const select = document.getElementById('equipmentSelect');
        const countText = document.getElementById('equipmentCount');
        const allOptions = Array.from(select.options).slice(1);

        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase().trim();

            select.innerHTML = '<option value="">-- เลือกครุภัณฑ์ --</option>';

            let visibleCount = 0;
            allOptions.forEach(function (option) {
                const searchData = option.dataset.search || '';
                if (searchTerm === '' || searchData.includes(searchTerm)) {
                    select.appendChild(option.cloneNode(true));
                    visibleCount++;
                }
            });

            countText.textContent = 'พบ ' + visibleCount + ' รายการ';

            if (visibleCount === 1) {
                select.selectedIndex = 1;
            }
        });

        // Image preview with delete functionality
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');
        const imageInputsContainer = document.getElementById('imageInputsContainer');
        let selectedFiles = [];
        let fileCounter = 0;

        imageInput.addEventListener('change', function () {
            const files = Array.from(this.files);

            files.forEach(function (file) {
                if (!file.type.startsWith('image/')) return;

                const fileId = 'file_' + fileCounter++;
                selectedFiles.push({ id: fileId, file: file });

                const previewDiv = document.createElement('div');
                previewDiv.className = 'position-relative';
                previewDiv.id = 'preview_' + fileId;
                previewDiv.style.cssText = 'width: 100px; height: 100px;';

                const img = document.createElement('img');
                img.className = 'rounded border';
                img.style.cssText = 'width: 100px; height: 100px; object-fit: cover;';

                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0';
                deleteBtn.style.cssText = 'padding: 0 5px; font-size: 12px;';
                deleteBtn.innerHTML = '<i class="bi bi-x"></i>';
                deleteBtn.onclick = function () {
                    selectedFiles = selectedFiles.filter(f => f.id !== fileId);
                    document.getElementById('preview_' + fileId).remove();
                    document.getElementById('input_' + fileId).remove();
                };

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'file';
                hiddenInput.name = 'images[]';
                hiddenInput.id = 'input_' + fileId;
                hiddenInput.style.display = 'none';

                const dt = new DataTransfer();
                dt.items.add(file);
                hiddenInput.files = dt.files;

                const reader = new FileReader();
                reader.onload = function (e) {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);

                previewDiv.appendChild(img);
                previewDiv.appendChild(deleteBtn);
                imagePreview.appendChild(previewDiv);
                imageInputsContainer.appendChild(hiddenInput);
            });

            this.value = '';
        });
    });
</script>
