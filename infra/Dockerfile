# Multi-stage Dockerfile for Amazon Mini E-Commerce Platform
# Production-ready image for ghcr.io/the-abra/amazonmini

# ============================================
# Stage 1: Base PHP Image
# ============================================
FROM php:8.2-apache AS base

LABEL org.opencontainers.image.source="https://github.com/the-abra/amazonmini"
LABEL org.opencontainers.image.description="Amazon Mini - E-Commerce Platform"
LABEL org.opencontainers.image.licenses="MIT"

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    pdo \
    pdo_mysql \
    mysqli \
    mbstring \
    zip

# Enable Apache modules
RUN a2enmod rewrite headers expires deflate

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'expose_php = Off'; \
        echo 'display_errors = Off'; \
        echo 'display_startup_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /var/log/apache2/php_errors.log'; \
        echo 'max_execution_time = 30'; \
        echo 'max_input_time = 60'; \
        echo 'memory_limit = 256M'; \
        echo 'post_max_size = 20M'; \
        echo 'upload_max_filesize = 20M'; \
        echo 'session.cookie_httponly = On'; \
        echo 'session.cookie_secure = On'; \
        echo 'session.use_strict_mode = On'; \
    } > "$PHP_INI_DIR/conf.d/custom.ini"

# ============================================
# Stage 2: Production Build
# ============================================
FROM base AS production

WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data app ./app
COPY --chown=www-data:www-data config ./config
COPY --chown=www-data:www-data database ./database
COPY --chown=www-data:www-data public ./public

# Copy public files to Apache document root
RUN cp -r public/* /var/www/html/ \
    && rm -rf public

# Create necessary directories
RUN mkdir -p /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/logs

# Configure Apache DocumentRoot
RUN sed -i 's|/var/www/html|/var/www/html|g' /etc/apache2/sites-available/000-default.conf \
    && { \
        echo '<Directory /var/www/html>'; \
        echo '    Options -Indexes +FollowSymLinks'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } >> /etc/apache2/sites-available/000-default.conf

# Security: Remove sensitive files if accidentally copied
RUN rm -f config/config.local.php .env

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/index.php || exit 1

# Expose port 80
EXPOSE 80

# Environment variables (override with -e or docker-compose)
ENV APP_ENV=production \
    APP_URL=http://localhost:8080 \
    DB_HOST=mysql \
    DB_PORT=3306 \
    DB_NAME=ecommerce_db \
    DB_USER=ecom_user \
    DB_PASS=ecom_pass

# Start Apache in foreground
CMD ["apache2-foreground"]

# ============================================
# Stage 3: Development Build (optional)
# ============================================
FROM base AS development

WORKDIR /var/www/html

# Install development tools
RUN apt-get update && apt-get install -y \
    git \
    vim \
    nano \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP for development
RUN { \
        echo 'display_errors = On'; \
        echo 'display_startup_errors = On'; \
        echo 'error_reporting = E_ALL'; \
    } > "$PHP_INI_DIR/conf.d/dev.ini"

# Copy all files (including dev files)
COPY --chown=www-data:www-data . .

# Configure Apache
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && { \
        echo '<Directory /var/www/html/public>'; \
        echo '    Options -Indexes +FollowSymLinks'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } >> /etc/apache2/sites-available/000-default.conf

ENV APP_ENV=development

EXPOSE 80

CMD ["apache2-foreground"]
