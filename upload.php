<?php
/**
 * Upload Handler (upload.php)
 * Accepts either a regular file upload OR a base64 cropped image from Cropper.js
 */
require_once __DIR__ . '/config.php';

// Route protection
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Verify CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    set_flash_message('error', 'Token CSRF tidak valid. Silakan coba unggah kembali.');
    header("Location: index.php");
    exit;
}

$unique_filename = '';
$original_name   = '';
$destination     = '';

// ── Path A: Base64 cropped image from Cropper.js ──────────────────────────
$cropped_data = $_POST['cropped_image'] ?? '';
if (!empty($cropped_data)) {

    // Validate data URI format
    if (!preg_match('/^data:image\/(jpeg|png|jpg);base64,/', $cropped_data, $matches)) {
        set_flash_message('error', 'Format gambar hasil crop tidak valid.');
        header("Location: index.php");
        exit;
    }

    $img_ext    = ($matches[1] === 'png') ? 'png' : 'jpg';
    $base64_str = preg_replace('/^data:image\/\w+;base64,/', '', $cropped_data);
    $img_data   = base64_decode($base64_str);

    if (!$img_data) {
        set_flash_message('error', 'Gagal mendekode data gambar. Silakan coba lagi.');
        header("Location: index.php");
        exit;
    }

    // Size check (5MB max)
    if (strlen($img_data) > 5 * 1024 * 1024) {
        set_flash_message('error', 'Ukuran gambar tidak boleh melebihi 5MB.');
        header("Location: index.php");
        exit;
    }

    $original_name   = $_POST['original_filename'] ?? ('crop_image.' . $img_ext);
    $unique_filename = uniqid('ocr_', true) . '_' . time() . '.' . $img_ext;
    $destination     = UPLOAD_DIR . $unique_filename;

    if (file_put_contents($destination, $img_data) === false) {
        set_flash_message('error', 'Gagal menyimpan gambar hasil crop. Periksa izin folder uploads/');
        header("Location: index.php");
        exit;
    }

// ── Path B: Regular file upload ───────────────────────────────────────────
} else {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        set_flash_message('error', 'Silakan pilih file gambar terlebih dahulu.');
        header("Location: index.php");
        exit;
    }

    $file = $_FILES['image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        set_flash_message('error', 'Terjadi kesalahan saat mengunggah berkas. Kode Error: ' . $file['error']);
        header("Location: index.php");
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        set_flash_message('error', 'Ukuran gambar tidak boleh melebihi 5MB.');
        header("Location: index.php");
        exit;
    }

    $original_name = $file['name'];
    $tmp_name      = $file['tmp_name'];
    $path_info     = pathinfo($original_name);
    $img_ext       = isset($path_info['extension']) ? strtolower($path_info['extension']) : '';

    if (!in_array($img_ext, ['jpg', 'jpeg', 'png'])) {
        set_flash_message('error', 'Format berkas tidak didukung. Hanya JPG, JPEG, dan PNG yang diperbolehkan.');
        header("Location: index.php");
        exit;
    }

    // MIME type double-check
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/pjpeg', 'image/x-png'];
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);
    } else {
        $img_info = getimagesize($tmp_name);
        $mime     = $img_info ? $img_info['mime'] : '';
    }

    if (!in_array($mime, $allowed_mimes)) {
        set_flash_message('error', 'Isi berkas tidak valid sebagai gambar JPG/PNG.');
        header("Location: index.php");
        exit;
    }

    $unique_filename = uniqid('ocr_', true) . '_' . time() . '.' . $img_ext;
    $destination     = UPLOAD_DIR . $unique_filename;

    if (!move_uploaded_file($tmp_name, $destination)) {
        set_flash_message('error', 'Gagal memindahkan file ke folder penyimpanan. Periksa izin folder.');
        header("Location: index.php");
        exit;
    }
}

// ── Save to database ──────────────────────────────────────────────────────
try {
    $db   = get_db_connection();
    $stmt = $db->prepare("
        INSERT INTO uploads (user_id, original_name, file_name, file_path, status)
        VALUES (?, ?, ?, ?, 'uploaded')
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $original_name,
        $unique_filename,
        'uploads/' . $unique_filename
    ]);

    $upload_id = $db->lastInsertId();

    set_flash_message('success', 'Gambar berhasil diunggah! Memulai pemrosesan OCR...');
    header("Location: process.php?id=" . $upload_id);
    exit;

} catch (PDOException $e) {
    if (file_exists($destination)) unlink($destination);
    set_flash_message('error', 'Gagal menyimpan riwayat unggahan ke database. Error: ' . $e->getMessage());
    header("Location: index.php");
    exit;
}
