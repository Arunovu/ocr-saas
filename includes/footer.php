<?php
/**
 * Global Footer layout file
 */
?>
    </main>

    <!-- Footer -->
    <footer class="bg-[#060606] border-t border-yellow-900/20 py-8 mt-12 text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center sm:text-left flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <p class="text-xs font-medium">&copy; <?php echo date('Y'); ?> OCRSaaS. All rights reserved.</p>
                <p class="text-[11px] mt-1 text-slate-600">Alat bantu OCR gambar ke teks dengan format PDF &amp; TXT instan.</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-medium">
                <a href="landing.php" class="hover:text-amber-400 transition">Beranda</a>
                <span class="w-1.5 h-1.5 rounded-full bg-yellow-900/50"></span>
                <a href="setup.php" class="hover:text-amber-400 transition text-amber-600">Setup Panel</a>
            </div>
        </div>
    </footer>

    <!-- ══════════════════════════════════════════
         GLOBAL JAVASCRIPT UTILITIES
         ══════════════════════════════════════════ -->
    <script>
    /* ─── Mobile Menu ─── */
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu    = document.getElementById('mobile-menu');
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
    }

    /* ══════════════════════════════════════════
       TOAST NOTIFICATION SYSTEM
       Usage: showToast('message', 'success'|'error'|'info'|'warning')
       ══════════════════════════════════════════ */
    const TOAST_DURATION = 4500; // ms

    const TOAST_ICONS = {
        success: `<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#F59E0B"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        error:   `<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#ef4444"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        warning: `<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#FBBF24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`,
        info:    `<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#FBBF24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    };

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const wrap = document.createElement('div');
        wrap.className = 'toast-wrap';

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            ${TOAST_ICONS[type] || TOAST_ICONS.info}
            <span class="toast-msg">${message}</span>
            <button class="toast-close" aria-label="Tutup">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="toast-progress" style="animation-duration:${TOAST_DURATION}ms"></div>
        `;

        wrap.appendChild(toast);
        container.appendChild(wrap);

        const closeBtn = toast.querySelector('.toast-close');
        const dismiss = () => {
            toast.classList.add('hiding');
            setTimeout(() => wrap.remove(), 320);
        };
        closeBtn.addEventListener('click', dismiss);

        // Auto-dismiss
        const timer = setTimeout(dismiss, TOAST_DURATION);
        closeBtn.addEventListener('click', () => clearTimeout(timer));
    }

    /* ══════════════════════════════════════════
       CUSTOM CONFIRM MODAL
       Usage: showConfirm('message', () => { onConfirm }, 'Title (optional)')
       ══════════════════════════════════════════ */
    let _confirmCallback = null;

    function showConfirm(message, onConfirm, title = 'Konfirmasi Tindakan') {
        const overlay     = document.getElementById('modal-overlay');
        const titleEl     = document.getElementById('modal-title');
        const messageEl   = document.getElementById('modal-message');
        const confirmBtn  = document.getElementById('modal-confirm-btn');
        const cancelBtn   = document.getElementById('modal-cancel-btn');
        const iconEl      = document.getElementById('modal-icon');

        titleEl.textContent   = title;
        messageEl.textContent = message;
        iconEl.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>`;
        iconEl.style.color    = '#F59E0B';

        _confirmCallback = onConfirm;
        overlay.classList.add('active');
    }

    function showAlert(message, title = 'Informasi', type = 'info') {
        const overlay     = document.getElementById('modal-overlay');
        const titleEl     = document.getElementById('modal-title');
        const messageEl   = document.getElementById('modal-message');
        const confirmBtn  = document.getElementById('modal-confirm-btn');
        const cancelBtn   = document.getElementById('modal-cancel-btn');
        const iconEl      = document.getElementById('modal-icon');

        const colors = { info: '#F59E0B', error: '#ef4444', success: '#F59E0B', warning: '#FBBF24' };

        titleEl.textContent   = title;
        messageEl.textContent = message;
        iconEl.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>`;
        iconEl.style.color    = colors[type] || '#F59E0B';

        cancelBtn.style.display   = 'none';
        confirmBtn.textContent    = 'OK';
        _confirmCallback = null;
        overlay.classList.add('active');
    }

    // Modal button handlers
    document.addEventListener('DOMContentLoaded', function() {
        const overlay    = document.getElementById('modal-overlay');
        const confirmBtn = document.getElementById('modal-confirm-btn');
        const cancelBtn  = document.getElementById('modal-cancel-btn');

        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                overlay.classList.remove('active');
                cancelBtn.style.display = '';
                confirmBtn.textContent  = 'Ya, Lanjutkan';
                if (_confirmCallback) _confirmCallback();
                _confirmCallback = null;
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                overlay.classList.remove('active');
                cancelBtn.style.display = '';
                confirmBtn.textContent  = 'Ya, Lanjutkan';
                _confirmCallback = null;
            });
        }
        if (overlay) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                    cancelBtn.style.display = '';
                    confirmBtn.textContent  = 'Ya, Lanjutkan';
                    _confirmCallback = null;
                }
            });
        }
    });

    /* ══════════════════════════════════════════
       SESSION INACTIVITY TIMEOUT
       Warning after 14 min idle, logout at 15 min
       ══════════════════════════════════════════ */
    <?php if (is_logged_in()): ?>
    (function() {
        const IDLE_WARN_MS   = 14 * 60 * 1000;  // 14 minutes
        const IDLE_LOGOUT_MS = 15 * 60 * 1000;  // 15 minutes
        const COUNTDOWN_SEC  = 60;               // 60s warning countdown

        let idleWarnTimer   = null;
        let idleLogoutTimer = null;
        let countdownTimer  = null;
        let countdownSec    = COUNTDOWN_SEC;

        const inactivityOverlay    = document.getElementById('inactivity-overlay');
        const countdownEl          = document.getElementById('inactivity-countdown');
        const stayBtn              = document.getElementById('inactivity-stay-btn');

        function startCountdown() {
            countdownSec = COUNTDOWN_SEC;
            if (countdownEl) countdownEl.textContent = countdownSec;
            countdownTimer = setInterval(() => {
                countdownSec--;
                if (countdownEl) countdownEl.textContent = countdownSec;
                if (countdownSec <= 0) {
                    clearInterval(countdownTimer);
                    window.location.href = 'logout.php?reason=inactivity';
                }
            }, 1000);
        }

        function showInactivityWarning() {
            if (inactivityOverlay) inactivityOverlay.classList.add('active');
            startCountdown();
            idleLogoutTimer = setTimeout(() => {
                window.location.href = 'logout.php?reason=inactivity';
            }, COUNTDOWN_SEC * 1000);
        }

        window.resetInactivityTimer = function() {
            clearTimeout(idleWarnTimer);
            clearTimeout(idleLogoutTimer);
            clearInterval(countdownTimer);
            if (inactivityOverlay) inactivityOverlay.classList.remove('active');
            // Ping server to reset session
            fetch('logout.php?ping=1').catch(() => {});
            idleWarnTimer = setTimeout(showInactivityWarning, IDLE_WARN_MS);
        };

        // Activity events that reset the timer
        ['mousemove','keydown','click','scroll','touchstart'].forEach(evt => {
            document.addEventListener(evt, () => {
                if (inactivityOverlay && !inactivityOverlay.classList.contains('active')) {
                    clearTimeout(idleWarnTimer);
                    idleWarnTimer = setTimeout(showInactivityWarning, IDLE_WARN_MS);
                }
            }, { passive: true });
        });

        // Start the timer on page load
        idleWarnTimer = setTimeout(showInactivityWarning, IDLE_WARN_MS);
    })();
    <?php endif; ?>

    </script>
</body>
</html>
