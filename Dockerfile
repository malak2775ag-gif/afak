FROM php:8.2-apache

# Install system dependencies for PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Configure and install required PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip intl exif

# Enable Apache mod_rewrite for clean URLs (if you use .htaccess)
RUN a2enmod rewrite

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy the application source code to the container
COPY . .

# Ensure upload directories exist and set correct permissions for Apache (www-data)
RUN mkdir -p uploads/materials uploads/submissions uploads/payments uploads/avatars uploads/covers \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/uploads

# Expose port 80 to allow traffic to the web server
EXPOSE 80

# The default entrypoint for php:apache starts the web server automatically