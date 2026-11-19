        </main> <!-- /.app-content -->
    </div> <!-- /.app-main -->
</div> <!-- /.app-layout -->

<!-- Container Toast Notifikasi Global -->
<div id="toast-container"></div>

<!-- Modal Konfirmasi Hapus Kustom (Menggantikan confirm browser) -->
<div class="modal-backdrop" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <div class="modal-dialog">
        <form method="POST" id="deleteModalForm" action="">
            <?= csrf_field() ?>
            <div class="modal-header">
                <div class="modal-icon-danger">
                    <?= icon('trash', '', 20) ?>
                </div>
                <div>
                    <h3 class="modal-title" id="deleteModalTitle">Konfirmasi Hapus Data</h3>
                    <p class="modal-desc">
                        Apakah Anda yakin ingin menghapus <strong id="deleteModalItemName">data ini</strong>? Tindakan ini bersifat permanen dan tidak dapat dibatalkan.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="deleteModalCancel">Batal</button>
                <button type="submit" class="btn btn-danger">Ya, Hapus Data</button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="<?= url('assets/js/app.js') ?>"></script>

<!-- Render Flash Messages dari Session PHP -->
<?php
$flashes = get_flashes();
if (!empty($flashes)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($flashes as $f): ?>
    showToast(<?= json_encode($f['message']) ?>, <?= json_encode($f['type']) ?>);
    <?php endforeach; ?>
});
</script>
<?php endif; ?>

</body>
</html>
