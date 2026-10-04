FROM php:8.3-apache

# Só mysqli é usado pelo código (functions/conexao.php).
# php.ini de PRODUÇÃO: display_errors desligado (erros vão para o log, não
# para a tela do usuário), entre outras proteções padrão.
RUN docker-php-ext-install mysqli \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Ajustes do projeto (sessão, timezone, expose_php) e endurecimento do Apache.
COPY docker/php/custom.ini "$PHP_INI_DIR/conf.d/zz-custom.ini"
COPY docker/apache/zz-app-security.conf /etc/apache2/conf-available/zz-app-security.conf
RUN a2enconf zz-app-security

WORKDIR /var/www/html

# Código dentro da imagem. No docker-compose.yml o ./src é montado por cima
# (modo desenvolvimento); sem o volume, a imagem roda sozinha.
COPY src/ /var/www/html/
