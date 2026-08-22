FROM php:8.2-apache

# Install PostgreSQL & MySQL PDO extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy application files (excluding those in .dockerignore)
COPY . /var/www/html/

# Create uploads directory and ensure correct permissions
RUN mkdir -p /var/www/html/assets/uploads \
    && chown -R www-data:www-data /var/www/html/assets/uploads \
    && chmod -R 775 /var/www/html/assets/uploads

# Copy and setup entrypoint script for dynamic port binding (Render/Railway $PORT)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i -e 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && a2dismod -f mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork 2>/dev/null || true

# Expose default port
EXPOSE 80 8080

ENTRYPOINT ["docker-entrypoint.sh"]
