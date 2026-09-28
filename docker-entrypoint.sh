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
# MAIL_MAILER is not forced to "log" here: write_env() below passes through
# whatever the Render environment says, so a real transport can be selected.
printf 'SUPABASE_URL=%s\n' "$SUPABASE_URL" >> /var/www/html/.env
printf 'SUPABASE_ANON_KEY=%s\n' "$SUPABASE_ANON_KEY" >> /var/www/html/.env
printf 'SUPABASE_SERVICE_ROLE_KEY=%s\n' "$SUPABASE_SERVICE_ROLE_KEY" >> /var/www/html/.env
printf 'CLOUDINARY_CLOUD_NAME=%s\n' "$CLOUDINARY_CLOUD_NAME" >> /var/www/html/.env
printf 'CLOUDINARY_API_KEY=%s\n' "$CLOUDINARY_API_KEY" >> /var/www/html/.env
printf 'CLOUDINARY_API_SECRET=%s\n' "$CLOUDINARY_API_SECRET" >> /var/www/html/.env
printf 'SUPER_ADMIN_SECRET=%s\n' "$SUPER_ADMIN_SECRET" >> /var/www/html/.env

# --- info@worldchoiceperfume.com mailbox (Customer Care -> Mails) -------------
# Only variables that are actually set are written, so a missing one stays
# unset instead of becoming an empty string that Laravel reads as a value.
#
# The name and the value are both passed in: `${!1}` (indirect expansion) is a
# bash feature, and this script is run with `sh`, so using it killed the deploy
# with "Bad substitution". Referencing "$2" works in every shell.
write_env() {
    if [ -z "$2" ]; then
        echo "  $1: (NOT SET)"
        return
    fi

    # Render hands over whatever was typed, so INFO_MAIL_NAME arrives as
    # World Choice Perfumes with spaces and no quotes. Written out raw that is
    # not a valid dotenv line, and every artisan command then dies with
    # "Failed to parse dotenv file" — which is what broke the deploy.
    # Surrounding quotes are stripped, then the value is always re-quoted.
    # Escaping is done in a single pass so a backslash is never doubled twice;
    # the /g flag matters because a value may hold several of either.
    value=$(printf '%s' "$2" | sed -e 's/^"\(.*\)"$/\1/' -e "s/^[[:space:]]*'\(.*\)'[[:space:]]*\$/\1/")
    value=$(printf '%s' "$value" | sed -e 's/[\\"]/\\&/g' | tr -d '\n\r')

    printf '%s="%s"\n' "$1" "$value" >> /var/www/html/.env
    echo "  $1: set"
}

echo "=== Writing optional env vars ==="
write_env EMAIL_RECEIVING_WEBHOOK "${EMAIL_RECEIVING_WEBHOOK:-}"
write_env INFO_MAIL_ADDRESS "${INFO_MAIL_ADDRESS:-}"
write_env INFO_MAIL_NAME "${INFO_MAIL_NAME:-}"
write_env INFO_MAIL_INBOUND_URL "${INFO_MAIL_INBOUND_URL:-}"
write_env INFO_MAILS_PER_PAGE "${INFO_MAILS_PER_PAGE:-}"

# Until a sending provider is configured, mail must be written to the log
# rather than attempted over an unconfigured SMTP host.
if [ -n "${MAIL_MAILER:-}" ]; then
    write_env MAIL_MAILER "$MAIL_MAILER"
else
    printf 'MAIL_MAILER=log\n' >> /var/www/html/.env
    echo "  MAIL_MAILER: defaulting to log (set MAIL_MAILER on Render to send)"
fi

write_env MAIL_HOST "${MAIL_HOST:-}"
write_env MAIL_PORT "${MAIL_PORT:-}"
write_env MAIL_USERNAME "${MAIL_USERNAME:-}"
write_env MAIL_PASSWORD "${MAIL_PASSWORD:-}"
write_env MAIL_ENCRYPTION "${MAIL_ENCRYPTION:-}"
write_env MAIL_FROM_ADDRESS "${MAIL_FROM_ADDRESS:-}"
write_env MAIL_FROM_NAME "${MAIL_FROM_NAME:-}"
write_env RESEND_API_KEY "${RESEND_API_KEY:-}"

# The resend transport reads RESEND_API_KEY from the environment, so selecting
# it without a key does not fail at boot, it fails on every single send. That
# reads like the mail feature is broken rather than misconfigured, so it is
# called out loudly here instead.
if [ "${MAIL_MAILER:-}" = "resend" ] && [ -z "${RESEND_API_KEY:-}" ]; then
    echo "!!! WARNING: MAIL_MAILER is 'resend' but RESEND_API_KEY is not set."
    echo "!!! Every outgoing mail will fail. Add RESEND_API_KEY on Render."
fi

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
