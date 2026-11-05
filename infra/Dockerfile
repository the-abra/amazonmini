# ============================================
# Stage 1: Base PHP Image
# ============================================
FROM php:8.2-apache AS base

LABEL org.opencontainers.image.source="https://github.com/the-abra/amazonmini"
LABEL org.opencontainers.image.description="Amazon Mini - E-Commerce Platform"
LABEL org.opencontainers.image.licenses="MIT"

# Sistem bağımlılıkları
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# PHP Eklentileri
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    pdo \
    pdo_mysql \
    mysqli \
    mbstring \
    zip

# Apache Modülleri
RUN a2enmod rewrite headers expires deflate

# Üretim PHP Ayarları
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'expose_php = Off'; \
        echo 'display_errors = Off'; \
        echo 'display_startup_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /var/log/apache2/php_errors.log'; \
        echo 'max_execution_time = 30'; \
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

# Gerekli proje dosyalarını doğrudan köke aktar
COPY --chown=www-data:www-data app ./app
COPY --chown=www-data:www-data assets ./assets
COPY --chown=www-data:www-data config ./config
COPY --chown=www-data:www-data database ./database
COPY --chown=www-data:www-data index.php ./index.php

# Log dizini oluşturma ve yetkilendirme
RUN mkdir -p /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/logs

# Apache DocumentRoot yapılandırması (.htaccess çalışması için)
RUN { \
        echo '<Directory /var/www/html>'; \
        echo '    Options -Indexes +FollowSymLinks'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } >> /etc/apache2/sites-available/000-default.conf

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=20s --retries=3 \
    CMD curl -f http://localhost/index.php || exit 1

EXPOSE 80

ENV APP_ENV=production \
    APP_URL=http://localhost:8080 \
    DB_HOST=mysql \
    DB_PORT=3306 \
    DB_NAME=ecommerce_db \
    DB_USER=ecom_user \
    DB_PASS=ecom_pass

CMD ["apache2-foreground"]

# ============================================
# Stage 3: Development Build (Local Mount)
# ============================================
FROM base AS development

WORKDIR /var/www/html

RUN { \
        echo 'display_errors = On'; \
        echo 'display_startup_errors = On'; \
        echo 'error_reporting = E_ALL'; \
    } > "$PHP_INI_DIR/conf.d/dev.ini"

COPY --chown=www-data:www-data . .

RUN { \
        echo '<Directory /var/www/html>'; \
        echo '    Options -Indexes +FollowSymLinks'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } >> /etc/apache2/sites-available/000-default.conf

ENV APP_ENV=development

EXPOSE 80

CMD ["apache2-foreground"]
