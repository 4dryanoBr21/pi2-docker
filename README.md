mkdir -p nginx/certs

openssl req -x509 -nodes -newkey rsa:2048 \
  -keyout nginx/certs/selfsigned.key \
  -out nginx/certs/selfsigned.crt \
  -days 365 \
  -subj "/CN=localhost" \
  -addext "subjectAltName=DNS:localhost,IP:SEU_IP_LOCAL,IP:127.0.0.1"