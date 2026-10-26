# Deployment notes

## Docker and Render

The project includes a Docker image configured for Render's Docker web service.
It builds the Vite assets, installs production Composer dependencies, runs
Apache with Laravel's `public` directory as the document root, and listens on
port `10000`. The image includes MySQL, PostgreSQL, and SQLite PDO drivers.

For a local Docker test:

1. Copy `.env.example` to `.env`.
2. Generate an application key with `php artisan key:generate --show` and put
   the output in `.env` as `APP_KEY`. Keep the key private.
3. Run `docker compose up --build`.
4. Open `http://localhost:10000`.

The Compose configuration is for local testing and uses a local PostgreSQL
container. It runs database migrations when the app container starts. Render
deployments should use a separate managed database and run
`php artisan migrate --force` as the service's pre-deploy command instead.

For Render, create a Docker web service from this project and attach a managed
PostgreSQL database. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` to
a persistent secret, `APP_URL` to the Render HTTPS URL, and `DB_CONNECTION=pgsql`.
Set `ASSET_URL` to the same HTTPS URL and use `/up` as the Render health check
path.
Render's PostgreSQL connection URL is available as `DATABASE_URL`; the image
maps it to Laravel's `DB_URL` setting. Do not set `DB_URL` separately unless
you intend to override that URL. Configure `SESSION_SECURE_COOKIE=true` for
HTTPS. Keep all credentials and keys in Render's environment/secret settings,
not in the repository.

The container filesystem is ephemeral. Configure persistent external storage
for uploaded files (for example, the S3-compatible disk supported by this
application) before relying on attachments or other file uploads. The PHP
image allows the existing 50 MB attachment limit and 52 MB request body.

## Production release checklist

Use PHP 8.2 or newer and a database supported by Laravel. Point the web server's
document root at the application's `public` directory; do not expose the
repository root, `.env`, or private storage to the web. Provision the database,
HTTPS, persistent storage, and a process supervisor for queue workers before
opening the application to users.

Start from the tracked `.env.example`, then set deployment-specific values in
the host's secret/environment manager. In particular, use a persistent
`APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, the production `APP_URL`,
database credentials, a real outbound mailer if email is enabled, and
`SESSION_SECURE_COOKIE=true` when HTTPS is configured. Never generate a new
application key on each release: changing it invalidates encrypted sessions
and other encrypted application data. Do not commit production credentials.

Build and release with the equivalent of:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan view:cache
```

Run `php artisan key:generate` only once when creating a new installation that
does not already have an application key. Ensure `storage` and `bootstrap/cache`
are writable by the application user. Run a supervised queue worker for the
queues used by the application, and restart workers after each release.
Configure scheduled-task execution if scheduled commands are enabled.

Before launch, verify the HTTPS domain, sign-in and role access, database
migrations, file uploads/downloads, email/transcription integrations that are
enabled, queue processing, logs, and a tested backup/restore procedure. Review
the data-retention and access policies for clinical records and audio, and
restrict access to database and storage backups.

## Responsive layout

Clinic dashboards use responsive layouts for phones, tablets, and desktop
screens. Data tables that cannot fit a narrow screen remain readable in their
own horizontally scrollable containers. Before release, check the deployed
site on representative touch and desktop browsers, including small phone
widths, with the production fonts and content loaded.

## Persistent message attachments

Message file uploads, including images, are stored on the Laravel filesystem disk named by
`MESSAGING_ATTACHMENTS_DISK`. Configure this as a shared, persistent disk for
production; the default `local` disk is suitable only when the host preserves
`storage/app/private` across restarts and serves all app instances from the same
storage.

For the configured S3 disk, set `MESSAGING_ATTACHMENTS_DISK=s3` and provide the
usual `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, and
`AWS_BUCKET` deployment variables. Do not put credentials in source control.
Deploy the application with `php artisan migrate --force` so each message
records the disk and original filename for its attachment.

Attachments are served through an authenticated application endpoint to
authorized conversation participants. JPG, PNG, and WEBP images are displayed
inline; other files are downloaded. The upload limit is 50 MB per message.

Configure PHP with `upload_max_filesize=50M` and `post_max_size=52M` or greater.
The `composer run dev` script applies these limits to its Laravel development
server. Production PHP-FPM/Apache settings and any reverse-proxy request-body
limit must also allow at least 52 MB.

## Staff conversation list actions

Run `php artisan migrate --force` when deploying so the per-staff conversation
visibility state table is created. “Delete conversation” hides existing
messages only for the staff member who chose it; a later message makes the
conversation visible again. “Delete person” hides that staff member's direct
conversation from their list. It does not delete the staff account or anyone
else's messages; starting another direct chat makes the conversation visible
again.

## Appointment and referral scheduling

Nurses and head nurses may accept or decline appointment requests, but cannot
schedule them. Referring an accepted appointment links and cancels that source
appointment to prevent duplicate active visits. When a nurse refers a patient
to the physician or dentist, the receiving clinician accepts the internal
referral, sets its visit date and time, and the patient receives an in-app
notification. Referral appointment times are shown in both the clinician
referral list and the patient's referral list. Apply the referrals scheduling
migration before using this workflow.

## Triage model training

The cleaned consultation CSV can be evaluated and used for training with:

```powershell
php -d memory_limit=8G artisan ml:train-triage-model --csv=university_clinic_consultations_cleaned.csv
```

The CSV importer uses clinician-approved rows and maps the `Complaint`,
`Diagnosis`, `Symptoms`, and structured vital-sign columns to the same features
used by live triage. Visit IDs, timestamps, staff names, categories, and target
labels are not prediction features. Training holds out the most recently seen
20% of unique model-input groups, so identical inputs cannot appear in both
training and test sets. The model is saved only when held-out accuracy reaches
90% and Priority 1 recall is at least 95%; `--force` cannot bypass those two
requirements. F1 must reach the existing safety threshold unless `--force` is
explicitly used. Saving a model does not activate it; keep triage in shadow
mode until the model is clinically reviewed.

## Consultation transcription

The Whisper large v3 and Whisper large v3 turbo options use the configured
`GROQ_API_KEY`. Consultation audio is sent to Groq for transcription.

Student voice recordings are also stored as binary data in the
`consultation_recordings` database table. Database backups therefore include
the recordings; retain the existing 20 MB upload limit and check database
packet/storage limits when deploying. The existing private file copy is kept
for compatibility with older workflows.
