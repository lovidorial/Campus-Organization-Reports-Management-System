# Orgtrack

**Orgtrack: A Web-Based Monitoring System for Campus Student Organization Narrative, Accomplishment and Summary Reports.**

Orgtrack helps campus student organizations and administrators track General Plan of Action (GPOA) submissions, planned activities, activity requests, and reports.

## Student Organizations

- Submit a GPOA PDF and enter planned activities, or import activity rows from a DOCX or XLSX file for review before submission.
- View GPOAs and activity progress from the dashboard and activity monitor. Activities are tracked as pending, ongoing, completed, late, or archived where applicable.
- Create activity requests for planned activities, provide scheduling and venue details, submit communication letters, and follow request and document status.
- Submit narrative reports as uploaded PDFs or generate them from report text. Supporting photos and attendance sheets can also be submitted.
- Use the activity calendar, maintain organization officers and members, and export personal backup data.

## Administrators (OSDW)

- Monitor GPOAs, planned activities, activity requests, reports, and organization progress through dashboards and filtered activity lists.
- Record activity compliance assessments and remarks; view submitted documents and evidence.
- Review narrative reports and approve them or request revisions. GPOA records are currently marked approved when submitted by the application.
- View and export summary reports, manage organizations, user accounts, officers, organization classifications, and document deadlines.
- Review the activity calendar and venue availability, and manage database/file backups and activity logs.

The current project focus is monitoring and tracking. Broader approval workflows are planned after the defense; the existing narrative-report review actions are limited to the behavior described above.

## Requirements

- PHP 8.2 or newer with the GD and ZIP extensions enabled.
- Composer.
- Node.js and npm.
- MySQL.
- On Windows, XAMPP can provide the local PHP/MySQL stack. Ensure the PHP executable used by Composer and Artisan has the required extensions enabled.

TODO: Confirm and document supported Composer and Node.js/npm versions; the project manifests do not pin them.

## Installation

```text
git clone <repository-url>
cd <repository-directory>
composer install
npm install
```

Copy the example environment file, then configure the local application URL and MySQL connection values in `.env`:

```powershell
Copy-Item .env.example .env
```

Create an empty MySQL database and set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` for that database. Then initialize the application:

```text
php artisan key:generate
php artisan migrate
php artisan storage:link
npm run build
```

TODO: Replace `<repository-url>` with the project's clone URL and `<repository-directory>` with the directory created by that clone.

## Running Locally

Run the Laravel application and Vite development server in separate terminals:

```text
php artisan serve
```

```text
npm run dev
```

## Running Tests

```text
php artisan test
```

## Mail Setup

Password reset messages use Laravel mail configuration. Set the SMTP values in `.env` for your mail provider:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=<smtp-host>
MAIL_PORT=587
MAIL_USERNAME=<smtp-username>
MAIL_PASSWORD=<smtp-password>
MAIL_FROM_ADDRESS=<sender-address>
MAIL_FROM_NAME="Orgtrack"
```

Use the host, port, scheme, credentials, and sender address provided by your mail service. Keep real credentials out of source control. The environment example also notes Resend as a production mailer option.

## Backup and Restore

See [Backup and Recovery](docs/backup-recovery.md) for archive creation, restore steps, requirements for MySQL client tools, and recovery limitations.

## Default Roles and First Administrator

The application uses the `admin` role for administrators and the `user` role for student organization accounts. Public registration creates a regular user; it does not create an administrator.

The `AdminUserSeeder` creates or updates the initial administrator account. After configuring the database and running migrations, seed it with:

```text
php artisan db:seed --class=AdminUserSeeder
```

The seeder currently sets the email to `osdw@gmail.com` and the initial password to `admin123`. Change that password immediately after the first login and before using the application outside a local development environment.