document.addEventListener('DOMContentLoaded', () => {
    let activeConfirmForm = null;
    const confirmModalElement = document.querySelector('#confirmDeleteModal');
    const confirmModal = confirmModalElement && window.bootstrap
        ? window.bootstrap.Modal.getOrCreateInstance(confirmModalElement)
        : null;

    document.querySelectorAll('[data-image-input]').forEach((input) => {
        const preview = document.querySelector(input.dataset.previewTarget);
        const previewWrap = input.closest('form')?.querySelector('[data-new-photo-wrap]');
        const fileName = input.closest('form')?.querySelector('[data-upload-name]');
        const dropZone = input.closest('[data-drop-zone]');
        const showPreview = (file) => {
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                input.value = '';
                if (fileName) fileName.textContent = 'File harus berupa gambar.';
                return;
            }
            if (preview) {
                if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
                preview.dataset.objectUrl = URL.createObjectURL(file);
                preview.src = preview.dataset.objectUrl;
            }
            if (previewWrap) previewWrap.hidden = false;
            const currentPreview = input.closest('form')?.querySelector('#current-photo-preview');
            if (currentPreview && input.dataset.editMode !== 'true') currentPreview.hidden = true;
            if (fileName) fileName.textContent = file.name;
        };

        input.dataset.editMode = input.closest('form')?.querySelector('#current-photo-preview') ? 'true' : 'false';
        input.addEventListener('change', () => showPreview(input.files?.[0]));
        if (dropZone) {
            ['dragenter', 'dragover'].forEach((eventName) => dropZone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropZone.classList.add('is-dragging');
            }));
            ['dragleave', 'drop'].forEach((eventName) => dropZone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropZone.classList.remove('is-dragging');
            }));
            dropZone.addEventListener('drop', (event) => {
                const file = event.dataTransfer?.files?.[0];
                if (!file) return;
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
                showPreview(file);
            });
        }
    });

    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.closest('.password-input-wrap')?.querySelector('input');
            if (!input) return;
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
            button.innerHTML = `<i class="bi ${visible ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                delete form.dataset.confirmed;
            } else {
                event.preventDefault();
                if (confirmModal && confirmModalElement) {
                    activeConfirmForm = form;
                    const message = confirmModalElement.querySelector('[data-confirm-message]');
                    if (message) message.textContent = form.dataset.confirm || 'Apakah Anda yakin ingin melanjutkan?';
                    confirmModal.show();
                } else if (window.confirm(form.dataset.confirm)) {
                    form.dataset.confirmed = 'true';
                    form.requestSubmit();
                    return;
                }
                return;
            }

            if (form.matches('[data-loading-form]')) setLoading(form);
        });
    });

    confirmModalElement?.querySelector('[data-confirm-submit]')?.addEventListener('click', () => {
        if (!activeConfirmForm) return;
        const form = activeConfirmForm;
        activeConfirmForm = null;
        form.dataset.confirmed = 'true';
        confirmModal?.hide();
        form.requestSubmit();
    });

    document.querySelectorAll('form[data-loading-form]:not([data-confirm])').forEach((form) => {
        form.addEventListener('submit', () => setLoading(form));
    });

    document.querySelectorAll('[data-auto-submit]').forEach((input) => {
        input.addEventListener('change', () => input.form?.requestSubmit());
    });

    document.querySelectorAll('[data-dismiss-toast]').forEach((button) => {
        button.addEventListener('click', () => dismissToast(button.closest('[data-toast]')));
    });
    document.querySelectorAll('[data-toast]').forEach((toast) => {
        window.setTimeout(() => dismissToast(toast), 4000);
    });

    document.querySelectorAll('[data-view-button]').forEach((button) => {
        button.addEventListener('click', () => {
            const view = button.dataset.viewButton;
            const grid = document.querySelector('[data-inventory-grid]');
            const table = document.querySelector('[data-inventory-table]');
            if (!grid || !table) return;
            grid.hidden = view !== 'grid';
            table.hidden = view !== 'table';
            document.querySelectorAll('[data-view-button]').forEach((option) => {
                const active = option === button;
                option.classList.toggle('active', active);
                option.setAttribute('aria-pressed', String(active));
            });
        });
    });
});

function setLoading(form) {
    const button = form.querySelector('button[type="submit"]');
    if (!button || button.disabled) return;
    button.disabled = true;
    button.classList.add('is-loading');
    const label = button.querySelector('[data-button-label]');
    if (label) label.textContent = 'Memproses...';
    else button.setAttribute('aria-label', 'Memproses...');
}

function dismissToast(toast) {
    if (!toast || toast.dataset.dismissed === 'true') return;
    toast.dataset.dismissed = 'true';
    toast.classList.add('toast-hiding');
    window.setTimeout(() => toast.remove(), 250);
}
