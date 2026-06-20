#!/bin/bash
set -e

echo "=== Starting Apache ==="
echo "PORT env var: ${PORT:-not set, using 80}"

# Pastikan Apache listen di 0.0.0.0:80 (port yang sudah di-set di Railway Generate Domain)
echo "Listen 80" > /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:.*>/<VirtualHost *:80>/" /etc/apache2/sites-available/000-default.conf

echo "=== DEBUG: MPM modules enabled ==="
ls -la /etc/apache2/mods-enabled/ | grep -i mpm || echo "no mpm found"
echo "=== DEBUG: ports.conf content ==="
cat /etc/apache2/ports.conf
echo "==================================="

exec apache2-foreground