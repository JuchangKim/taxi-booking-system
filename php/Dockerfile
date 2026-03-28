FROM php:8.2-apache-bullseye

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Copy your source code
COPY . /var/www/html/
