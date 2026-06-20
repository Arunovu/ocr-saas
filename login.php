<?php
/**
 * Login Page
 */
require_once __DIR__ . '/config.php';

// Allow only guests to visit this page
require_guest();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF verification
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $errors[] = "Invalid CSRF token. Silakan coba kembali.";
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($email)) {
            $errors[] = "Email wajib diisi.";
        }
        if (empty($password)) {
            $errors[] = "Password wajib diisi.";
        }

        if (empty($errors)) {
            try {
                $db = get_db_connection();
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Password verify success - regenerate session to avoid session fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];

                    set_flash_message('success', 'Selamat datang kembali, ' . $user['name'] . '!');
                    header("Location: index.php");
                    exit;
                } else {
                    $errors[] = "Email atau password salah.";
                }
            } catch (PDOException $e) {
                $errors[] = "Gagal memproses login. Silakan hubungi admin.";
            }
        }
    }
}

$page_title = "Login Ke Akun";
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-16 flex items-center justify-center">
    <div class="w-full max-w-md bg-slate-900/60 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-md">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-white mb-2">Masuk ke Akun Anda</h2>
            <p class="text-xs text-slate-400">Silakan masukkan email dan password untuk melanjutkan.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs flex flex-col gap-1.5">
                <span class="font-bold flex items-center gap-1.5 text-rose-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Gagal Login:
                </span>
                <ul class="list-disc pl-5 space-y-0.5">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-5">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div>
                <label class="block text-xs font-semibold text-slate-450 uppercase tracking-wider mb-2">Email Address</label>
                <input type="email" name="email" value="<?php echo e($email); ?>" required placeholder="nama@email.com"
                       class="w-full bg-slate-950/80 border border-slate-800 focus:border-brand-500/60 focus:ring-1 focus:ring-brand-500/30 rounded-2xl px-4 py-3 text-slate-200 focus:outline-none transition text-sm">
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-semibold text-slate-450 uppercase tracking-wider">Password</label>
                </div>
                <input type="password" name="password" required placeholder="••••••••"
                       class="w-full bg-slate-950/80 border border-slate-800 focus:border-brand-500/60 focus:ring-1 focus:ring-brand-500/30 rounded-2xl px-4 py-3 text-slate-200 focus:outline-none transition text-sm">
            </div>

            <button type="submit" class="w-full py-3.5 mt-2 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 transition rounded-2xl text-white font-semibold text-sm shadow-lg shadow-indigo-600/20 border border-indigo-500/35">
                Login
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-800/80 text-center">
            <p class="text-xs text-slate-450">
                Belum memiliki akun? <a href="register.php" class="text-brand-400 hover:underline font-semibold">Silakan Daftar</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
