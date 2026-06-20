FROM php:8.2-apache

# Install Tesseract OCR + dependensi sistem
RUN apt-get update && apt-get install -y \
    tesseract-ocr \
    tesseract-ocr-ind \
    tesseract-ocr-eng \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Aktifkan mod_rewrite Apache (kalau pakai .htaccess)
RUN a2enmod rewrite

# Fix "More than one MPM loaded" secara definitif:
# Hapus SEMUA referensi MPM dari mods-available DAN mods-enabled,
# lalu buat ulang HANYA symlink mpm_prefork
RUN find /etc/apache2/mods-enabled/ -name "mpm_*" -delete \
    && rm -f /etc/apache2/mods-available/mpm_event.load \
             /etc/apache2/mods-available/mpm_worker.load \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    && apache2ctl -M 2>&1 | grep mpm || true

# Copy seluruh project ke document root Apache
COPY . /var/www/html/

# Set permission folder uploads agar bisa ditulis
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/uploads

# Copy script start yang handle PORT dinamis dari Railway
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]