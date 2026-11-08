document.addEventListener('DOMContentLoaded', () => {
    // Sidebar mobile toggle
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const toggleBtn = document.querySelector('.admin-mobile-toggle');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay?.classList.toggle('active');
        });

        overlay?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // Modal open/close
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.modalOpen);
            if (target) {
                target.classList.add('active');
                document.body.style.overflow = 'hidden';

                // If editing, populate form
                if (btn.dataset.editId) {
                    populateEditForm(btn);
                } else {
                    // Clear form for "add new"
                    const form = target.querySelector('form');
                    if (form) form.reset();
                    const hiddenId = target.querySelector('input[name="edit_id"]');
                    if (hiddenId) hiddenId.value = '';
                    const title = target.querySelector('.admin-modal-header h3');
                    if (title && btn.dataset.addTitle) title.textContent = btn.dataset.addTitle;
                }
            }
        });
    });

    document.querySelectorAll('.admin-modal-close, .admin-modal-overlay').forEach(el => {
        el.addEventListener('click', (e) => {
            if (e.target === el) {
                const modal = el.closest('.admin-modal-overlay') || el;
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Populate edit form from data attributes
    function populateEditForm(btn) {
        const modal = document.getElementById(btn.dataset.modalOpen);
        if (!modal) return;

        const title = modal.querySelector('.admin-modal-header h3');
        if (title && btn.dataset.editTitle) title.textContent = btn.dataset.editTitle;

        const hiddenId = modal.querySelector('input[name="edit_id"]');
        if (hiddenId) hiddenId.value = btn.dataset.editId;

        // Populate all data-field-* attributes
        Array.from(btn.attributes).forEach(attr => {
            if (attr.name.startsWith('data-field-')) {
                const fieldName = attr.name.substring(11); // remove 'data-field-'
                const input = modal.querySelector(`[name="${fieldName}"]`);
                if (input) {
                    input.value = attr.value;
                }
            }
        });
    }

    // Delete confirmation
    document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.admin-alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
