# LMS production runbook

## Release

Use PHP 8.2+, Node 22 for builds, MySQL, HTTPS, and a persistent Laravel cache.
Keep the web root restricted to `public/`. Delete `public/export-database.php`
from already deployed servers: an upload alone may leave the removed file behind.
Never upload `.env`, backups, test databases, or repository metadata to the web root.

Set the server environment (do not copy the local development `.env`):

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=180
CACHE_STORE=database
BACKUP_ENABLED=true
BACKUP_PATH=/srv/lms-backups
MYSQLDUMP_BINARY=/usr/bin/mysqldump
```

Preserve the existing APP_KEY during upgrades. Configure real mail credentials and
the existing WhatsApp/OpenClaw integration separately. Secrets belong on the server.

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan queue:restart
php artisan lms:production-check
```

The production check exits nonzero on configuration problems. It does not prove
that cron, the process manager, or an external provider is running.

## Scheduled work and workers

Install this cron entry as the application user, using the correct PHP and project path:

```cron
* * * * * cd /srv/lms && /usr/bin/php artisan schedule:run >> /var/log/lms-scheduler.log 2>&1
```

Run `php artisan queue:work --sleep=3 --tries=3 --timeout=120` under systemd or
Supervisor with automatic restart. Configure the queue connection retry_after
greater than the worker timeout (for example 180 seconds). On shared hosting,
confirm the provider supports persistent workers before enabling queued delivery.

An external scheduler may call POST `/scheduler/run` once per hour using an
`Authorization: Bearer <SCHEDULER_TOKEN>` header. Query-string tokens and GET
requests are no longer accepted. This endpoint runs WhatsApp triggers only; it
does not replace the full Laravel scheduler for backups, analytics or reminders.
Use a shared persistent cache for locks. Do not run multiple independent caches.

Check `php artisan queue:failed`, application logs, and provider delivery/outbox
records daily. Retry failed jobs only after fixing the cause. Monitor `/up` and
alert on HTTP failures, worker exits, failed backups, and disk-space pressure.

## Backups and restore drill

Run `php artisan lms:backup`. It creates a private ZIP containing a consistent
database dump, public/private uploads, and SHA-256 manifest, then reads each
archive entry back and verifies its checksum. SQLite also gets an integrity check.
MySQL backups require mysqldump, sufficient database permissions, and InnoDB tables
for transactional consistency. Pause uploads/content edits while taking a release
backup if cross-file/database consistency is required.

Backups can contain private student data. Restrict permissions, encrypt off-server
copies using the hosting provider, retain a documented history, and monitor storage.
No automatic deletion is configured. Keep APP_KEY and server secrets separately in
the organization's secret manager; they are deliberately excluded from archives.

Before handover, restore an archive into a NEW disposable database and storage
directory, never over the live database. Verify manifest hashes, import database.sql
with the MySQL client (or open database.sqlite and run PRAGMA integrity_check),
restore uploads/public and uploads/private, configure the restored application,
and test login, course content, private resources, and certificates. Disable all
outbound notifications in the restored environment. Record the restore date,
backup name, result, and recovery duration. Local archive checks are not a substitute
for this hosting-specific restore drill.

## Client acceptance

1. Open a course's Edit page and resolve its Content Checklist items.
2. Set the actual duration in seconds on each lesson's Edit page.
3. Confirm course/module/lesson access rules with a non-admin test student.
4. Enroll, play and resume a lesson; confirm rapid heartbeats cannot inflate watch time.
5. Complete required reflections, reviews, tasks, and quizzes; download the certificate.
6. Repeat completion requests and confirm no duplicate rewards/certificates.
7. Ask a question, send an admin reply, and confirm internal notes stay private.
8. Verify email and WhatsApp delivery with consenting test recipients.
9. Check desktop and mobile forms, then complete the backup restore drill above.

Admin mutations are logged as `admin.change` activity events with route, method,
path and changed field names; sensitive values are not logged. Existing user activity
screens can inspect these records. New Questions screens support replies, resolution,
staff assignments, internal notes and audio attachments.

The checklist is advisory; this release does not introduce a draft/publish state,
impersonation, bulk student import, billing, live classes, or a native mobile app.
These require separate workflow and business decisions. Multilingual content fields
already exist and are preserved when editing courses.
