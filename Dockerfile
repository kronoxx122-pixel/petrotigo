# Usa una imagen base con PHP y Apache
FROM php:8.2-apache

# 1. Instala las librerías del sistema
RUN apt-get update && \
    apt-get install -y \
        libpq-dev \
        libicu-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 2. Instala las extensiones de PHP
RUN docker-php-ext-install pdo pdo_pgsql intl

# 3. Habilitar mod_rewrite para .htaccess
RUN a2enmod rewrite

# 4. Permitir que .htaccess funcione en /var/www/html
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Copia todo el código de tu proyecto al directorio del servidor web
COPY . /var/www/html

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Puerto de escucha
EXPOSE 80
