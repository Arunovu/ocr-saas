<?php
/**
 * Result and Editor Page (result.php)
 * Shows the original image and allows viewing/editing of the extracted OCR text.
 */
require_once __DIR__ . '/config.php';

// Route protection
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

try {
    $db = get_db_connection();
    
    // Fetch upload detail
    $stmt = $db->prepare("SELECT * FROM uploads WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $upload = $stmt->fetch();
    
    if (!$upload) {
        set_flash_message('error', 'Dokumen tidak ditemukan.');
        header("Location: index.php");
        exit;
    }

    // Handle updates to the text
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($csrf_token)) {
            $errors[] = "Token CSRF tidak valid.";
        } else {
            $updated_text = $_POST['ocr_text'] ?? '';
            
            // Save updated text
            $stmt_update = $db->prepare("UPDATE uploads SET ocr_text = ? WHERE id = ?");
            $stmt_update->execute([$updated_text, $id]);
            
            // Update local variable
            $upload['ocr_text'] = $updated_text;
            set_flash_message('success', 'Perubahan teks berhasil disimpan.');
            $success = true;
        }
    }
} catch (PDOException $e) {
    $errors[] = "Masalah database: " . $e->getMessage();
}

$page_title = "Hasil OCR - " . $upload['original_name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Page Header & Action Bar -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800/80 pb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-white flex items-center gap-2">
                <span class="truncate"><?php echo e($upload['original_name']); ?></span>
            </h1>
            <p class="text-xs text-slate-400 mt-1 flex items-center gap-1.5">
                <span>Diupload pada: <?php echo date('d M Y, H:i', strtotime($upload['created_at'])); ?></span>
                <span class="w-1.5 h-1.5 bg-slate-850 rounded-full"></span>
                <span>Status:</span>
                <?php if ($upload['status'] === 'done'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">Done</span>
                <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/25"><?php echo e($upload['status']); ?></span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Export Buttons -->
        <div class="flex items-center gap-3 shrink-0">
            <a href="download.php?id=<?php echo $id; ?>&format=txt" class="text-xs font-semibold text-slate-350 bg-slate-800 hover:bg-slate-750 hover:text-white px-4 py-2.5 rounded-xl border border-slate-700/60 transition flex items-center gap-1.5 shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Ekspor TXT
            </a>
            <div class="flex items-center gap-3 text-xs">
        <label class="text-slate-300" for="pdf-size">Ukuran:</label>
        <select id="pdf-size" class="bg-slate-800 border border-slate-700 text-slate-200 rounded px-2 py-1">
            <option value="a4" selected>A4</option>
            <option value="letter">Letter</option>
            <option value="legal">Legal</option>
        </select>
        <label class="text-slate-300" for="pdf-orientation">Orientasi:</label>
        <select id="pdf-orientation" class="bg-slate-800 border border-slate-700 text-slate-200 rounded px-2 py-1">
            <option value="portrait" selected>Portrait</option>
            <option value="landscape">Landscape</option>
        </select>
        <label class="text-slate-300" for="pdf-fontsize">Ukuran Font:</label>
        <input type="number" id="pdf-fontsize" min="8" max="20" value="11" class="w-12 bg-slate-800 border border-slate-700 text-slate-200 rounded px-1 py-1" />
        <button id="pdf-export-btn" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 rounded text-white text-xs">Ekspor PDF</button>
    </div>
    <script>
        document.getElementById('pdf-export-btn').addEventListener('click', () => {
            const size = document.getElementById('pdf-size').value;
            const orientation = document.getElementById('pdf-orientation').value;
            const fontsize = document.getElementById('pdf-fontsize').value;
            const url = `download.php?id=<?php echo $id; ?>&format=pdf&size=${size}&orientation=${orientation}&fontsize=${fontsize}`;
            window.location.href = url;
        });
    </script>
        </div>
    </div>

    <!-- Error Callout -->
    <?php if (!empty($errors)): ?>
        <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-350 text-xs flex flex-col gap-1.5">
            <span class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Kesalahan:
            </span>
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Two-column workspace layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
        
        <!-- Left Side: Original Image Card -->
        <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-5 sm:p-6 backdrop-blur-md shadow-xl">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-850">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Gambar Sumber</h3>
                <span class="text-xs text-slate-500">Pratinjau Asli</span>
            </div>
            
            <div class="rounded-2xl overflow-hidden bg-slate-950/80 border border-slate-800 p-4 flex items-center justify-center min-h-[300px] max-h-[500px]">
                <img id="source-image" src="<?php echo e($upload['file_path']); ?>" alt="Original Source" class="max-w-full max-h-[400px] object-contain transition-all duration-300">
            </div>
        </div>

        <!-- Right Side: Interactive Text Editor Card -->
        <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-5 sm:p-6 backdrop-blur-md shadow-xl">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-850">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Hasil Teks OCR</h3>
                <span class="text-xs text-slate-500">Dapat diedit langsung</span>
            </div>

            <?php if ($upload['status'] === 'failed'): ?>
                <div class="p-8 text-center text-rose-350 bg-rose-500/10 border border-rose-500/20 rounded-2xl">
                    <svg class="w-10 h-10 text-rose-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <p class="font-bold text-sm">Ekstraksi Teks Gagal</p>
                    <p class="text-xs mt-1 text-slate-400">Silakan ulangi proses dengan mengunggah gambar yang memiliki teks lebih jelas.</p>
                    <a href="process.php?id=<?php echo $id; ?>" class="inline-block mt-4 text-xs font-semibold text-white bg-indigo-650 hover:bg-indigo-500 px-4 py-2 rounded-xl transition">Proses Ulang</a>
                </div>
            <?php elseif ($upload['status'] === 'uploaded' || $upload['status'] === 'processing'): ?>
                <div class="p-8 text-center text-slate-400 bg-slate-950/20 border border-slate-800 rounded-2xl">
                    <div class="w-8 h-8 rounded-full border-2 border-brand-400 border-t-transparent animate-spin mx-auto mb-3"></div>
                    <p class="font-bold text-sm text-slate-200">Sedang diproses...</p>
                    <p class="text-xs mt-1 text-slate-500">Halaman akan memuat secara dinamis setelah proses OCR selesai.</p>
                    <a href="process.php?id=<?php echo $id; ?>" class="inline-block mt-4 text-xs font-semibold text-brand-400 hover:underline">Pantau Status Proses</a>
                </div>
            <?php else: ?>
                <form method="POST" action="result.php?id=<?php echo $id; ?>" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <textarea name="ocr_text" id="ocr-editor" rows="14" placeholder="Hasil teks OCR akan ditampilkan di sini..."
                              class="w-full bg-slate-950/80 border border-slate-800 focus:border-brand-500/60 focus:ring-1 focus:ring-brand-500/30 rounded-2xl p-4 text-slate-200 focus:outline-none transition font-mono text-sm leading-relaxed scrollbar-thin"><?php echo e($upload['ocr_text']); ?></textarea>
                    <div class="flex justify-between items-center pt-2">
                        <span id="save-status" class="text-xs text-slate-500 italic">
                            <?php if ($success): ?>
                                <span class="text-emerald-450 font-medium">✓ Perubahan disimpan ke DB</span>
                            <?php else: ?>
                                Teks disinkronisasi ke DB
                            <?php endif; ?>
                        </span>
                        <div class="flex gap-2">
                            <button type="button" id="copy-btn" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-xl text-white text-xs font-semibold">Salin</button>
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 transition rounded-xl text-white font-semibold text-xs shadow-md shadow-indigo-600/15 border border-indigo-500/30">Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Automatically hide success text after 3 seconds
    const statusText = document.getElementById('save-status');
    if (statusText && statusText.textContent.includes('✓')) {
        setTimeout(() => {
            statusText.innerHTML = 'Teks disinkronisasi ke DB';
        }, 3000);
    }

    // Copy to clipboard functionality
    const copyBtn = document.getElementById('copy-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            const ocrEditor = document.getElementById('ocr-editor');
            if (ocrEditor) {
                navigator.clipboard.writeText(ocrEditor.value)
                    .then(() => {
                        // Assuming showToast exists for notifications
                        if (typeof showToast === 'function') {
                            showToast('Teks disalin ke clipboard!', 'success');
                        }
                    })
                    .catch(() => {
                        if (typeof showToast === 'function') {
                            showToast('Gagal menyalin teks.', 'error');
                        }
                    });
            }
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
