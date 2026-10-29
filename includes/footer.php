        </main>
    </div>
</div>
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content confirm-modal">
            <div class="modal-body">
                <h2 id="confirmDeleteTitle">Konfirmasi tindakan</h2>
                <p data-confirm-message>Apakah Anda yakin ingin melanjutkan?</p>
                <div class="modal-actions">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-danger" type="button" data-confirm-submit>Ya, lanjutkan</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(appUrl('/assets/js/script.js')) ?>"></script>
</body>
</html>
