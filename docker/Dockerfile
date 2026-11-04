# docker/Dockerfile
# PHP 8.2 Apache production & standalone image for Amazon Mini Clone

FROM php:8.2-apache

# Install PDO MySQL extension and utilities
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite headers

# Copy custom Apache clean logging configuration and php.ini
COPY docker/apache-clean-log.conf /etc/apache2/conf-available/clean-log.conf
RUN a2enconf clean-log
COPY docker/apache-security.conf /etc/apache2/conf-available/app-security.conf
RUN a2enconf app-security
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy application files into Apache document root
WORKDIR /var/www/html
COPY . /var/www/html/

# Ensure web server ownership
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

HEALTHCHECK --interval=15s --timeout=5s --start-period=10s --retries=5 \
    CMD curl -fsS "http://localhost/index.php?route=health" || exit 1

CMD ["apache2-foreground"]
