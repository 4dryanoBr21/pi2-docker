FROM php:8.3-apache

RUN docker-php-ext-install mysqli pdo_mysql \
    && a2enmod rewrite

# Extensão mysqli (usada em conexao.php) + mod_rewrite (caso haja .htaccess)
#RUN docker-php-ext-install mysqli \
#    && a2enmod rewrite

# Permite que .htaccess sobrescreva configurações no DocumentRoot
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html
