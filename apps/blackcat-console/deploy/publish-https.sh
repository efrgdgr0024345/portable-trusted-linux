#!/usr/bin/env bash
set -euo pipefail

# Publish an already bootstrapped Black Cat Console over HTTPS.
# This script intentionally refuses to expose the app until DNS points at this host.

DOMAIN="${DOMAIN:-console.easyai.com.au}"
EMAIL="${LETSENCRYPT_EMAIL:-}"
INSTALL_ROOT="${INSTALL_ROOT:-/opt/blackcat}"
APP_ROOT="$INSTALL_ROOT/portable-trusted-linux/apps/blackcat-console"
PUBLIC_SITE="/etc/nginx/sites-available/blackcat-console"
ACME_ROOT="/var/www/blackcat-acme"

if [[ "${EUID}" -ne 0 ]]; then
  echo "ERROR: run as root." >&2
  exit 1
fi
if [[ -z "$EMAIL" ]]; then
  echo "ERROR: set LETSENCRYPT_EMAIL before publishing." >&2
  exit 1
fi
if [[ ! -f "$APP_ROOT/index.php" ]]; then
  echo "ERROR: Console is not bootstrapped at $APP_ROOT" >&2
  exit 1
fi

PUBLIC_IP="$(curl -fsS --max-time 2 http://169.254.169.254/metadata/v1/interfaces/public/0/ipv4/address || true)"
if [[ -z "$PUBLIC_IP" ]]; then
  echo "ERROR: could not determine this droplet's public IPv4 address." >&2
  exit 1
fi

mapfile -t DNS_IPS < <(dig +short A "$DOMAIN" | grep -E '^[0-9]+(\.[0-9]+){3}$' || true)
MATCH=0
for ip in "${DNS_IPS[@]:-}"; do
  [[ "$ip" == "$PUBLIC_IP" ]] && MATCH=1
 done
if [[ "$MATCH" -ne 1 ]]; then
  echo "REFUSING TO PUBLISH." >&2
  echo "$DOMAIN does not currently resolve to this droplet ($PUBLIC_IP)." >&2
  echo "Observed A records: ${DNS_IPS[*]:-(none)}" >&2
  exit 1
fi

apt-get update
apt-get install -y --no-install-recommends certbot
mkdir -p "$ACME_ROOT/.well-known/acme-challenge"

# ACME-only HTTP listener. The application is not exposed through this configuration.
cat > "$PUBLIC_SITE" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;

    location ^~ /.well-known/acme-challenge/ {
        root $ACME_ROOT;
        default_type text/plain;
    }

    location / {
        return 404;
    }
}
EOF
ln -sfn "$PUBLIC_SITE" /etc/nginx/sites-enabled/blackcat-console
nginx -t
systemctl reload nginx

certbot certonly \
  --webroot -w "$ACME_ROOT" \
  -d "$DOMAIN" \
  --email "$EMAIL" \
  --agree-tos \
  --non-interactive

PHP_FPM_SOCK="$(find /run/php -maxdepth 1 -type s -name 'php*-fpm.sock' 2>/dev/null | sort -V | tail -n1 || true)"
if [[ -z "$PHP_FPM_SOCK" ]]; then
  echo "ERROR: PHP-FPM socket not found." >&2
  exit 1
fi

cat > "$PUBLIC_SITE" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;

    location ^~ /.well-known/acme-challenge/ {
        root $ACME_ROOT;
    }

    location / {
        return 301 https://\$host\$request_uri;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name $DOMAIN;

    ssl_certificate /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;

    root $APP_ROOT;
    index index.php;

    add_header Strict-Transport-Security "max-age=31536000" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer" always;

    location /data/ { deny all; return 404; }
    location ~ /\. { deny all; }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$PHP_FPM_SOCK;
    }
}
EOF

nginx -t
systemctl reload nginx

HTTP_CODE="$(curl -sS -o /dev/null -w '%{http_code}' "https://$DOMAIN/" || true)"
if [[ "$HTTP_CODE" != "200" ]]; then
  echo "ERROR: HTTPS health check returned HTTP $HTTP_CODE" >&2
  exit 1
fi

cat <<OUT
BLACK CAT CONSOLE HTTPS PUBLISH: PASS

URL: https://$DOMAIN/
Public IPv4: $PUBLIC_IP
HTTP redirects to HTTPS.
The Console remains password-protected by BLACKCAT_CONSOLE_PASSWORD.
OUT
