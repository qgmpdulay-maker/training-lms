# Setup, Configuration, and Operations

## Requirements

- PHP 8.3+ (the project pins `platform.php` to 8.3.19)
- Composer
- Node 18+ / npm
- MySQL 8 (development and production both use MySQL; `.env.example` still
  ships the Laravel default of SQLite — change `DB_CONNECTION` when copying it)

## Local setup

```bash
composer setup
```

That runs `composer install`, copies `.env.example` to `.env` if missing,
generates the app key, migrates, installs npm packages, and builds assets.

Then point `.env` at your database and seed:

```bash
php artisan migrate:fresh --seed
```

Run everything (server, queue worker, log tailer, Vite) with one command:

```bash
composer dev
```

Or just the app server — this is also what `.claude/launch.json` starts:

```bash
php artisan serve
```

## Seeders

| Seeder | What it creates |
| --- | --- |
| `DatabaseSeeder` | Three accounts: `test@example.com` (participant), `admin@ocd.gov.ph` (Regional Admin, Region III), `superadmin@ocd.gov.ph` (Super Admin). Run by `--seed` |
| `NationwideDemoSeeder` | Realistic nationwide volume — participants, instructors, trainings, evaluations, TNAs across **all 18 regions**. Use this for anything chart- or scale-related |
| `DemoDataSeeder`, `DemoEnrichmentSeeder` | Smaller earlier demo sets |
| `PendingRegistrationSeeder` | Rows for the approval queue |

```bash
php artisan db:seed --class=NationwideDemoSeeder
```

`NationwideDemoSeeder` is idempotent (guarded by a marker organization name)
and uses bulk `DB::table()->insert()` rather than Eloquent, since it writes
thousands of rows. **Extend it rather than writing a new nationwide seeder.**

## Tests

```bash
composer test
```

Feature tests cover auth/Breeze basics plus the rules that are easy to break:

| Test | Guards |
| --- | --- |
| `RegionalAdminIsReadOnlyTest` | That Regional Admins can't write anything but deployments |
| `ParticipantDeploymentTest` | The deployments feature and its region scoping |
| `OrganizationTest` | Organization CRUD and membership |
| `PromoteToAdminTest` | Role promotion/demotion |

## Configuration

### Environment

| Variable | Notes |
| --- | --- |
| `DB_*` | MySQL connection |
| `MAIL_*` | Required for OTP verification and approval emails. With `MAIL_MAILER=log`, OTPs land in `storage/logs/laravel.log` instead of an inbox — fine for local work |
| `TRAINING_REQUEST_EMAIL` | Where training notifications go (default `training@ocd.gov.ph`) |
| `FILESYSTEM_DISK` | Leave as `local`; certificates depend on the private disk |

Mail failures are caught and logged rather than thrown — a broken SMTP config
degrades the flow (the user is told to resend) instead of breaking it.

### Config files that carry real data

| File | Contents | Notes |
| --- | --- | --- |
| `config/trainings.php` | The training catalog | Placeholder descriptions pending real course content. **Slugs must stay unique and stable** — trainings reference a course by slug |
| `config/regions.php` | `list` (18 regions), `agency_map` (OCD office → region), `geo` (map centroids) | NIR is intentionally unmapped: no matching regional admin scope exists yet |
| `config/cities.php` | City autocomplete source | |
| `config/tna_*.php` | TNA question sets per participant type | `tna_common.php` holds text shared verbatim across them |

Both `trainings.php` and `regions.php` carry TODOs to move to real tables if
they ever need their own attributes.

## Storage

`php artisan storage:link` is required for the `public` disk (instructor
photos, participant pictures, ATAR uploads, TNA attachments). Certificates do
**not** use it — they live on the private disk and are streamed by the app.

## Deployment notes

A trial deployment runs on InfinityFree shared hosting. Things that needed
adjusting there, in case they resurface:

- `storage:link` symlinks don't survive; the document root is `htdocs/`
- The PHP version must be pinned to a supported 8.3 build
- SMTP credentials differ from local
- `.htaccess` at the project root handles routing into `public/`

Certificates issued before they were moved to the private disk may still exist
on the public disk on a long-lived deployment — `CertificateController::download()`
probes both, so old links keep working, but those files should be migrated.

## Code style

```bash
./vendor/bin/pint
```

Conventions worth matching:

- Mass assignment uses the `#[Fillable([...])]` attribute, not `$fillable`
- Labels live in static `$xLabels` arrays on the model, with a
  `xLabel()` accessor that falls back to the raw value so legacy data still
  renders
- Comments explain *why* a constraint exists, not what the line does — the
  existing comments are load-bearing context; keep that density when editing
