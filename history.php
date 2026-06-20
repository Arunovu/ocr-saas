<?php
/**
 * History List Page (history.php)
 * Displays a list of all documents uploaded by the user with status badges and download shortcuts.
 */
require_once __DIR__ . '/config.php';

// Route protection
require_login();

$user_id = $_SESSION['user_id'];
$errors = [];

try {
    $db = get_db_connection();

    // Secure DELETE action handling
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($csrf_token)) {
            set_flash_message('error', 'Token CSRF tidak valid.');
        } else {
            $delete_id = (int)$_POST['delete_id'];
            
            // Get upload details to delete file
            $stmt_file = $db->prepare("SELECT * FROM uploads WHERE id = ? AND user_id = ?");
            $stmt_file->execute([$delete_id, $user_id]);
            $upload_rec = $stmt_file->fetch();
            
            if ($upload_rec) {
                // Delete physical image file
                $full_img_path = BASE_DIR . $upload_rec['file_path'];
                if (file_exists($full_img_path)) {
                    unlink($full_img_path);
                }
                
                // Delete database entry
                $stmt_del = $db->prepare("DELETE FROM uploads WHERE id = ? AND user_id = ?");
                $stmt_del->execute([$delete_id, $user_id]);
                
                set_flash_message('success', 'Riwayat berkas berhasil dihapus secara permanen.');
                header("Location: history.php");
                exit;
            } else {
                set_flash_message('error', 'Berkas tidak ditemukan atau Anda tidak berwenang.');
            }
        }
    }

    // Fetch all uploads
    $stmt = $db->prepare("SELECT * FROM uploads WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$user_id]);
    $uploads = $stmt->fetchAll();

} catch (PDOException $e) {
    $errors[] = "Masalah database: " . $e->getMessage();
    $uploads = [];
}

$page_title = "Riwayat Pemindaian OCR";
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-white">Riwayat Pemindaian</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Daftar berkas yang pernah Anda unggah dan hasil konversinya.</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-600/20 transition-all-300">
            Unggah Baru
        </a>
    </div>

    <!-- Error Banner -->
    <?php if (!empty($errors)): ?>
        <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- History list table -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-3xl overflow-hidden backdrop-blur-md shadow-xl">
        <?php if (empty($uploads)): ?>
            <div class="py-20 text-center text-slate-500 flex flex-col items-center justify-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-800/40 text-slate-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-400">Belum Ada Riwayat OCR</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-sm">Semua gambar yang Anda unggah beserta teks hasil pemindaian akan terdaftar di sini.</p>
                <a href="index.php" class="mt-4 px-4 py-2 border border-slate-700 hover:bg-slate-800 rounded-xl text-xs font-semibold text-slate-350 transition">Unggah Gambar Pertama</a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-850 text-slate-400 font-semibold text-xs uppercase tracking-wider bg-slate-950/20">
                            <th class="py-4.5 px-6">Nama File</th>
                            <th class="py-4.5 px-6">Status</th>
                            <th class="py-4.5 px-6">Tanggal Upload</th>
                            <th class="py-4.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-850">
                        <?php foreach ($uploads as $upload): ?>
                            <?php
                            $status_badge = '';
                            if ($upload['status'] === 'uploaded') {
                                $status_badge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-850 text-slate-400 border border-slate-750">Uploaded</span>';
                            } elseif ($upload['status'] === 'processing') {
                                $status_badge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/25 animate-pulse">Processing</span>';
                            } elseif ($upload['status'] === 'done') {
                                $status_badge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">Done</span>';
                            } else {
                                $status_badge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/25">Failed</span>';
                            }
                            ?>
                            <tr class="hover:bg-slate-950/20 transition">
                                <td class="py-4 px-6 font-medium text-slate-200">
                                    <div class="flex items-center gap-3 max-w-[250px] sm:max-w-[400px]">
                                        <!-- Miniature preview -->
                                        <div class="w-8 h-8 rounded-lg overflow-hidden border border-slate-800 bg-slate-950 shrink-0 flex items-center justify-center">
                                            <img src="<?php echo e($upload['file_path']); ?>" alt="mini" class="object-cover w-full h-full">
                                        </div>
                                        <span class="truncate" title="<?php echo e($upload['original_name']); ?>"><?php echo e($upload['original_name']); ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <?php echo $status_badge; ?>
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-450">
                                    <?php echo date('d M Y, H:i', strtotime($upload['created_at'])); ?>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2.5">
                                        <?php if ($upload['status'] === 'done'): ?>
                                            <a href="result.php?id=<?php echo $upload['id']; ?>" class="p-1.5 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition" title="Lihat & Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </a>
                                            <a href="download.php?id=<?php echo $upload['id']; ?>&format=txt" class="p-1.5 hover:bg-slate-800 text-slate-450 hover:text-indigo-400 rounded-lg transition" title="Unduh TXT">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                                            </a>
                                            <a href="download.php?id=<?php echo $upload['id']; ?>&format=pdf" class="p-1.5 hover:bg-slate-800 text-slate-450 hover:text-indigo-400 rounded-lg transition" title="Unduh PDF">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                            </a>
                                        <?php else: ?>
                                            <a href="process.php?id=<?php echo $upload['id']; ?>" class="text-xs font-semibold text-brand-400 hover:underline px-2 py-1.5">Proses Ulang</a>
                                        <?php endif; ?>
                                        
                                        <!-- Secure Deletion Trigger Form -->
                                        <form method="POST" action="history.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus berkas dan riwayat OCR ini secara permanen? Tindakan ini tidak dapat dibatalkan.');" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="delete_id" value="<?php echo $upload['id']; ?>">
                                            <button type="submit" class="p-1.5 hover:bg-slate-800 text-slate-500 hover:text-rose-450 rounded-lg transition" title="Hapus Permanen">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
