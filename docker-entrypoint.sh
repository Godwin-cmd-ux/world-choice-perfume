#!/bin/bash

echo "=== Generating .env from environment ==="
cat > /var/www/html/.env <<EOF
APP_NAME="World Choice Perfumes"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=true
APP_URL=${APP_URL}
LOG_CHANNEL=stderr
LOG_LEVEL=debug
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
CACHE_STORE=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log
SUPABASE_URL=${SUPABASE_URL}
SUPABASE_ANON_KEY=${SUPABASE_ANON_KEY}
SUPABASE_SERVICE_ROLE_KEY=${SUPABASE_SERVICE_ROLE_KEY}
CLOUDINARY_CLOUD_NAME=${CLOUDINARY_CLOUD_NAME}
CLOUDINARY_API_KEY=${CLOUDINARY_API_KEY}
CLOUDINARY_API_SECRET=${CLOUDINARY_API_SECRET}
SUPER_ADMIN_SECRET=${SUPER_ADMIN_SECRET}
EOF

echo "=== Verifying .env ==="
cat /var/www/html/.env | head -5

echo "=== Verifying APP_KEY ==="
echo "APP_KEY length: $(echo -n "$APP_KEY" | wc -c)"

echo "=== Setting up database ==="
rm -f /var/www/html/database/database.sqlite
touch /var/www/html/database/database.sqlite

echo "=== Running migrations ==="
php artisan migrate --force 2>&1 || echo "Migration warning"

echo "=== Testing app boot ==="
php artisan route:list --columns=method,uri,name 2>&1 | head -5 || echo "Route list failed"

echo "=== Done ==="
