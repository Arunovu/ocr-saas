<?php
/**
 * Public Landing Page
 */
$page_title = "OCR Image to Document Converter";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="relative overflow-hidden pt-20 pb-28 md:pt-32 md:pb-40">
    <!-- Background Blur Decorative Gradients -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-brand-500/10 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute top-1/3 left-1/4 -translate-y-1/2 w-[300px] h-[300px] bg-indigo-500/15 rounded-full blur-[80px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto">
            <!-- SaaS Badge -->
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-brand-500/10 text-indigo-300 border border-brand-500/20 mb-6">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-400 animate-ping"></span>
                Tesseract OCR v3.0 Powered
            </span>

            <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight text-white mb-6 leading-tight sm:leading-none">
                Ubah Gambar Menjadi Dokumen <br class="hidden sm:inline">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-brand-400 via-indigo-400 to-purple-400">
                    Secara Instan & Akurat
                </span>
            </h1>

            <p class="text-base sm:text-lg md:text-xl text-slate-400 font-medium mb-10 max-w-2xl mx-auto">
                Unggah hasil scan, foto formulir, atau screenshot, lalu ubah menjadi teks yang dapat diedit dan diunduh dalam format PDF atau TXT dalam hitungan detik.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <?php if (is_logged_in()): ?>
                    <a href="index.php" class="w-full sm:w-auto px-8 py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-2xl shadow-xl shadow-indigo-600/35 border border-indigo-500/30 transition-all-300 text-center flex items-center justify-center gap-2">
                        Masuk ke Dashboard
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                <?php else: ?>
                    <a href="register.php" class="w-full sm:w-auto px-8 py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-2xl shadow-xl shadow-indigo-600/35 border border-indigo-500/30 transition-all-300 text-center">
                        Mulai Gratis Sekarang
                    </a>
                    <a href="login.php" class="w-full sm:w-auto px-8 py-3.5 border border-slate-700 hover:bg-slate-800 text-slate-200 font-semibold rounded-2xl transition-all-300 text-center">
                        Login Ke Akun
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Preview Mockup Card -->
        <div class="mt-16 sm:mt-20 max-w-4xl mx-auto rounded-3xl bg-slate-900/60 p-4 border border-slate-800/80 shadow-2xl backdrop-blur-md">
            <div class="rounded-2xl overflow-hidden border border-slate-800 bg-[#0e1320] p-6 grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <!-- Visual Upload Side -->
                <div class="border border-dashed border-slate-700/60 hover:border-brand-500/60 rounded-xl p-8 flex flex-col items-center justify-center bg-slate-800/20 transition cursor-pointer">
                    <div class="w-12 h-12 rounded-xl bg-brand-500/10 text-brand-400 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-350">pilih_gambar_ocr.png</span>
                    <span class="text-xs text-slate-500 mt-1">Format JPG atau PNG maks 5MB</span>
                </div>
                <!-- Visual Process/Output Side -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Hasil Ekstraksi OCR</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">Done</span>
                    </div>
                    <div class="p-3 bg-slate-950/80 rounded-lg text-xs font-mono text-indigo-200/90 h-32 overflow-y-auto leading-relaxed border border-slate-800">
                        PENGGUNAAN TEKNOLOGI OCR UNTUK DIGITALISASI DOKUMEN...<br>
                        Tesseract OCR secara otomatis mengenali karakter-karakter teks dalam gambar, mendukung bahasa Inggris dan Indonesia.<br>
                        Hasil konversi dapat diedit secara langsung di sini.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-20 bg-[#080b13] border-t border-slate-800/60 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-3xl font-bold text-white mb-4">Fitur Utama Platform Kami</h2>
            <p class="text-slate-400 font-medium text-sm sm:text-base">Membantu mempercepat ekstraksi teks dari dokumen gambar Anda secara efisien.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="p-6 bg-slate-900/40 rounded-2xl border border-slate-800/80 hover:border-indigo-500/35 transition-all-300">
                <div class="w-12 h-12 bg-indigo-500/10 text-indigo-400 rounded-xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0a8 8 0 11-16 0 8 8 0 0116 0z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Tesseract OCR Eng + Ind</h3>
                <p class="text-sm text-slate-400 leading-relaxed">Mendukung ekstraksi multibahasa untuk mengenali teks berbahasa Inggris dan Indonesia dengan akurasi tinggi.</p>
            </div>

            <!-- Feature 2 -->
            <div class="p-6 bg-slate-900/40 rounded-2xl border border-slate-800/80 hover:border-indigo-500/35 transition-all-300">
                <div class="w-12 h-12 bg-indigo-500/10 text-indigo-400 rounded-xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Editor Teks Interaktif</h3>
                <p class="text-sm text-slate-400 leading-relaxed">Hasil ekstraksi OCR dapat disunting kembali jika terdapat kesalahan sebelum melakukan proses ekspor dokumen.</p>
            </div>

            <!-- Feature 3 -->
            <div class="p-6 bg-slate-900/40 rounded-2xl border border-slate-800/80 hover:border-indigo-500/35 transition-all-300">
                <div class="w-12 h-12 bg-indigo-500/10 text-indigo-400 rounded-xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Ekspor PDF & TXT Instan</h3>
                <p class="text-sm text-slate-400 leading-relaxed">Satu klik untuk mengunduh hasil ekstraksi dokumen sebagai berkas TXT bersih atau dokumen berformat PDF (Dompdf).</p>
            </div>
        </div>
    </div>
</section>

<!-- Security Assurance Callout -->
<section class="py-16 bg-[#0b0f19] border-t border-slate-800/60">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-indigo-500/10 text-indigo-400 rounded-full mb-6">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
        </div>
        <h3 class="text-2xl font-bold text-white mb-3">Keamanan Data Terjamin</h3>
        <p class="text-slate-400 max-w-xl mx-auto text-sm leading-relaxed mb-6">
            Kami menjaga kerahasiaan unggahan berkas gambar Anda. Berkas dilindungi enkripsi session yang ketat dan tidak dapat diakses oleh publik secara langsung.
        </p>
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">100% Secure Private Session Authentication</span>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="py-20 bg-[#080b13] border-t border-slate-800/60">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-white mb-3">Pertanyaan Umum (FAQ)</h2>
            <p class="text-slate-400 text-sm">Menjawab segala keraguan Anda mengenai penggunaan platform.</p>
        </div>

        <div class="space-y-4">
            <!-- FAQ Item 1 -->
            <div class="bg-slate-900/40 rounded-xl p-5 border border-slate-800/80">
                <h4 class="text-sm sm:text-base font-bold text-white mb-2">Apakah Tesseract OCR berjalan offline di server?</h4>
                <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                    Ya, aplikasi ini menggunakan eksekusi Command Line interface (CLI) Tesseract OCR secara lokal di server web XAMPP, sehingga seluruh proses berlangsung di server Anda secara langsung tanpa pihak ketiga.
                </p>
            </div>
            <!-- FAQ Item 2 -->
            <div class="bg-slate-900/40 rounded-xl p-5 border border-slate-800/80">
                <h4 class="text-sm sm:text-base font-bold text-white mb-2">Apakah saya dapat mengedit kembali teks hasil konversi?</h4>
                <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                    Tentu. Setelah proses pemindaian OCR selesai, halaman hasil akan menampilkan teks di dalam area suntingan yang interaktif sehingga Anda dapat menyesuaikan isi dokumen sebelum mengekspornya.
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
