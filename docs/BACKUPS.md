# Nightly database backups

Every night at **00:00 Tanzania time** the app dumps every table in the database
and files the result in Cloudinary, one folder per day:

```
4ftdshm54xvhks/backup_2026-10-29/schema_2026-10-29.sql   the structure (a copy of SYSTEM_SCHEMA.sql)
4ftdshm54xvhks/backup_2026-10-29/seed_2026-10-29.sql     every row of every table
```

The two files together are a complete restore: `schema` creates the tables,
`seed` fills them. The schema is copied into each folder (rather than assumed to
still exist somewhere) so an old backup keeps working even if `SYSTEM_SCHEMA.sql`
is edited later.

The schedule itself lives in `routes/console.php`; the work is done by
`App\Services\DatabaseBackupService`.

## Turn it on (VPS)

Laravel's scheduler is not a daemon — it only runs when something calls it. On a
VPS that is one cron line, added once as the user that owns the app files:

```bash
crontab -e
```

```cron
* * * * * cd /var/www/world-choice-perfumes && /usr/bin/php artisan schedule:run >> /var/log/wcp-schedule.log 2>&1
```

Use the real project path and the PHP binary your site actually runs on
(`which php`). That is the whole setup — **do not** also add a `0 0 * * *` line
for the backup itself; the schedule already contains the midnight entry, and a
second trigger would just compete with it.

Confirm the app agrees with you:

```bash
php artisan schedule:list
#   0 21 * * *  database-backup .......... Next Due: ...
```

`0 21` is UTC (the app's timezone); with the schedule pinned to
`Africa/Dar_es_Salaam` that is 00:00 midnight in Tanzania, once a day.

## See and delete them in the app

Super Admin → **Backups** (`/super-admin/backups`) lists every backup by date,
with each file's size and a download button for both halves. The trash button
deletes that day's two files from Cloudinary and then the row from the
`backups` table; if Cloudinary refuses, the row stays and the page says why, so
the list never shows a backup that is already gone.

The page reads the `backups` table, which each night's run writes to. On load it
also asks Cloudinary for folders the table does not know about and records them,
so a backup taken before the page existed — or one whose row failed to write —
still appears and can be deleted.

## Take one now

Any of these:

- Sign in as super admin and open `/backup/run` — the JSON reply lists the
  folder, both file URLs, the row count per table and how long it took.
- With `BACKUP_TRIGGER_TOKEN` set in `.env`:
  `curl "https://your-domain/backup/run?token=$BACKUP_TRIGGER_TOKEN"`
- From the command line:
  `php artisan tinker --execute="dump(app(App\Services\DatabaseBackupService::class)->run());"`

Run it once by hand after setting up cron, so you know cron itself works rather
than finding out a month later that it never did.

## Restore a backup

Download that day's two files, then against the empty database:

```bash
psql "$DATABASE_URL" -f schema_2026-10-29.sql
psql "$DATABASE_URL" -f seed_2026-10-29.sql
```

In Supabase's SQL editor, paste and run `schema` first, then `seed`. The seed
ends by moving every id sequence past the highest restored id, so the app can
insert new rows straight away.

## What a backup needs

- `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET` in
  `.env` — without them the run fails with the reason in the response and
  `storage/logs/laravel.log`.
- `SYSTEM_SCHEMA.sql` at the project root. It is tracked in git, so a normal
  deploy has it.
- Outbound HTTPS to Supabase and `api.cloudinary.com` from PHP CLI (not just
  from PHP-FPM).

Nothing is written to the server's disk: the dump is built in memory and
uploaded straight to Cloudinary, so a backup never fills the VPS.

If the site is ever deployed as the Docker image instead of a git checkout, the
same cron line goes on the host, calling
`docker exec <container> php artisan schedule:run`.
