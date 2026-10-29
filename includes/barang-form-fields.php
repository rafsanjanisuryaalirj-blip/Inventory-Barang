<?php
if (!defined('INVENTORY_APP')) {
    http_response_code(404);
    exit;
}
?>
<div class="row form-grid">
    <div class="col-md-6">
        <label class="form-label" for="kode_barang">Kode barang <span class="text-danger">*</span></label>
        <input class="form-control <?= isset($errors['kode_barang']) ? 'is-invalid' : '' ?>" id="kode_barang" name="kode_barang" maxlength="20" required value="<?= e($values['kode_barang']) ?>">
        <?php if (isset($errors['kode_barang'])): ?><div class="invalid-feedback"><?= e($errors['kode_barang']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="nama_barang">Nama barang <span class="text-danger">*</span></label>
        <input class="form-control <?= isset($errors['nama_barang']) ? 'is-invalid' : '' ?>" id="nama_barang" name="nama_barang" maxlength="150" required value="<?= e($values['nama_barang']) ?>">
        <?php if (isset($errors['nama_barang'])): ?><div class="invalid-feedback"><?= e($errors['nama_barang']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="id_kategori">Kategori <span class="text-danger">*</span></label>
        <select class="form-select <?= isset($errors['id_kategori']) ? 'is-invalid' : '' ?>" id="id_kategori" name="id_kategori" required>
            <option value="">Pilih kategori</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e($category['id_kategori']) ?>" <?= $values['id_kategori'] === (string) $category['id_kategori'] ? 'selected' : '' ?>><?= e($category['nama_kategori']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['id_kategori'])): ?><div class="invalid-feedback"><?= e($errors['id_kategori']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="harga">Harga (Rp) <span class="text-danger">*</span></label>
        <input class="form-control <?= isset($errors['harga']) ? 'is-invalid' : '' ?>" id="harga" name="harga" type="number" min="0" step="0.01" required value="<?= e($values['harga']) ?>">
        <?php if (isset($errors['harga'])): ?><div class="invalid-feedback"><?= e($errors['harga']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="stok">Stok <span class="text-danger">*</span></label>
        <input class="form-control <?= isset($errors['stok']) ? 'is-invalid' : '' ?>" id="stok" name="stok" type="number" min="0" step="1" required value="<?= e($values['stok']) ?>">
        <?php if (isset($errors['stok'])): ?><div class="invalid-feedback"><?= e($errors['stok']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="satuan">Satuan <span class="text-danger">*</span></label>
        <input class="form-control <?= isset($errors['satuan']) ? 'is-invalid' : '' ?>" id="satuan" name="satuan" maxlength="20" required placeholder="pcs, unit, box" value="<?= e($values['satuan']) ?>">
        <?php if (isset($errors['satuan'])): ?><div class="invalid-feedback"><?= e($errors['satuan']) ?></div><?php endif; ?>
    </div>
    <div class="col-12">
        <label class="form-label" for="foto">Foto <?= isset($isEdit) && $isEdit ? '' : '<span class="text-danger">*</span>' ?></label>
        <label class="upload-zone <?= isset($errors['foto']) ? 'upload-zone-invalid' : '' ?>" for="foto" data-drop-zone>
            <input class="upload-input" id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-image-input data-preview-target="#photo-preview" <?= isset($isEdit) && $isEdit ? '' : 'required' ?>>
            <span class="upload-instructions">
                <strong>Klik atau seret foto ke sini</strong>
                <span>JPG, PNG, WEBP, maks 2 MB</span>
            </span>
            <span class="upload-preview-row">
                <?php if (isset($isEdit) && $isEdit): ?>
                    <span class="upload-preview-item" data-current-photo>
                        <img class="form-photo-preview" id="current-photo-preview" src="<?= e(imageUrl($currentPhoto)) ?>" alt="Foto barang saat ini">
                        <span>Foto saat ini</span>
                    </span>
                <?php endif; ?>
                <span class="upload-preview-item" data-new-photo-wrap hidden>
                    <img id="photo-preview" class="form-photo-preview" src="" alt="Preview foto barang">
                    <span class="upload-filename" data-upload-name>Belum ada file dipilih</span>
                </span>
                <span class="upload-change">Ganti</span>
            </span>
        </label>
        <?php if (isset($errors['foto'])): ?><div class="invalid-feedback d-block"><?= e($errors['foto']) ?></div><?php endif; ?>
    </div>
    <div class="col-12">
        <label class="form-label" for="deskripsi">Deskripsi <span class="form-hint-inline">Opsional</span></label>
        <textarea class="form-control <?= isset($errors['deskripsi']) ? 'is-invalid' : '' ?>" id="deskripsi" name="deskripsi" rows="4" maxlength="5000"><?= e($values['deskripsi']) ?></textarea>
        <?php if (isset($errors['deskripsi'])): ?><div class="invalid-feedback"><?= e($errors['deskripsi']) ?></div><?php endif; ?>
    </div>
</div>
