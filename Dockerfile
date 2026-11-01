FROM php:8.3-apache

# PHP2Shell (CVE-2026-109618 e CVE-2026-109619): a correção está no PHP 8.3.36.
# Se o Docker reaproveitar uma imagem base antiga (em cache), o build para aqui
# em vez de subir um servidor vulnerável.
RUN php -r 'if (version_compare(PHP_VERSION, "8.3.36", "<")) { fwrite(STDERR, "ERRO: PHP " . PHP_VERSION . " tem as falhas PHP2Shell. Reconstrua com: docker compose build --pull --no-cache\n"); exit(1); }'

# Só mysqli é usado pelo código (functions/conexao.php).
# php.ini de PRODUÇÃO: display_errors desligado (erros vão para o log, não
# para a tela do usuário), entre outras proteções padrão.
RUN docker-php-ext-install mysqli \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Ajustes do projeto (sessão, timezone, expose_php) e endurecimento do Apache.
COPY docker/php/custom.ini "$PHP_INI_DIR/conf.d/zz-custom.ini"
COPY docker/apache/zz-app-security.conf /etc/apache2/conf-available/zz-app-security.conf
RUN a2enconf zz-app-security
RUN a2enmod headers

WORKDIR /var/www/html

# Código dentro da imagem. No docker-compose.yml o ./src é montado por cima
# (modo desenvolvimento); sem o volume, a imagem roda sozinha.
COPY src/ /var/www/html/
