#!/usr/bin/env bash
set -euo pipefail

# Black Cat Console — safe local bootstrap for Debian 13+
# Installs the Console on the existing Black Cat Core host but does NOT expose it publicly.
# Run as root on blackcat-chatbot-01.

REPO_URL="${REPO_URL:-https://github.com/efrgdgr0024345/portable-trusted-linux.git}"
INSTALL_ROOT="${INSTALL_ROOT:-/opt/blackcat}"
APP_ROOT="$INSTALL_ROOT/portable-trusted-linux/apps/blackcat-console"
ENV_DIR="${ENV_DIR:-/etc/blackcat-console}"
ENV_FILE="$ENV_DIR/console.env"
NGINX_SITE="/etc/nginx/sites-available/blackcat-console-local"

if [[ "${EUID}" -ne 0 ]]; then
  echo "ERROR: run this bootstrap as root." >&2
  exit 1
fi

if [[ -z "${BLACKCAT_CONSOLE_PASSWORD:-}" ]]; then
  echo "ERROR: BLACKCAT_CONSOLE_PASSWORD must be supplied in the environment." >&2
  echo "Example: BLACKCAT_CONSOLE_PASSWORD='use-a-long-random-password' bash bootstrap-local.sh" >&2
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y --no-install-recommends \
  git nginx php-fpm php-cli php-curl ca-certificates curl jq dnsutils

mkdir -p "$INSTALL_ROOT" "$ENV_DIR"
chmod 0750 "$INSTALL_ROOT" "$ENV_DIR"

if [[ -d "$INSTALL_ROOT/portable-trusted-linux/.git" ]]; then
  git -C "$INSTALL_ROOT/portable-trusted-linux" fetch --prune origin
  git -C "$INSTALL_ROOT/portable-trusted-linux" checkout -f main
  git -C "$INSTALL_ROOT/portable-trusted-linux" reset --hard origin/main
else
  rm -rf "$INSTALL_ROOT/portable-trusted-linux"
  git clone --branch main --single-branch "$REPO_URL" "$INSTALL_ROOT/portable-trusted-linux"
fi

PHP_FPM_SOCK="$(find /run/php -maxdepth 1 -type s -name 'php*-fpm.sock' 2>/dev/null | sort -V | tail -n1 || true)"
if [[ -z "$PHP_FPM_SOCK" ]]; then
  echo "ERROR: PHP-FPM socket not found under /run/php." >&2
  exit 1
fi

cat > "$ENV_FILE" <<EOF
BLACKCAT_CONSOLE_PASSWORD=${BLACKCAT_CONSOLE_PASSWORD}
BLACKCAT_COACH_MODEL=${BLACKCAT_COACH_MODEL:-gpt-5.6-terra}
BLACKCAT_CHAT_MODEL=${BLACKCAT_CHAT_MODEL:-gpt-5.6-sol}
EOF
if [[ -n "${OPENAI_API_KEY:-}" ]]; then
  printf 'OPENAI_API_KEY=%s\n' "$OPENAI_API_KEY" >> "$ENV_FILE"
fi
chmod 0600 "$ENV_FILE"

# Keep runtime data outside the public repository checkout.
mkdir -p /var/lib/blackcat-console/projects
chown -R www-data:www-data /var/lib/blackcat-console
chmod 0700 /var/lib/blackcat-console /var/lib/blackcat-console/projects

# Replace the app data directory with a private symlink.
rm -rf "$APP_ROOT/data"
ln -s /var/lib/blackcat-console "$APP_ROOT/data"
chown -h www-data:www-data "$APP_ROOT/data"

# Expose environment variables to PHP-FPM without putting secrets in web files.
POOL_FILE="$(find /etc/php -path '*/fpm/pool.d/www.conf' | sort -V | tail -n1)"
if [[ -z "$POOL_FILE" ]]; then
  echo "ERROR: PHP-FPM www.conf not found." >&2
  exit 1
fi

BEGIN_MARK='; BLACKCAT-CONSOLE-BEGIN'
END_MARK='; BLACKCAT-CONSOLE-END'
sed -i "/${BEGIN_MARK}/,/${END_MARK}/d" "$POOL_FILE"
{
  echo "$BEGIN_MARK"
  while IFS='=' read -r k v; do
    [[ -z "$k" ]] && continue
    # Escape backslashes, quotes and dollar signs for FPM env syntax.
    esc="${v//\\/\\\\}"
    esc="${esc//\"/\\\"}"
    printf 'env[%s] = "%s"\n' "$k" "$esc"
  done < "$ENV_FILE"
  echo "$END_MARK"
} >> "$POOL_FILE"

systemctl restart "$(systemctl list-units --type=service --all --no-legend | awk '/php.*fpm.service/{print $1; exit}')"

cat > "$NGINX_SITE" <<EOF
server {
    listen 127.0.0.1:8080;
    server_name localhost;
    root $APP_ROOT;
    index index.php;

    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer" always;

    location /data/ { deny all; return 404; }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$PHP_FPM_SOCK;
    }
}
EOF

ln -sfn "$NGINX_SITE" /etc/nginx/sites-enabled/blackcat-console-local
nginx -t
systemctl enable --now nginx
systemctl reload nginx

php -l "$APP_ROOT/index.php"

HTTP_CODE="$(curl -sS -o /tmp/blackcat-console-bootstrap.html -w '%{http_code}' http://127.0.0.1:8080/ || true)"
if [[ "$HTTP_CODE" != "200" ]]; then
  echo "ERROR: local Console health check returned HTTP $HTTP_CODE" >&2
  sed -n '1,60p' /tmp/blackcat-console-bootstrap.html >&2 || true
  exit 1
fi

if ! grep -qiE 'Black Cat Console|Setup locked|login' /tmp/blackcat-console-bootstrap.html; then
  echo "ERROR: local Console response did not contain an expected marker." >&2
  exit 1
fi

cat <<OUT
BLACK CAT CONSOLE LOCAL BOOTSTRAP: PASS

Installed repository: $INSTALL_ROOT/portable-trusted-linux
Application root:     $APP_ROOT
Private data:         /var/lib/blackcat-console
Environment file:     $ENV_FILE
Local URL only:       http://127.0.0.1:8080/

The Console is NOT publicly exposed yet.
Run publish-https.sh only after the chosen DNS name resolves to this droplet.
OUT
