#!/bin/sh
set -e

echo "=== Generating .env from environment ==="
cat > /var/www/html/.env <<EOF
APP_NAME="World Choice Perfumes"
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost:8000}

LOG_CHANNEL=${LOG_CHANNEL:-stderr}
LOG_LEVEL=debug

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

SESSION_DRIVER=${SESSION_DRIVER:-file}
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

CACHE_STORE=${CACHE_STORE:-file}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}

MAIL_MAILER=log

SUPABASE_URL=${SUPABASE_URL:-}
SUPABASE_ANON_KEY=${SUPABASE_ANON_KEY:-}
SUPABASE_SERVICE_ROLE_KEY=${SUPABASE_SERVICE_ROLE_KEY:-}

CLOUDINARY_CLOUD_NAME=${CLOUDINARY_CLOUD_NAME:-}
CLOUDINARY_API_KEY=${CLOUDINARY_API_KEY:-}
CLOUDINARY_API_SECRET=${CLOUDINARY_API_SECRET:-}

SUPER_ADMIN_SECRET=${SUPER_ADMIN_SECRET:-}
EOF

echo "=== Preparing database ==="
rm -f /var/www/html/database/database.sqlite
touch /var/www/html/database/database.sqlite

echo "=== Generating APP_KEY ==="
php artisan key:generate --force

echo "=== Clearing caches ==="
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "=== Running migrations ==="
php artisan migrate --force

echo "=== Starting server on port ${PORT:-8000} ==="
exec php -S 0.0.0.0:${PORT:-8000} -t /var/www/html/public /var/www/html/public/index.php
