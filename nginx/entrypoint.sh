#!/bin/sh
set -e

# Define se o proxy vai subir com HTTPS (certificado autoassinado) ou
# apenas em HTTP simples. Controlado pela variável de ambiente USE_HTTPS
# (definida no .env, lida pelo docker-compose.yml). Aceita "true"/"false"
# (qualquer variação de maiúsculas/minúsculas); qualquer outro valor cai
# no padrão seguro (HTTPS).
USE_HTTPS_NORMALIZADO=$(echo "${USE_HTTPS:-true}" | tr '[:upper:]' '[:lower:]')

if [ "$USE_HTTPS_NORMALIZADO" = "false" ]; then
    if [ ! -f /etc/nginx/conf-templates/http.conf ]; then
        echo "[entrypoint] Template http.conf não encontrado." >&2
        exit 1
    fi
    echo "[entrypoint] USE_HTTPS=false -> subindo apenas em HTTP (porta 80)."
    cp /etc/nginx/conf-templates/http.conf /etc/nginx/conf.d/default.conf
else
    if [ ! -f /etc/nginx/conf-templates/https.conf ]; then
        echo "[entrypoint] Template https.conf não encontrado." >&2
        exit 1
    fi
    if [ ! -f /etc/nginx/certs/selfsigned.crt ] || [ ! -f /etc/nginx/certs/selfsigned.key ]; then
        echo "[entrypoint] AVISO: certificado autoassinado não encontrado em nginx/certs/." >&2
        echo "[entrypoint] Gere-o com as instruções do README.md ou defina USE_HTTPS=false." >&2
    fi
    echo "[entrypoint] USE_HTTPS=true -> subindo com HTTPS (porta 443) e redirecionamento de HTTP para HTTPS."
    cp /etc/nginx/conf-templates/https.conf /etc/nginx/conf.d/default.conf
fi

exec nginx -g "daemon off;"
