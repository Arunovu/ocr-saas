<?php
/**
 * Document Exporter (download.php)
 * Delivers dynamic text downloads or renders a styled PDF using Dompdf.
 */
require_once __DIR__ . '/config.php';

// Route protection
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$format = isset($_GET['format']) ? trim($_GET['format']) : 'txt';
$user_id = $_SESSION['user_id'];

if (!in_array($format, ['txt', 'pdf'])) {
    set_flash_message('error', 'Format ekspor tidak didukung.');
    header("Location: history.php");
    exit;
}

try {
    $db = get_db_connection();
    
    // Fetch upload detail
    $stmt = $db->prepare("SELECT * FROM uploads WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $upload = $stmt->fetch();
    
    if (!$upload) {
        set_flash_message('error', 'Dokumen tidak ditemukan.');
        header("Location: history.php");
        exit;
    }

    if ($upload['status'] !== 'done') {
        set_flash_message('error', 'Dokumen belum selesai diproses oleh OCR.');
        header("Location: result.php?id=" . $id);
        exit;
    }

    $original_filename = pathinfo($upload['original_name'], PATHINFO_FILENAME);
    $ocr_text = $upload['ocr_text'] ?? '';

    // 1. TEXT EXPORT
    if ($format === 'txt') {
        $download_name = $original_filename . '_ocr.txt';
        
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . rawurlencode($download_name) . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $ocr_text;
        exit;
    }

    // 2. PDF EXPORT (DOMPDF)
    if ($format === 'pdf') {
        // Require Dompdf autoloader
        require_once DOMPDF_AUTOLOAD;

        if (!class_exists('Dompdf\\Dompdf')) {
            set_flash_message('error', 'Terjadi masalah pemuatan library PDF (Class Dompdf tidak ditemukan).');
            header("Location: result.php?id=" . $id);
            exit;
        }

        // Instantiate options FIRST
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        // Parse and validate PDF customization parameters
        $size        = $_GET['size'] ?? 'a4';
        $orientation = $_GET['orientation'] ?? 'portrait';
        $fontsize    = isset($_GET['fontsize']) && is_numeric($_GET['fontsize']) ? (int)$_GET['fontsize'] : 11;

        $allowed_sizes        = ['a4', 'letter', 'legal'];
        $allowed_orientations = ['portrait', 'landscape'];
        if (!in_array(strtolower($size), $allowed_sizes))              $size        = 'a4';
        if (!in_array(strtolower($orientation), $allowed_orientations)) $orientation = 'portrait';

        $options->set('defaultPaperSize', $size);
        $options->set('defaultPaperOrientation', $orientation);

        $pdf_fontsize_pt = $fontsize . 'pt';

        $dompdf = new \Dompdf\Dompdf($options);

        // Escape outputs inside PDF
        $escaped_title   = htmlspecialchars($upload['original_name'], ENT_QUOTES, 'UTF-8');
        $escaped_user    = htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8');
        $escaped_date    = date('d M Y, H:i', strtotime($upload['created_at']));
        $escaped_content = htmlspecialchars($ocr_text, ENT_QUOTES, 'UTF-8');

        // Render document structure
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$escaped_title}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            line-height: 1.5;
            margin: 20px;
            font-size: {$pdf_fontsize_pt};
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }
        .logo {
            font-size: 18pt;
            font-weight: bold;
            color: #4f46e5;
        }
        .meta-grid {
            margin-top: 8px;
            font-size: 9pt;
            color: #64748b;
            width: 100%;
        }
        .meta-grid td {
            padding: 2px 0;
        }
        .content {
            font-size: 10pt;
            white-space: pre-wrap;
            word-wrap: break-word;
            color: #0f172a;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }
        .footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            text-align: center;
            font-size: 8pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">OCRSaaS Document</div>
        <table class="meta-grid">
            <tr>
                <td style="width: 18%;"><strong>Nama File:</strong></td>
                <td>{$escaped_title}</td>
            </tr>
            <tr>
                <td><strong>Hasil Scan:</strong></td>
                <td>{$escaped_date}</td>
            </tr>
            <tr>
                <td><strong>Pemilik:</strong></td>
                <td>{$escaped_user}</td>
            </tr>
        </table>
    </div>
    
    <div class="content">{$escaped_content}</div>
    
    <div class="footer">
        Dihasilkan secara otomatis oleh OCR Image to Document Converter (SaaS Style)
    </div>
</body>
</html>
HTML;

        $dompdf->loadHtml($html);
        $dompdf->render();

        // Download stream
        $download_name = $original_filename . '_ocr.pdf';
        $dompdf->stream($download_name, array("Attachment" => true));
        exit;
    }

} catch (PDOException $e) {
    set_flash_message('error', 'Gagal memproses ekspor karena gangguan database.');
    header("Location: history.php");
    exit;
} catch (Exception $e) {
    set_flash_message('error', 'Gagal membuat dokumen: ' . $e->getMessage());
    header("Location: result.php?id=" . $id);
    exit;
}