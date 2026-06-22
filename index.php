<?php
/**
 * Dashboard (index.php)
 */
require_once __DIR__ . '/config.php';

// Route protection
if (!is_logged_in()) {
    header("Location: landing.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$recent_uploads = [];
$stats = ['total' => 0, 'done' => 0, 'processing' => 0, 'failed' => 0];

try {
    $db = get_db_connection();
    
    // Fetch stats
    $stmt_stats = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM uploads 
        WHERE user_id = ?
    ");
    $stmt_stats->execute([$user_id]);
    $stats_res = $stmt_stats->fetch();
    if ($stats_res) {
        $stats['total'] = (int)$stats_res['total'];
        $stats['done'] = (int)$stats_res['done'];
        $stats['processing'] = (int)$stats_res['processing'];
        $stats['failed'] = (int)$stats_res['failed'];
    }

    // Fetch 5 most recent uploads
    $stmt_recent = $db->prepare("SELECT * FROM uploads WHERE user_id = ? ORDER BY id DESC LIMIT 5");
    $stmt_recent->execute([$user_id]);
    $recent_uploads = $stmt_recent->fetchAll();

} catch (PDOException $e) {
    // Database issue
    $error_msg = "Gagal memuat beberapa data dashboard.";
}

$page_title = "Dashboard";
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Welcome Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Selamat Datang, <?php echo e($_SESSION['user_name']); ?>!</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Kelola dan konversi gambar dokumen Anda dengan mudah dari dashboard ini.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-450 border border-emerald-500/20">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                Tesseract Ready
            </span>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Stat Item 1 -->
        <div class="bg-slate-900/40 border border-slate-800 rounded-2xl p-5 backdrop-blur-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-bold text-white"><?php echo $stats['total']; ?></div>
                <div class="text-[10px] sm:text-xs font-medium text-slate-400 uppercase tracking-wider mt-0.5">Total Upload</div>
            </div>
        </div>
        <!-- Stat Item 2 -->
        <div class="bg-slate-900/40 border border-slate-800 rounded-2xl p-5 backdrop-blur-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-bold text-white"><?php echo $stats['done']; ?></div>
                <div class="text-[10px] sm:text-xs font-medium text-slate-400 uppercase tracking-wider mt-0.5">Selesai OCR</div>
            </div>
        </div>
        <!-- Stat Item 3 -->
        <div class="bg-slate-900/40 border border-slate-800 rounded-2xl p-5 backdrop-blur-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-bold text-white"><?php echo $stats['processing']; ?></div>
                <div class="text-[10px] sm:text-xs font-medium text-slate-400 uppercase tracking-wider mt-0.5">Diproses</div>
            </div>
        </div>
        <!-- Stat Item 4 -->
        <div class="bg-slate-900/40 border border-slate-800 rounded-2xl p-5 backdrop-blur-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-bold text-white"><?php echo $stats['failed']; ?></div>
                <div class="text-[10px] sm:text-xs font-medium text-slate-400 uppercase tracking-wider mt-0.5">Gagal</div>
            </div>
        </div>
    </div>

    <!-- Main Work Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left 2 Cols: File Upload Card -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-md shadow-xl">
                <h3 class="text-lg font-bold text-white mb-2">Unggah Gambar Baru</h3>
                <p class="text-xs text-slate-455 mb-6">Mendukung format gambar JPEG & PNG. Maksimal ukuran berkas adalah 5MB.</p>

                <!-- Drag-and-Drop Area Form -->
                <form id="upload-form" method="POST" action="upload.php" enctype="multipart/form-data">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div id="drop-zone" class="border border-dashed border-slate-700/60 hover:border-brand-500/60 rounded-2xl p-8 sm:p-12 flex flex-col items-center justify-center bg-slate-950/20 hover:bg-slate-950/40 transition-all duration-300 cursor-pointer group">
                        <input type="file" id="file-input" name="image" accept="image/png, image/jpeg, image/jpg" class="hidden">
                        
                        <div class="w-16 h-16 rounded-2xl bg-brand-500/10 text-brand-400 flex items-center justify-center mb-6 group-hover:scale-105 transition-all duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        </div>
                        
                        <h4 class="text-sm font-semibold text-slate-200 text-center mb-1">
                            Pilih gambar, seret, atau tempel dari clipboard
                        </h4>
                        <p class="text-xs text-slate-500 text-center">PNG, JPG, JPEG sampai 5MB &nbsp;&bull;&nbsp; Tekan <kbd class="px-1.5 py-0.5 text-[10px] rounded bg-slate-700 border border-slate-600 text-slate-300 font-mono">Ctrl+V</kbd> untuk paste gambar</p>
                        
                        <!-- File Info Panel (Hidden by default) -->
                        <div id="file-info" class="hidden mt-6 px-4 py-2 rounded-xl bg-slate-800 border border-slate-700 items-center gap-3">
                            <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span id="file-name" class="text-xs font-semibold text-slate-300 truncate max-w-[200px]">nama_file.png</span>
                        </div>
                        
                        <!-- Hidden fields for cropping -->
                        <input type="hidden" name="original_filename" id="original-filename" value="">
                        <input type="hidden" name="cropped_image" id="cropped-image" value="">
                    </div>

                    <button type="submit" id="submit-btn" disabled class="w-full mt-6 py-3.5 bg-indigo-600 hover:bg-indigo-500 disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed disabled:border-slate-850 active:bg-indigo-700 transition rounded-2xl text-white font-semibold text-sm shadow-lg shadow-indigo-600/10 border border-indigo-500/25">
                        Mulai Proses Unggah & OCR
                    </button>
                </form>
            </div>
        </div>

        <!-- Crop Modal -->
        <div id="crop-modal" class="fixed inset-0 hidden bg-black bg-opacity-70 flex items-center justify-center z-50">
            <div class="bg-slate-900/80 rounded-2xl p-6 max-w-lg w-full">
                <h3 class="text-lg font-bold text-white mb-4">Crop Image</h3>
                <div class="relative w-full h-64">
                    <img id="crop-image" src="" alt="Crop preview" class="max-w-full max-h-full mx-auto" />
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button id="crop-cancel" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 rounded">Batal</button>
                    <button id="crop-confirm" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 rounded">Simpan Crop</button>
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Shortcut History -->
        <div class="space-y-6">
            <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 backdrop-blur-md shadow-xl flex flex-col h-full">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Unggahan Terakhir</h3>
                    <a href="history.php" class="text-xs font-semibold text-brand-450 hover:underline">Semua</a>
                </div>

                <div class="space-y-3.5 flex-grow">
                    <?php if (empty($recent_uploads)): ?>
                        <div class="py-12 text-center text-slate-500 flex flex-col items-center justify-center">
                            <svg class="w-8 h-8 text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            <p class="text-xs">Belum ada unggahan gambar.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_uploads as $upload): ?>
                            <?php
                            $status_badge = '';
                            if ($upload['status'] === 'uploaded') {
                                $status_badge = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700/60">Uploaded</span>';
                            } elseif ($upload['status'] === 'processing') {
                                $status_badge = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/25 animate-pulse">Processing</span>';
                            } elseif ($upload['status'] === 'done') {
                                $status_badge = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">Done</span>';
                            } else {
                                $status_badge = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/25">Failed</span>';
                            }
                            ?>
                            <div class="p-3.5 bg-slate-950/40 rounded-2xl border border-slate-800/80 flex items-center justify-between gap-3 hover:border-slate-750 transition">
                                <div class="min-w-0 flex-grow">
                                    <div class="text-xs font-semibold text-slate-200 truncate" title="<?php echo e($upload['original_name']); ?>">
                                        <?php echo e($upload['original_name']); ?>
                                    </div>
                                    <div class="text-[10px] text-slate-500 mt-1 flex items-center gap-1.5">
                                        <span><?php echo date('d M Y, H:i', strtotime($upload['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="shrink-0 flex flex-col items-end gap-1.5">
                                    <?php echo $status_badge; ?>
                                    <a href="<?php echo $upload['status'] === 'done' ? 'result.php?id=' . $upload['id'] : 'process.php?id=' . $upload['id']; ?>" class="text-[10px] font-bold text-brand-400 hover:underline">
                                        <?php echo $upload['status'] === 'done' ? 'Lihat' : 'Proses'; ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Frontend logic for Drag and Drop uploading
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const submitBtn = document.getElementById('submit-btn');

    // Open file selector on click
    dropZone.addEventListener('click', () => fileInput.click());

    // Drag over effect – allow dropping
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-brand-500/70', 'bg-slate-950/50');
    });

    // Drag leave effect – reset visual cue
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-brand-500/70', 'bg-slate-950/50');
    });

    // Drop handling – use DataTransfer to set file input and call the same selector logic
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-brand-500/70', 'bg-slate-950/50');
        const files = e.dataTransfer.files;
        if (files && files.length > 0) {
            // Populate hidden file input for fallback compatibility
            const dt = new DataTransfer();
            for (let i = 0; i < files.length; i++) {
                dt.items.add(files[i]);
            }
            fileInput.files = dt.files;
            // Run the same handling as file input change
            handleFileSelect();
        }
    });

    // File input change
    fileInput.addEventListener('change', handleFileSelect);

    // ── Clipboard Paste support ──────────────────────────────────────────────
    // Listen globally so Ctrl+V anywhere on the page works
    document.addEventListener('paste', (e) => {
        const items = (e.clipboardData || e.originalEvent?.clipboardData)?.items;
        if (!items) return;

        for (const item of items) {
            if (item.type.startsWith('image/')) {
                const file = item.getAsFile();
                if (!file) continue;

                // Basic size check (5 MB)
                if (file.size > 5 * 1024 * 1024) {
                    showToast('Ukuran file tidak boleh lebih dari 5MB!', 'error');
                    return;
                }

                // Inject the pasted file into the hidden file input
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;

                // Provide a generated filename since clipboard items have no name
                const ext = file.type === 'image/png' ? 'png' : 'jpg';
                const fakeName = 'clipboard_' + Date.now() + '.' + ext;

                // Flash the drop zone to give visual feedback
                dropZone.classList.add('border-brand-500/70', 'bg-slate-950/50');
                setTimeout(() => dropZone.classList.remove('border-brand-500/70', 'bg-slate-950/50'), 600);

                // Open crop modal directly
                const reader = new FileReader();
                reader.onload = function (ev) {
                    const previewImg = document.getElementById('crop-image');
                    previewImg.src = ev.target.result;
                    document.getElementById('original-filename').value = fakeName;
                    document.getElementById('crop-modal').classList.remove('hidden');
                    if (window.cropper) window.cropper.destroy();
                    window.cropper = new Cropper(previewImg, { viewMode: 1, autoCropArea: 1 });
                };
                reader.readAsDataURL(file);

                // Show the file-info badge
                fileName.textContent = fakeName;
                fileInfo.classList.remove('hidden');
                fileInfo.classList.add('flex');
                submitBtn.setAttribute('disabled', 'true');

                showToast('Gambar dari clipboard berhasil ditempel!', 'success');
                break; // only handle the first image item
            }
        }
    });
    // ────────────────────────────────────────────────────────────────────────

    function handleFileSelect() {
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];

            // Basic validation
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                showToast('Hanya format JPG dan PNG yang didukung!', 'error');
                fileInput.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showToast('Ukuran file tidak boleh lebih dari 5MB!', 'error');
                fileInput.value = '';
                return;
            }

            // Preview and crop
            const reader = new FileReader();
            reader.onload = function (e) {
                const previewImg = document.getElementById('crop-image');
                previewImg.src = e.target.result;
                document.getElementById('original-filename').value = file.name;
                document.getElementById('crop-modal').classList.remove('hidden');
                if (window.cropper) window.cropper.destroy();
                window.cropper = new Cropper(previewImg, { viewMode: 1, autoCropArea: 1 });
            };
            reader.readAsDataURL(file);

            fileName.textContent = file.name;
            fileInfo.classList.remove('hidden');
            fileInfo.classList.add('flex');
            // Enable submit button **only after** crop confirm; keep disabled now
            submitBtn.setAttribute('disabled', 'true'); // wait for crop confirmation
        } else {
            fileInfo.classList.add('hidden');
            fileInfo.classList.remove('flex');
            submitBtn.setAttribute('disabled', 'true');
        }
    }

    // Crop modal actions
    document.getElementById('crop-cancel').addEventListener('click', () => {
        document.getElementById('crop-modal').classList.add('hidden');
        fileInput.value = '';
        submitBtn.setAttribute('disabled', 'true');
    });

    document.getElementById('crop-confirm').addEventListener('click', () => {
        if (window.cropper) {
            const canvas = window.cropper.getCroppedCanvas();
            if (canvas) {
                const dataURL = canvas.toDataURL('image/jpeg');
                document.getElementById('cropped-image').value = dataURL;
                // Enable submit after successful crop
                submitBtn.removeAttribute('disabled');
                document.getElementById('crop-modal').classList.add('hidden');
            }
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
