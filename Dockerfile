FROM php:8.3-apache

# Install tools commonly required by Composer packages.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer CLI from the official image.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Activate mod_rewrite (required by our MVC front controller)
# and install the PDO MySQL extension
RUN a2enmod rewrite \
    && docker-php-ext-install pdo_mysql

# Point DocumentRoot to /var/www/html/public where index.php lives
RUN sed -i \
        "s|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|" \
        /etc/apache2/sites-available/000-default.conf

# Add a <Directory> block that honours our .htaccess (AllowOverride All)
RUN printf "<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n" \
    >> /etc/apache2/sites-available/000-default.conf

# Suppress the ServerName FQDN warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
