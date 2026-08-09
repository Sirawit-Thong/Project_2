<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class=" mb-0"><i class="bi bi-database-down"></i> สำรองฐานข้อมูล</h4>
    <form method="POST" action="<?= SITE_URL ?>/backup">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="action" value="download">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-download"></i> ดาวน์โหลด SQL
        </button>
    </form>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h6 class="mb-0"><i class="bi bi-table"></i> ข้อมูลตารางในฐานข้อมูล</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="60">#</th>
                        <th>ชื่อตาราง</th>
                        <th width="140" class="text-end">จำนวนแถว</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tableInfo)): ?>
                        <?php $total = 0; $i = 1; ?>
                        <?php foreach ($tableInfo as $tableName => $rowCount): ?>
                            <?php $total += $rowCount; ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td>
                                    <i class="bi bi-table text-info"></i>
                                    <?= htmlspecialchars($tableName) ?>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-secondary"><?= number_format($rowCount) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td colspan="2" class="text-end">รวมทั้งหมด</td>
                            <td class="text-end"><span class="badge bg-dark"><?= number_format($total) ?> แถว</span></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">ไม่พบข้อมูลตาราง</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
