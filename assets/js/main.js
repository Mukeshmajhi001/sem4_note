// =============================================
// NoteVault — main.js
// Theme toggle, search, PDF modal, file upload
// =============================================

document.addEventListener('DOMContentLoaded', function () {

    // ── Theme Toggle ──────────────────────────
    const themeToggle = document.getElementById('themeToggle');
    const saved = localStorage.getItem('nv_theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    setIcon(saved);

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const cur  = document.documentElement.getAttribute('data-theme');
            const next = cur === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('nv_theme', next);
            setIcon(next);
        });
    }
    function setIcon(theme) {
        if (themeToggle) {
            themeToggle.textContent = theme === 'dark' ? '☀️' : '🌙';
            themeToggle.title = theme === 'dark' ? 'Switch to Day Mode' : 'Switch to Night Mode';
        }
    }

    // ── Live Search — Folders ─────────────────
    const folderSearch = document.getElementById('folderSearch');
    if (folderSearch) {
        folderSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.folder-card').forEach(card => {
                const name = card.querySelector('.folder-name').textContent.toLowerCase();
                card.style.display = name.includes(q) ? '' : 'none';
            });
        });
    }

    // ── Live Search — Files ───────────────────
    const fileSearch = document.getElementById('fileSearch');
    if (fileSearch) {
        fileSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.file-card').forEach(card => {
                const title = card.querySelector('.file-title').textContent.toLowerCase();
                const desc  = (card.querySelector('.file-desc')?.textContent || '').toLowerCase();
                const wrap  = card.closest('.file-card-wrap') || card.parentElement;
                wrap.style.display = (title.includes(q) || desc.includes(q)) ? '' : 'none';
            });
        });
    }

    // ── PDF Viewer Modal ──────────────────────
    const modal      = document.getElementById('pdfModal');
    const modalTitle = document.getElementById('modalTitle');
    const pdfFrame   = document.getElementById('pdfFrame');
    const modalClose = document.getElementById('modalClose');

    window.openPDF = function (url, title) {
        if (!modal) return;
        modalTitle.textContent = title || 'PDF Viewer';
        // Use Google Docs viewer for external URLs
        const isExternal = url.startsWith('http') && !url.includes(window.location.hostname);
        pdfFrame.src = isExternal
            ? 'https://docs.google.com/viewer?url=' + encodeURIComponent(url) + '&embedded=true'
            : url;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modal) modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => { if (pdfFrame) pdfFrame.src = ''; }, 300);
    }

    // ── Admin: Upload Type Selector ───────────
    function toggleUploadFields(type) {
        const pdf  = document.getElementById('pdfSection');
        const link = document.getElementById('linkSection');
        if (!pdf || !link) return;
        pdf.style.display  = (type === 'pdf'  || type === 'both') ? 'block' : 'none';
        link.style.display = (type === 'link' || type === 'both') ? 'block' : 'none';
    }
    document.querySelectorAll('.type-option').forEach(inp => {
        inp.addEventListener('change', () => toggleUploadFields(inp.value));
    });
    const activeType = document.querySelector('.type-option:checked');
    if (activeType) toggleUploadFields(activeType.value);

    // ── Auto-hide Alerts ──────────────────────
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 4000);
    });

    // ── Drag & Drop File Upload ───────────────
    const uploadArea = document.querySelector('.file-upload-area');
    if (uploadArea) {
        uploadArea.addEventListener('dragover', e => {
            e.preventDefault();
            uploadArea.style.boxShadow = 'var(--neu-inset), 0 0 0 2px var(--accent)';
        });
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.style.boxShadow = '';
        });
        uploadArea.addEventListener('drop', e => {
            e.preventDefault();
            uploadArea.style.boxShadow = '';
            const input = uploadArea.querySelector('input[type="file"]');
            if (input && e.dataTransfer.files.length) {
                // Can't set input.files directly in all browsers; show name only
                uploadArea.querySelector('.file-upload-text').textContent =
                    '📄 ' + e.dataTransfer.files[0].name;
            }
        });
        const fi = uploadArea.querySelector('input[type="file"]');
        if (fi) {
            fi.addEventListener('change', function () {
                if (this.files.length) {
                    uploadArea.querySelector('.file-upload-text').textContent =
                        '📄 ' + this.files[0].name;
                }
            });
        }
    }

});
