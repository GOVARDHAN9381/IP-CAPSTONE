#!/bin/bash
set -e

# Support dynamic PORT from cloud environments (Render, Railway, Fly.io)
PORT="${PORT:-80}"

echo "Starting Apache on port $PORT..."
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT>/g" /etc/apache2/sites-available/000-default.conf

# Execute standard Apache foreground runner
exec apache2-foreground
