<?php
/**
 * Visual OCR Processing Page (process.php)
 * Renders a premium SaaS-style scanner visual and runs AJAX request to execute OCR.
 */
require_once __DIR__ . '/config.php';

// Route protection
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

try {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM uploads WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $upload = $stmt->fetch();
    
    if (!$upload) {
        set_flash_message('error', 'Dokumen tidak ditemukan.');
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    set_flash_message('error', 'Koneksi database terganggu.');
    header("Location: index.php");
    exit;
}

$page_title = "Memproses OCR Gambar";
require_once __DIR__ . '/includes/header.php';
?>

<style>
    /* Scanner laser animation effect */
    @keyframes scan {
        0%, 100% {
            top: 0%;
        }
        50% {
            top: 100%;
        }
    }
    .scan-laser {
        animation: scan 3s ease-in-out infinite;
    }
</style>

<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-8 backdrop-blur-md shadow-2xl text-center">
        
        <!-- Status Header -->
        <div class="mb-8">
            <h2 id="status-title" class="text-xl sm:text-2xl font-bold text-white mb-2">Memulai Analisis Dokumen...</h2>
            <p id="status-subtitle" class="text-xs sm:text-sm text-slate-400">Harap tunggu sementara engine kami memindai gambar Anda.</p>
        </div>

        <!-- Scanner Preview Container -->
        <div class="relative max-w-sm mx-auto aspect-video rounded-2xl overflow-hidden border border-slate-700 bg-slate-950 mb-8 flex items-center justify-center">
            <!-- Uploaded Image -->
            <img src="<?php echo e($upload['file_path']); ?>" alt="Preview" class="max-w-full max-h-full object-contain opacity-40">
            
            <!-- Laser Line -->
            <div class="scan-laser absolute left-0 right-0 h-1 bg-gradient-to-r from-transparent via-brand-400 to-transparent shadow-[0_0_15px_#6366f1] pointer-events-none"></div>
            
            <!-- Floating overlay status badge -->
            <div class="absolute bottom-3 right-3 px-2.5 py-1 bg-slate-900/80 border border-slate-700/60 rounded-lg text-[10px] font-bold text-brand-300">
                Tesseract Core v3
            </div>
        </div>

        <!-- Console Log Output -->
        <div class="max-w-md mx-auto bg-slate-950/80 border border-slate-800 rounded-xl p-4 text-left font-mono text-xs text-indigo-300/85 mb-8 h-36 overflow-y-auto space-y-1.5 scrollbar-thin">
            <div id="log-1" class="flex items-center gap-2">
                <span class="text-emerald-400">●</span>
                <span>[<?php echo date('H:i:s'); ?>] File ditemukan: <?php echo e($upload['original_name']); ?></span>
            </div>
            <div id="log-2" class="hidden flex items-center gap-2">
                <span class="text-brand-400 animate-pulse">●</span>
                <span>[<?php echo date('H:i:s'); ?>] Menginisialisasi engine Tesseract OCR...</span>
            </div>
            <div id="log-3" class="hidden flex items-center gap-2">
                <span class="text-brand-400 animate-pulse">●</span>
                <span>[<?php echo date('H:i:s'); ?>] Menjalankan pemindaian eng+ind...</span>
            </div>
            <div id="log-4" class="hidden flex items-center gap-2">
                <span class="text-emerald-400">●</span>
                <span>[<?php echo date('H:i:s'); ?>] Menyimpan hasil ekstraksi...</span>
            </div>
            <div id="log-err" class="hidden flex items-center gap-2 text-rose-450">
                <span>✕</span>
                <span id="log-err-msg">Proses gagal.</span>
            </div>
        </div>

        <!-- Progress Indicator -->
        <div class="max-w-xs mx-auto mb-8">
            <div class="w-full bg-slate-850 h-2 rounded-full overflow-hidden border border-slate-800">
                <div id="progress-bar" class="bg-gradient-to-r from-brand-600 to-indigo-400 h-full w-[10%] transition-all duration-500"></div>
            </div>
        </div>

        <!-- Back Button (hidden during loading, shown on error) -->
        <div id="action-area" class="hidden">
            <a href="index.php" class="px-6 py-2.5 bg-slate-800 border border-slate-700 hover:bg-slate-750 transition rounded-xl text-slate-300 font-semibold text-sm">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const log2 = document.getElementById('log-2');
        const log3 = document.getElementById('log-3');
        const log4 = document.getElementById('log-4');
        const logErr = document.getElementById('log-err');
        const logErrMsg = document.getElementById('log-err-msg');
        
        const progressBar = document.getElementById('progress-bar');
        const statusTitle = document.getElementById('status-title');
        const statusSubtitle = document.getElementById('status-subtitle');
        const actionArea = document.getElementById('action-area');

        // Stage 1: Init Engine visual log
        setTimeout(() => {
            log2.classList.remove('hidden');
            progressBar.style.width = '35%';
            statusTitle.textContent = "Menginisialisasi Engine...";
        }, 800);

        // Stage 2: Scanning visual log
        setTimeout(() => {
            log3.classList.remove('hidden');
            progressBar.style.width = '65%';
            statusTitle.textContent = "Sedang Memindai Gambar...";
            statusSubtitle.textContent = "Tesseract mendeteksi karakter bahasa Inggris & Indonesia.";
            
            // Trigger actual OCR call
            startOCR();
        }, 1800);

        function startOCR() {
            fetch('process_trigger.php?id=<?php echo $id; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        log4.classList.remove('hidden');
                        progressBar.style.width = '100%';
                        statusTitle.textContent = "Pemindaian Selesai!";
                        statusSubtitle.textContent = "Mengarahkan ke halaman hasil...";
                        
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1200);
                    } else {
                        handleError(data.error);
                    }
                })
                .catch(err => {
                    handleError("Terjadi error koneksi server atau request timeout.");
                });
        }

        function handleError(message) {
            progressBar.classList.remove('bg-gradient-to-r', 'from-brand-600', 'to-indigo-400');
            progressBar.classList.add('bg-rose-600');
            progressBar.style.width = '100%';
            
            logErr.classList.remove('hidden');
            logErrMsg.textContent = `[Error] ${message}`;
            
            statusTitle.textContent = "Pemindaian Gagal";
            statusTitle.classList.add('text-rose-455');
            statusSubtitle.textContent = "Gagal memproses gambar melalui Tesseract OCR.";
            
            actionArea.classList.remove('hidden');
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
