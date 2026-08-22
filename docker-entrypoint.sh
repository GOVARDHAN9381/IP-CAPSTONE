#!/bin/bash
set -e

# Support dynamic PORT from cloud environments (Render, Railway, Fly.io)
PORT="${PORT:-80}"

echo "Starting Apache on port $PORT..."
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT>/g" /etc/apache2/sites-available/000-default.conf

# Ensure only mpm_prefork is loaded (resolves AH00534: More than one MPM loaded)
a2dismod -f mpm_event 2>/dev/null || true
a2dismod -f mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# Execute standard Apache foreground runner
exec apache2-foreground
