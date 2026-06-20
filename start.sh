#!/bin/bash
set -e

echo "=== Starting Apache ==="
echo "PORT env var: ${PORT:-not set, using 80}"

# Force hapus mpm_event yang ternyata muncul lagi di runtime (bukan cuma build time)
rm -f /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load

# Pastikan mpm_prefork tetap ada
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

# Pastikan Apache listen di 0.0.0.0:80 (port yang sudah di-set di Railway Generate Domain)
echo "Listen 80" > /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:.*>/<VirtualHost *:80>/" /etc/apache2/sites-available/000-default.conf

echo "=== DEBUG: MPM modules enabled ==="
ls -la /etc/apache2/mods-enabled/ | grep -i mpm || echo "no mpm found"
echo "=== DEBUG: ports.conf content ==="
cat /etc/apache2/ports.conf
echo "==================================="

exec apache2-foreground