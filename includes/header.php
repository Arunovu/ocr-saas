<?php
/**
 * Global Header layout file
 */
require_once __DIR__ . '/../config.php';
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - OCR Image to Document Converter' : 'OCR Converter (SaaS Style)'; ?></title>
    <!-- Tailwind CSS v3 Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Cropper.js for image crop feature -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .transition-all-300 {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ── Toast Notification System ── */
        #toast-container {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            pointer-events: none;
        }
        .toast {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1.1rem;
            border-radius: 1rem;
            border: 1px solid;
            min-width: 280px;
            max-width: 380px;
            backdrop-filter: blur(12px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
            pointer-events: all;
            animation: toastIn 0.35s cubic-bezier(0.34,1.56,0.64,1) forwards;
            font-size: 0.8rem;
            font-weight: 500;
            line-height: 1.4;
        }
        .toast.hiding {
            animation: toastOut 0.3s ease forwards;
        }
        .toast-icon {
            flex-shrink: 0;
            width: 1.4rem;
            height: 1.4rem;
        }
        .toast-msg { flex-grow: 1; }
        .toast-close {
            flex-shrink: 0;
            width: 1.4rem;
            height: 1.4rem;
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.2s;
            background: none; border: none; padding: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .toast-close:hover { opacity: 1; }
        .toast-progress {
            position: absolute;
            bottom: 0; left: 0;
            height: 3px;
            border-radius: 0 0 0.5rem 0.5rem;
            animation: toastProgress linear forwards;
        }
        .toast-wrap { position: relative; overflow: hidden; }

        /* Toast color variants */
        .toast-success { background: rgba(20,20,20,0.92); border-color: rgba(245,158,11,0.4); color: #fde68a; }
        .toast-success .toast-progress { background: #F59E0B; }
        .toast-error   { background: rgba(20,20,20,0.92); border-color: rgba(239,68,68,0.4); color: #fca5a5; }
        .toast-error   .toast-progress { background: #ef4444; }
        .toast-info    { background: rgba(20,20,20,0.92); border-color: rgba(245,158,11,0.25); color: #fcd34d; }
        .toast-info    .toast-progress { background: #FBBF24; }
        .toast-warning { background: rgba(20,20,20,0.92); border-color: rgba(251,191,36,0.4); color: #fde68a; }
        .toast-warning .toast-progress { background: #FBBF24; }

        @keyframes toastIn {
            from { opacity:0; transform: translateX(60px) scale(0.92); }
            to   { opacity:1; transform: translateX(0)   scale(1);    }
        }
        @keyframes toastOut {
            from { opacity:1; transform: translateX(0)   scale(1); max-height: 100px; }
            to   { opacity:0; transform: translateX(60px) scale(0.88); max-height: 0; margin: 0; padding-top:0; padding-bottom:0; }
        }
        @keyframes toastProgress {
            from { width: 100%; }
            to   { width: 0%; }
        }

        /* ── Confirm / Alert Modal ── */
        #modal-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 10000;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(6px);
            align-items: center;
            justify-content: center;
        }
        #modal-overlay.active { display: flex; }
        #modal-box {
            background: #111;
            border: 1px solid rgba(245,158,11,0.25);
            border-radius: 1.25rem;
            padding: 2rem;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.7);
            animation: modalIn 0.3s cubic-bezier(0.34,1.56,0.64,1) forwards;
        }
        @keyframes modalIn {
            from { opacity:0; transform: scale(0.88) translateY(20px); }
            to   { opacity:1; transform: scale(1)    translateY(0);    }
        }
        #modal-icon { width:3rem; height:3rem; margin: 0 auto 1rem; display:block; }
        #modal-title { font-size:1rem; font-weight:700; color:#fff; text-align:center; margin-bottom:0.5rem; }
        #modal-message { font-size:0.8rem; color:#a3a3a3; text-align:center; margin-bottom:1.5rem; line-height:1.6; }
        .modal-btn {
            flex:1; padding:0.7rem 1rem; border-radius:0.75rem;
            font-size:0.8rem; font-weight:600; cursor:pointer;
            transition: all 0.2s; border: 1px solid transparent;
        }
        #modal-confirm-btn { background:#F59E0B; color:#000; border-color:#F59E0B; }
        #modal-confirm-btn:hover { background:#FBBF24; }
        #modal-cancel-btn { background:transparent; color:#a3a3a3; border-color:#333; }
        #modal-cancel-btn:hover { background:#1a1a1a; color:#fff; }

        /* ── Inactivity Warning ── */
        #inactivity-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 10001;
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
        }
        #inactivity-overlay.active { display: flex; }

        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #111; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #F59E0B; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:  '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            450: '#f9b813',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-[#0a0a0a] text-slate-100 min-h-screen flex flex-col selection:bg-brand-500/30 selection:text-brand-200">

    <!-- ── Toast Container ── -->
    <div id="toast-container"></div>

    <!-- ── Confirm / Alert Modal ── -->
    <div id="modal-overlay">
        <div id="modal-box">
            <svg id="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#F59E0B">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div id="modal-title">Konfirmasi</div>
            <div id="modal-message">Apakah Anda yakin?</div>
            <div style="display:flex; gap:0.75rem;">
                <button id="modal-cancel-btn" class="modal-btn">Batal</button>
                <button id="modal-confirm-btn" class="modal-btn">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>

    <!-- ── Inactivity Warning Modal ── -->
    <div id="inactivity-overlay">
        <div id="modal-box" style="border-color:rgba(245,158,11,0.5); max-width:380px;">
            <div style="text-align:center; margin-bottom:1rem;">
                <svg style="width:3rem;height:3rem;color:#F59E0B;margin:0 auto 0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div style="font-size:1rem;font-weight:700;color:#fff;margin-bottom:0.5rem;">Sesi Hampir Habis</div>
                <div style="font-size:0.8rem;color:#a3a3a3;line-height:1.6; margin-bottom:1.25rem;">
                    Anda tidak aktif selama beberapa waktu.<br>
                    Sesi akan berakhir dalam <strong id="inactivity-countdown" style="color:#F59E0B;">60</strong> detik.
                </div>
                <button id="inactivity-stay-btn" onclick="resetInactivityTimer()" style="background:#F59E0B;color:#000;border:none;border-radius:0.75rem;padding:0.7rem 2rem;font-weight:700;font-size:0.85rem;cursor:pointer;width:100%;">
                    Tetap Login
                </button>
            </div>
        </div>
    </div>

    <!-- Navigation Header -->
    <header class="sticky top-0 z-50 bg-[#0a0a0a]/90 backdrop-blur-md border-b border-yellow-900/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="<?php echo is_logged_in() ? 'index.php' : 'landing.php'; ?>" class="flex items-center gap-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-600 to-yellow-400 flex items-center justify-center shadow-lg shadow-amber-600/30 group-hover:scale-105 transition-all duration-300">
                            <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <span class="text-lg font-bold tracking-wide">
                            OCR<span class="text-amber-400 font-medium">SaaS</span>
                        </span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center gap-6">
                    <?php if (is_logged_in()): ?>
                        <a href="index.php" class="text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-amber-400' : 'text-slate-400 hover:text-white transition'; ?>">Dashboard</a>
                        <a href="history.php" class="text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'history.php' ? 'text-amber-400' : 'text-slate-400 hover:text-white transition'; ?>">Riwayat</a>
                    <?php else: ?>
                        <a href="landing.php#features" class="text-sm font-medium text-slate-400 hover:text-white transition">Fitur</a>
                        <a href="landing.php#faq" class="text-sm font-medium text-slate-400 hover:text-white transition">FAQ</a>
                    <?php endif; ?>
                </nav>

                <!-- Actions -->
                <div class="flex items-center gap-3">
                    <?php if (is_logged_in()): ?>
                        <span class="hidden sm:inline-block text-xs font-medium text-slate-400 bg-yellow-900/20 px-3 py-1.5 rounded-full border border-yellow-800/30">
                            Hi, <strong class="text-amber-300"><?php echo e($_SESSION['user_name']); ?></strong>
                        </span>
                        <a href="logout.php" class="text-xs font-semibold text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 px-4 py-2 rounded-xl border border-rose-500/20 transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-sm font-semibold text-slate-300 hover:text-white transition px-4 py-2">Login</a>
                        <a href="register.php" class="text-sm font-semibold text-black bg-amber-400 hover:bg-amber-300 px-4 py-2 rounded-xl shadow-lg shadow-amber-600/25 border border-amber-500/30 transition-all-300">
                            Daftar Sekarang
                        </a>
                    <?php endif; ?>

                    <!-- Mobile Menu Trigger -->
                    <button id="mobile-menu-btn" class="md:hidden p-2 text-slate-400 hover:text-white transition focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Panel -->
        <div id="mobile-menu" class="hidden md:hidden border-b border-yellow-900/30 bg-[#0a0a0a] px-4 py-3 space-y-2">
            <?php if (is_logged_in()): ?>
                <a href="index.php" class="block text-sm font-medium text-slate-300 hover:text-white py-2 rounded-lg">Dashboard</a>
                <a href="history.php" class="block text-sm font-medium text-slate-300 hover:text-white py-2 rounded-lg">Riwayat</a>
            <?php else: ?>
                <a href="landing.php#features" class="block text-sm font-medium text-slate-300 hover:text-white py-2 rounded-lg">Fitur</a>
                <a href="landing.php#faq" class="block text-sm font-medium text-slate-300 hover:text-white py-2 rounded-lg">FAQ</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-grow">
        <!-- Toast Flash Message from PHP -->
        <?php if ($flash): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showToast(<?php echo json_encode($flash['message']); ?>, <?php echo json_encode($flash['type']); ?>);
            });
        </script>
        <?php endif; ?>
