/**
 * assets/js/main.js
 * JavaScript Interaktivitas UI EduAccess
 */

document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------
    // 1. Sidebar Toggle (Mobile & Responsive)
    // -------------------------------------------------------------
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const appSidebar = document.getElementById('appSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggleBtn && appSidebar) {
        sidebarToggleBtn.addEventListener('click', function () {
            appSidebar.classList.toggle('show');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('show');
            }
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            appSidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }

    // -------------------------------------------------------------
    // 2. Toggle Show/Hide Password
    // -------------------------------------------------------------
    const togglePasswordBtns = document.querySelectorAll('.btn-toggle-password');
    togglePasswordBtns.forEach(button => {
        button.addEventListener('click', function () {
            const targetInputId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetInputId);
            const icon = this.querySelector('i');

            if (targetInput) {
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    if (icon) {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    }
                } else {
                    targetInput.type = 'password';
                    if (icon) {
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                }
            }
        });
    });

    // -------------------------------------------------------------
    // 3. Demo Account Auto-Fill Helper (Halaman Login)
    // -------------------------------------------------------------
    const demoButtons = document.querySelectorAll('.btn-demo-fill');
    demoButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const username = this.getAttribute('data-username');
            const password = this.getAttribute('data-password');

            const userInput = document.getElementById('username');
            const passInput = document.getElementById('password');

            if (userInput && passInput) {
                userInput.value = username;
                passInput.value = password;
                
                // Visual feedback
                userInput.focus();
                passInput.focus();
                
                // Highlight input
                userInput.classList.add('is-valid');
                passInput.classList.add('is-valid');
                setTimeout(() => {
                    userInput.classList.remove('is-valid');
                    passInput.classList.remove('is-valid');
                }, 1500);
            }
        });
    });

    // -------------------------------------------------------------
    // 4. Inisialisasi Tooltip Bootstrap (jika ada)
    // -------------------------------------------------------------
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // -------------------------------------------------------------
    // 5. Konfirmasi Tindakan Berbahaya (Hapus Data)
    // -------------------------------------------------------------
    const confirmDeleteBtns = document.querySelectorAll('.btn-confirm-delete');
    confirmDeleteBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});
