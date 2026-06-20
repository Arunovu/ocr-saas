<?php
/**
 * Setup and Installer Script
 * Checks requirements, configures Database, installs Dompdf, and imports SQL schema.
 */

// Define absolute path
define('BASE_DIR', __DIR__ . '/');
define('DOMPDF_ZIP_URL', 'https://github.com/dompdf/dompdf/releases/download/v3.1.5/dompdf-3.1.5.zip');
define('DOMPDF_PATH', BASE_DIR . 'libs/dompdf/autoload.inc.php');

// Simple session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = [];
$info = [];

// Helper to escape output strings
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper to extract a ZIP file — uses ZipArchive if available, falls back to PowerShell on Windows
function extract_zip($zip_path, $dest_dir) {
    if (extension_loaded('zip')) {
        $zip = new ZipArchive;
        if ($zip->open($zip_path) === TRUE) {
            $zip->extractTo($dest_dir);
            $zip->close();
            return true;
        }
        return false;
    } elseif (PHP_OS_FAMILY === 'Windows') {
        // Use PowerShell Expand-Archive as fallback
        $zip_path_esc  = str_replace("'", "''", $zip_path);
        $dest_dir_esc  = str_replace("'", "''", $dest_dir);
        $cmd = "powershell -NoProfile -Command \"Expand-Archive -LiteralPath '$zip_path_esc' -DestinationPath '$dest_dir_esc' -Force\"";
        $output = [];
        $ret = -1;
        exec($cmd, $output, $ret);
        return $ret === 0;
    }
    return false;
}

// Helper to check command availability in Windows
function check_command($cmd) {
    $output = [];
    $return_var = -1;
    exec("where $cmd 2>nul", $output, $return_var);
    if ($return_var === 0) {
        return trim($output[0]);
    }
    // Check default XAMPP or Windows installation paths for Tesseract
    $common_paths = [
        'C:\Program Files\Tesseract-OCR\tesseract.exe',
        'C:\Program Files (x86)\Tesseract-OCR\tesseract.exe',
        'C:\Users\\' . get_current_user() . '\AppData\Local\Tesseract-OCR\tesseract.exe'
    ];
    foreach ($common_paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    return false;
}

// 1. Requirements Check
$requirements = [
    'php_version' => [
        'name' => 'PHP Version >= 7.4',
        'check' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'current' => PHP_VERSION,
        'required' => '7.4.0+'
    ],
    'pdo_mysql' => [
        'name' => 'PDO MySQL Extension',
        'check' => extension_loaded('pdo_mysql'),
        'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled',
        'required' => 'Enabled'
    ],
    'curl' => [
        'name' => 'cURL Extension',
        'check' => extension_loaded('curl'),
        'current' => extension_loaded('curl') ? 'Enabled' : 'Disabled',
        'required' => 'Enabled'
    ],
    'zip' => [
        'name' => 'Zip Extension (or PowerShell fallback)',
        'check' => extension_loaded('zip') || (PHP_OS_FAMILY === 'Windows'),
        'current' => extension_loaded('zip') ? 'Enabled' : (PHP_OS_FAMILY === 'Windows' ? 'Disabled (PowerShell fallback available)' : 'Disabled'),
        'required' => 'Enabled or Windows PowerShell'
    ],
    'gd' => [
        'name' => 'GD Library Extension',
        'check' => extension_loaded('gd'),
        'current' => extension_loaded('gd') ? 'Enabled' : 'Disabled',
        'required' => 'Enabled'
    ],
    'write_permission' => [
        'name' => 'Write Permissions (root directory)',
        'check' => is_writable(BASE_DIR),
        'current' => is_writable(BASE_DIR) ? 'Writable' : 'Not Writable',
        'required' => 'Writable'
    ]
];

$all_reqs_passed = true;
foreach ($requirements as $req) {
    if (!$req['check']) {
        $all_reqs_passed = false;
    }
}

// Detect Tesseract
$tesseract_detected_path = check_command('tesseract');

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2 && isset($_POST['configure_db'])) {
        $db_host = trim($_POST['db_host']);
        $db_port = trim($_POST['db_port']);
        $db_name = trim($_POST['db_name']);
        $db_user = trim($_POST['db_user']);
        $db_pass = $_POST['db_pass'];
        $tess_path = trim($_POST['tess_path']);

        // Check DB Connection and create database if needed
        try {
            $dsn_no_db = "mysql:host=$db_host;port=$db_port;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT            => 5
            ];
            
            $pdo_temp = new PDO($dsn_no_db, $db_user, $db_pass, $options);
            $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Reconnect with DB
            $pdo_db = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $options);
            
            // Save settings to session to pass to next step
            $_SESSION['setup_db_host'] = $db_host;
            $_SESSION['setup_db_port'] = $db_port;
            $_SESSION['setup_db_name'] = $db_name;
            $_SESSION['setup_db_user'] = $db_user;
            $_SESSION['setup_db_pass'] = $db_pass;
            $_SESSION['setup_tess_path'] = $tess_path;
            
            header("Location: setup.php?step=3");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Koneksi database gagal: " . $e->getMessage();
        }
    }

    if ($step === 3 && isset($_POST['install_libs'])) {
        $db_host = $_SESSION['setup_db_host'] ?? '127.0.0.1';
        $db_port = $_SESSION['setup_db_port'] ?? '3306';
        $db_name = $_SESSION['setup_db_name'] ?? 'ocr_db';
        $db_user = $_SESSION['setup_db_user'] ?? 'root';
        $db_pass = $_SESSION['setup_db_pass'] ?? '';
        $tess_path = $_SESSION['setup_tess_path'] ?? 'tesseract';

        try {
            // Connect
            $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
            $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            $pdo = new PDO($dsn, $db_user, $db_pass, $options);

            // Import db.sql
            $sql_file = BASE_DIR . 'db.sql';
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                // Strip comments and split by semi-colon to execute line-by-line safely
                $sql = preg_replace('/--.*\n/', '', $sql);
                $queries = explode(';', $sql);
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        $pdo->exec($query);
                    }
                }
                $success[] = "Skema database berhasil diimpor.";
            } else {
                $errors[] = "File db.sql tidak ditemukan di direktori root.";
            }

            // Download and extract Dompdf
            if (!file_exists(DOMPDF_PATH)) {
                $libs_dir = BASE_DIR . 'libs/';
                if (!is_dir($libs_dir)) {
                    mkdir($libs_dir, 0755, true);
                }
                
                $temp_zip = $libs_dir . 'dompdf_temp.zip';
                
                // Initialize cURL to download file
                $ch = curl_init(DOMPDF_ZIP_URL);
                $fp = fopen($temp_zip, 'wb');
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_exec($ch);
                curl_close($ch);
                fclose($fp);

                if (file_exists($temp_zip) && filesize($temp_zip) > 1000) {
                    // Extract Zip (ZipArchive or PowerShell fallback)
                    if (extract_zip($temp_zip, $libs_dir)) {
                        $success[] = "Dompdf library berhasil diunduh dan diekstrak.";
                    } else {
                        $errors[] = "Gagal mengekstrak Dompdf ZIP. Pastikan ekstensi ZIP atau PowerShell tersedia.";
                    }
                    unlink($temp_zip); // Delete temp file
                } else {
                    $errors[] = "Gagal mengunduh Dompdf. Silakan periksa koneksi internet Anda.";
                    if (file_exists($temp_zip)) {
                        unlink($temp_zip);
                    }
                }
            } else {
                $success[] = "Dompdf library sudah terinstal sebelumnya.";
            }

            // Write local configuration file config_local.php
            if (empty($errors)) {
                $escaped_pass = str_replace("'", "\\'", $db_pass);
                $escaped_tess = str_replace("\\", "\\\\", $tess_path);
                $config_content = "<?php\n"
                    . "// local database config overrides\n"
                    . "\$db_host = '$db_host';\n"
                    . "\$db_port = '$db_port';\n"
                    . "\$db_name = '$db_name';\n"
                    . "\$db_user = '$db_user';\n"
                    . "\$db_pass = '$escaped_pass';\n"
                    . "\$tesseract_path = '$escaped_tess';\n";
                
                if (file_put_contents(BASE_DIR . 'config_local.php', $config_content)) {
                    $success[] = "File config_local.php berhasil dibuat.";
                    
                    // Clear session setup vars
                    unset($_SESSION['setup_db_host']);
                    unset($_SESSION['setup_db_port']);
                    unset($_SESSION['setup_db_name']);
                    unset($_SESSION['setup_db_user']);
                    unset($_SESSION['setup_db_pass']);
                    unset($_SESSION['setup_tess_path']);
                    
                    header("Location: setup.php?step=4");
                    exit;
                } else {
                    $errors[] = "Gagal membuat file config_local.php. Periksa izin direktori.";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Gagal saat instalasi: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Installer - OCR Image to Document Converter</title>
    <!-- Tailwind CSS v3 Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-[#0f172a] text-slate-200 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl bg-[#1e293b] border border-slate-700/60 rounded-2xl shadow-2xl overflow-hidden backdrop-blur-md">
        
        <!-- Header -->
        <div class="px-8 py-6 bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-800 flex items-center justify-between border-b border-indigo-500/20">
            <div>
                <h1 class="text-xl font-bold text-white tracking-wide">OCR Converter Setup</h1>
                <p class="text-xs text-indigo-200/90 mt-0.5">SaaS Installer Utility for Local XAMPP</p>
            </div>
            <div class="bg-white/10 px-3 py-1.5 rounded-full text-xs font-semibold text-white tracking-wider border border-white/10">
                Langkah <?php echo $step; ?> dari 4
            </div>
        </div>

        <!-- Stepper Indicator -->
        <div class="px-8 pt-6 flex items-center gap-2">
            <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="flex-1 h-1.5 rounded-full transition-all duration-300 <?php echo $i <= $step ? 'bg-indigo-500' : 'bg-slate-700'; ?>"></div>
            <?php endfor; ?>
        </div>

        <div class="p-8">
            
            <!-- Alert Display -->
            <?php if (!empty($errors)): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-sm flex flex-col gap-1.5">
                    <span class="font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Terdapat Masalah:
                    </span>
                    <ul class="list-disc pl-5 space-y-0.5">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm flex flex-col gap-1.5">
                    <span class="font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Proses Berhasil:
                    </span>
                    <ul class="list-disc pl-5 space-y-0.5">
                        <?php foreach ($success as $msg): ?>
                            <li><?php echo e($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- STEP 1: REQUIREMENTS CHECK -->
            <?php if ($step === 1): ?>
                <div>
                    <h2 class="text-lg font-semibold text-white mb-2">Pemeriksaan Persyaratan Sistem</h2>
                    <p class="text-sm text-slate-400 mb-6">Sebelum memulai instalasi, pastikan lingkungan XAMPP Anda memenuhi persyaratan di bawah ini.</p>

                    <div class="space-y-3.5 mb-8">
                        <?php foreach ($requirements as $key => $req): ?>
                            <div class="flex items-center justify-between p-3.5 bg-slate-800/40 rounded-xl border border-slate-700/40">
                                <div>
                                    <div class="text-sm font-medium text-slate-200"><?php echo e($req['name']); ?></div>
                                    <div class="text-xs text-slate-500">Nilai: <?php echo e($req['current']); ?> (Dibutuhkan: <?php echo e($req['required']); ?>)</div>
                                </div>
                                <div>
                                    <?php if ($req['check']): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                                            Lolos
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/25">
                                            Gagal
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Tesseract OCR Info -->
                        <div class="p-3.5 bg-slate-800/40 rounded-xl border border-slate-700/40 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-slate-200">Tesseract OCR (CLI Version)</div>
                                <div class="text-xs text-slate-500">
                                    <?php if ($tesseract_detected_path): ?>
                                        Terdeteksi di: <code class="bg-slate-900 px-1 py-0.5 rounded text-indigo-400 text-xs"><?php echo e($tesseract_detected_path); ?></code>
                                    <?php else: ?>
                                        Belum terdeteksi di System PATH (Anda dapat memasukkan path manual di langkah berikutnya)
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <?php if ($tesseract_detected_path): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/25">
                                        Terdeteksi
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/25">
                                        Manual Config
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <?php if ($all_reqs_passed): ?>
                            <a href="setup.php?step=2" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 transition rounded-xl text-white font-medium text-sm flex items-center gap-1.5 shadow-lg shadow-indigo-600/20">
                                Lanjut
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        <?php else: ?>
                            <button disabled class="px-6 py-2.5 bg-slate-700 text-slate-500 cursor-not-allowed rounded-xl font-medium text-sm">
                                Perbaiki masalah di atas
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

            <!-- STEP 2: DATABASE CONFIGURATION -->
            <?php elseif ($step === 2): ?>
                <form method="POST" action="setup.php?step=2" class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-white mb-1">Konfigurasi Database & Tesseract</h2>
                        <p class="text-sm text-slate-400 mb-6">Hubungkan aplikasi dengan database MySQL Anda dan arahkan lokasi biner Tesseract OCR.</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5 uppercase tracking-wider">Host Database</label>
                                <input type="text" name="db_host" value="127.0.0.1" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-slate-200 focus:outline-none focus:border-indigo-500 transition text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5 uppercase tracking-wider">Port</label>
                                <input type="text" name="db_port" value="3306" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-slate-200 focus:outline-none focus:border-indigo-500 transition text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5 uppercase tracking-wider">Nama Database (Akan Dibuat otomatis)</label>
                                <input type="text" name="db_name" value="ocr_db" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-slate-200 focus:outline-none focus:border-indigo-500 transition text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5 uppercase tracking-wider">Username Database</label>
                                <input type="text" name="db_user" value="root" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-slate-200 focus:outline-none focus:border-indigo-500 transition text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-400 mb-1.5 uppercase tracking-wider">Password Database (Bawaan XAMPP kosongkan)</label>
                                <input type="password" name="db_pass" value="" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-slate-200 focus:outline-none focus:border-indigo-500 transition text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-400 mb-1.5 uppercase tracking-wider">Lokasi Executable Tesseract OCR</label>
                                <input type="text" name="tess_path" value="<?php echo e($tesseract_detected_path ?: 'C:\Program Files\Tesseract-OCR\tesseract.exe'); ?>" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-slate-200 focus:outline-none focus:border-indigo-500 transition text-sm">
                                <p class="text-[11px] text-slate-400 mt-1.5">
                                    Masukkan <code class="text-indigo-400 font-mono">tesseract</code> jika sudah terdaftar di Path variable Windows, atau gunakan path mutlak (misal: <code class="text-indigo-400 font-mono">C:\Program Files\Tesseract-OCR\tesseract.exe</code>).
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <a href="setup.php?step=1" class="px-5 py-2.5 border border-slate-700 hover:bg-slate-800 transition rounded-xl text-slate-300 font-medium text-sm">
                            Kembali
                        </a>
                        <button type="submit" name="configure_db" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 transition rounded-xl text-white font-medium text-sm flex items-center gap-1.5 shadow-lg shadow-indigo-600/20">
                            Simpan & Hubungkan
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                </form>

            <!-- STEP 3: MIGRATION & LIBRARIES DOWNLOAD -->
            <?php elseif ($step === 3): ?>
                <div>
                    <h2 class="text-lg font-semibold text-white mb-2">Instalasi Library & Skema Database</h2>
                    <p class="text-sm text-slate-400 mb-6">Kami akan mengunduh library PDF parser (Dompdf v3.1.5) dari GitHub serta mengimpor tabel ke database.</p>

                    <div class="p-6 bg-slate-800/40 border border-slate-700/50 rounded-xl mb-8 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-300">Hubungkan database <code class="text-indigo-400 font-mono"><?php echo e($_SESSION['setup_db_name'] ?? 'ocr_db'); ?></code></span>
                            <span class="text-xs text-indigo-400 font-semibold">Siap</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-300">Impor file <code class="text-indigo-400 font-mono">db.sql</code></span>
                            <span class="text-xs text-indigo-400 font-semibold">Siap</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-300">Unduh & Ekstrak Dompdf (v3.1.5)</span>
                            <span class="text-xs text-indigo-400 font-semibold">Siap (~4MB)</span>
                        </div>
                    </div>

                    <form method="POST" action="setup.php?step=3">
                        <div class="flex justify-between items-center">
                            <a href="setup.php?step=2" class="px-5 py-2.5 border border-slate-700 hover:bg-slate-800 transition rounded-xl text-slate-300 font-medium text-sm">
                                Kembali
                            </a>
                            <button type="submit" name="install_libs" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 transition rounded-xl text-white font-medium text-sm flex items-center gap-1.5 shadow-lg shadow-indigo-600/20">
                                Mulai Instalasi
                                <svg class="w-4 h-4 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            </button>
                        </div>
                    </form>
                </div>

            <!-- STEP 4: FINISHED -->
            <?php elseif ($step === 4): ?>
                <div class="text-center py-6">
                    <div class="w-16 h-16 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    </div>

                    <h2 class="text-xl font-bold text-white mb-2">Instalasi Selesai!</h2>
                    <p class="text-sm text-slate-400 max-w-md mx-auto mb-8">
                        OCR Image to Document Converter (SaaS Style) berhasil diinstal di server lokal Anda. Anda sekarang dapat menjelajahi seluruh fiturnya.
                    </p>

                    <div class="flex justify-center gap-4">
                        <a href="landing.php" class="px-6 py-3 border border-slate-700 hover:bg-slate-800 transition rounded-xl text-slate-300 font-medium text-sm">
                            Ke Landing Page
                        </a>
                        <a href="register.php" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 transition rounded-xl text-white font-medium text-sm flex items-center gap-1.5 shadow-lg shadow-indigo-600/20">
                            Daftar Akun Baru
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>
