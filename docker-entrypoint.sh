#!/bin/bash
# Generate .env file using printf (avoids heredoc CRLF issues)
printf 'APP_NAME="World Choice Perfumes"\n' > /var/www/html/.env
printf 'APP_ENV=production\n' >> /var/www/html/.env
printf 'APP_KEY=%s\n' "$APP_KEY" >> /var/www/html/.env
printf 'APP_DEBUG=true\n' >> /var/www/html/.env

# Force HTTPS in APP_URL
if [ -n "$APP_URL" ]; then
    SAFE_URL=$(echo "$APP_URL" | sed 's|^http://|https://|')
else
    SAFE_URL="https://world-choice-perfume.onrender.com"
fi
printf 'APP_URL=%s\n' "$SAFE_URL" >> /var/www/html/.env

printf 'LOG_CHANNEL=stderr\n' >> /var/www/html/.env
printf 'LOG_LEVEL=debug\n' >> /var/www/html/.env
printf 'DB_CONNECTION=sqlite\n' >> /var/www/html/.env
printf 'DB_DATABASE=/var/www/html/database/database.sqlite\n' >> /var/www/html/.env
printf 'SESSION_DRIVER=file\n' >> /var/www/html/.env
printf 'SESSION_LIFETIME=120\n' >> /var/www/html/.env
printf 'SESSION_ENCRYPT=false\n' >> /var/www/html/.env
printf 'SESSION_PATH=/\n' >> /var/www/html/.env
printf 'SESSION_DOMAIN=\n' >> /var/www/html/.env
printf 'SESSION_SECURE_COOKIE=true\n' >> /var/www/html/.env
printf 'SESSION_SAME_SITE=lax\n' >> /var/www/html/.env
printf 'CACHE_STORE=file\n' >> /var/www/html/.env
printf 'QUEUE_CONNECTION=sync\n' >> /var/www/html/.env
printf 'MAIL_MAILER=log\n' >> /var/www/html/.env
printf 'SUPABASE_URL=%s\n' "$SUPABASE_URL" >> /var/www/html/.env
printf 'SUPABASE_ANON_KEY=%s\n' "$SUPABASE_ANON_KEY" >> /var/www/html/.env
printf 'SUPABASE_SERVICE_ROLE_KEY=%s\n' "$SUPABASE_SERVICE_ROLE_KEY" >> /var/www/html/.env
printf 'CLOUDINARY_CLOUD_NAME=%s\n' "$CLOUDINARY_CLOUD_NAME" >> /var/www/html/.env
printf 'CLOUDINARY_API_KEY=%s\n' "$CLOUDINARY_API_KEY" >> /var/www/html/.env
printf 'CLOUDINARY_API_SECRET=%s\n' "$CLOUDINARY_API_SECRET" >> /var/www/html/.env
printf 'SUPER_ADMIN_SECRET=%s\n' "$SUPER_ADMIN_SECRET" >> /var/www/html/.env

echo "=== ENV VAR CHECK ==="
echo "SUPABASE_URL: ${SUPABASE_URL:-(NOT SET!)}"
echo "SUPABASE_ANON_KEY length: $(echo -n "$SUPABASE_ANON_KEY" | wc -c)"
echo "SUPABASE_SERVICE_ROLE_KEY length: $(echo -n "$SUPABASE_SERVICE_ROLE_KEY" | wc -c)"
echo "APP_KEY: ${APP_KEY:-(NOT SET!)}"
echo "APP_URL: ${APP_URL:-(NOT SET!)}"

if [ -z "$SUPABASE_URL" ] || [ -z "$SUPABASE_SERVICE_ROLE_KEY" ]; then
    echo "!!! WARNING: Critical Supabase env vars are missing! Database will not work!"
    echo "!!! Please set SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY in Render env vars!"
fi

echo "=== Verifying APP_KEY ==="
if grep -q "APP_KEY=base64:" /var/www/html/.env; then
    echo "APP_KEY is set"
else
    echo "APP_KEY is EMPTY - generating one..."
    php artisan key:generate --force
fi

echo "=== Setting up database ==="
rm -f /var/www/html/database/database.sqlite
touch /var/www/html/database/database.sqlite

echo "=== Running migrations ==="
php artisan migrate --force 2>&1 || echo "Migration done"

echo "=== Done ==="
