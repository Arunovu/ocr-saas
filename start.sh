#!/bin/bash
# Railway memberikan PORT secara dinamis lewat environment variable
# Apache perlu di-set ulang setiap container start, bukan saat build
 
set -e
 
: "${PORT:=80}"
 
# Update port listen Apache sesuai PORT dari Railway
sed -i "s/Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:.*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
 
# Debug: tampilkan modul MPM yang ke-load (cek log Railway untuk lihat ini)
echo "=== DEBUG: MPM modules enabled ==="
ls -la /etc/apache2/mods-enabled/ | grep -i mpm
echo "==================================="
 
exec apache2-foreground
 