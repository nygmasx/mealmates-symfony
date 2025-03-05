FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    intl \
    opcache \
    zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/symfony

# Copy only composer files first
COPY composer.json composer.lock ./

# Install Symfony dependencies early to optimize build caching
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copy remaining project files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/symfony/var
