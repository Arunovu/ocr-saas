<?php
/**
 * Registration Page
 */
require_once __DIR__ . '/config.php';

// Allow only guests to visit this page
require_guest();

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF verification
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $errors[] = "Invalid CSRF token. Silakan coba kembali.";
    } else {
        // Sanitize input data
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        // Validation - Name
        if (empty($name)) {
            $errors[] = "Nama lengkap wajib diisi.";
        }

        // Validation - Email
        if (empty($email)) {
            $errors[] = "Email wajib diisi.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format email tidak valid.";
        }

        // Validation - Password
        if (empty($password)) {
            $errors[] = "Password wajib diisi.";
        } else {
            // Rule 1: Min 8 characters
            if (strlen($password) < 8) {
                $errors[] = "Password harus minimal 8 karakter.";
            }
            // Rule 2: Must contain letters (a-z / A-Z)
            if (!preg_match('/[a-zA-Z]/', $password)) {
                $errors[] = "Password harus mengandung minimal satu huruf (a-z atau A-Z).";
            }
            // Rule 3: Blacklist common weak passwords
            $weak_passwords = [
                '12345678', '123456789', 'password', 'qwerty', 'admin123',
                'admin', 'password123', 'qwerty1234', '1234567890', 'password321'
            ];
            if (in_array(strtolower($password), $weak_passwords)) {
                $errors[] = "Password terlalu lemah/umum. Silakan gunakan kombinasi yang lebih aman.";
            }
        }

        // If no errors so far, check email uniqueness
        if (empty($errors)) {
            try {
                $db = get_db_connection();
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $errors[] = "Email sudah terdaftar. Silakan login atau gunakan email lain.";
                } else {
                    // Password hash saving
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert into DB
                    $insert_stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                    $insert_stmt->execute([$name, $email, $hashed_password]);
                    
                    set_flash_message('success', 'Registrasi berhasil! Silakan login menggunakan akun Anda.');
                    header("Location: login.php");
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = "Gagal memproses pendaftaran. Silakan periksa koneksi database.";
            }
        }
    }
}

$page_title = "Daftar Akun Baru";
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-16 flex items-center justify-center">
    <div class="w-full max-w-md bg-slate-900/60 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-md">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-white mb-2">Buat Akun Baru</h2>
            <p class="text-xs text-slate-400">Mulailah mengubah gambar Anda menjadi dokumen secara instan.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs flex flex-col gap-1.5">
                <span class="font-bold flex items-center gap-1.5 text-rose-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Pendaftaran Gagal:
                </span>
                <ul class="list-disc pl-5 space-y-0.5">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="space-y-5">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div>
                <label class="block text-xs font-semibold text-slate-450 uppercase tracking-wider mb-2">Nama Lengkap</label>
                <input type="text" name="name" value="<?php echo e($name); ?>" required placeholder="Masukkan nama lengkap Anda"
                       class="w-full bg-slate-950/80 border border-slate-800 focus:border-brand-500/60 focus:ring-1 focus:ring-brand-500/30 rounded-2xl px-4 py-3 text-slate-200 focus:outline-none transition text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-450 uppercase tracking-wider mb-2">Email Address</label>
                <input type="email" name="email" value="<?php echo e($email); ?>" required placeholder="nama@email.com"
                       class="w-full bg-slate-950/80 border border-slate-800 focus:border-brand-500/60 focus:ring-1 focus:ring-brand-500/30 rounded-2xl px-4 py-3 text-slate-200 focus:outline-none transition text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-450 uppercase tracking-wider mb-2">Password</label>
                <input type="password" name="password" required placeholder="Minimal 8 karakter & mengandung huruf"
                       class="w-full bg-slate-950/80 border border-slate-800 focus:border-brand-500/60 focus:ring-1 focus:ring-brand-500/30 rounded-2xl px-4 py-3 text-slate-200 focus:outline-none transition text-sm">
                <p class="text-[10px] text-slate-500 mt-1.5 leading-relaxed">
                    * Wajib minimal 8 karakter, mengandung minimal satu huruf (a-z / A-Z), dan bukan kata sandi pasaran.
                </p>
            </div>

            <button type="submit" class="w-full py-3.5 mt-2 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 transition rounded-2xl text-white font-semibold text-sm shadow-lg shadow-indigo-600/20 border border-indigo-500/35">
                Daftar Akun
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-800/80 text-center">
            <p class="text-xs text-slate-450">
                Sudah memiliki akun? <a href="login.php" class="text-brand-400 hover:underline font-semibold">Silakan Login</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
