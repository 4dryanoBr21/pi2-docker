FROM php:8.2-apache

# Extensão mysqli (usada em conexao.php) + mod_rewrite (caso haja .htaccess)
RUN docker-php-ext-install mysqli \
    && a2enmod rewrite

# Permite que .htaccess sobrescreva configurações no DocumentRoot
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html
