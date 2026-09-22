# Architecture

## Stack

| Layer | Choice |
| --- | --- |
| Framework | Laravel 13 on PHP 8.3+ |
| Auth scaffolding | Laravel Breeze (Blade), extended with OTP verification and Super Admin approval |
| Views | Blade + Alpine.js, no SPA |
| Styling | Tailwind CSS, built with Vite; light/dark theme per user |
| Database | MySQL in development and production (`.env.example` ships SQLite; the real `.env` uses MySQL) |
| PDFs | `barryvdh/laravel-dompdf` |
| Tests | PHPUnit 12 |
| Formatting | Laravel Pint |

Models use PHP attributes rather than properties for mass assignment —
`#[Fillable([...])]` and `#[Hidden([...])]` — so don't look for `protected $fillable`.

## Directory map

```
app/
  Http/Controllers/
    Auth/                  Breeze + OtpVerificationController
    Participant/           Everything a participant can reach
    Admin/                 Shared by Regional Admin and Super Admin
      SuperAdmin/          Super-Admin-only controllers
    PublicTrainingCatalogController.php, SettingsController.php
  Http/Middleware/
    EnsureUserHasRole.php  Registered as the `role` middleware
    SetLocale.php          Applies the user's saved locale
  Models/                  13 Eloquent models
  Services/
    AtarImportParser.php   CDTI Training Database CSV → rows
    AtarReportGenerator.php Completed training → pre-filled narrative ATAR
    CertificateService.php Certificate codes and PDF issuance
  Mail/                    OTP, account approved/rejected/pending, request confirmation
  Rules/NotSimilarToAccount.php
config/
  trainings.php            The training catalog (placeholder data, stable slugs)
  regions.php              18 regions, the agency→region map, and region centroids
  cities.php               City autocomplete source
  tna_common.php           Shared TNA checklist text
  tna_academe|nrdrrmc|lgu|volunteer.php   Per-type TNA content
resources/views/
  public/                  Landing + about
  participant/             Participant screens
  admin/                   Shared admin screens
    super-admin/           Super-Admin-only screens
    partials/              AJAX-swappable fragments
  pdf/                     DomPDF templates: certificate, ATAR report, blank templates
  layouts/                 app, guest, public, sidebar, topbar
```

**The directory a controller sits in mirrors its permission group.** A
controller under `Admin/SuperAdmin/` belongs in the `role:super_admin` route
group; one directly under `Admin/` is reachable by both admin roles. Keep them
in sync when adding routes.

## Front-end conventions

- **Live search and partial refresh.** Several admin lists re-request their own
  route with `_section=<name>` and swap in only a table fragment, so typing or
  paging doesn't reload the page. The driver is
  `resources/views/admin/partials/live-search-script.blade.php`; the controller
  side looks like:

  ```php
  if ($request->ajax() && $request->query('_section') === 'files') {
      return view('admin.partials.files-table', compact('filesRecords'));
  }
  ```

- **Lazy detail loading.** Expensive per-row detail (the Tools page's L1/L2
  breakdown, ATAR participant details) is fetched on first expand rather than
  rendered up front. Rendering everything eagerly produced ~26 MB of HTML on
  demo data.

- **On-demand pickers.** The participant roster runs into the thousands, so the
  participant picker queries `/admin/trainings/participants` scoped to the
  chosen region, paginated 10 at a time, instead of shipping the roster to the
  browser.

- **Shared colour constants.** Status and category badge colours are defined
  once in `SummaryController::STATUS_COLORS` and
  `CalendarController::CATEGORY_COLORS` so a status means the same thing on
  every screen.

## File storage

| What | Disk | Path |
| --- | --- | --- |
| Certificates | **`local` (private)** | `certificates/{slug}.pdf` |
| ATAR uploads | `public` | `atar/` |
| ATAR import CSVs | `public` | `atar-imports/` (deleted after confirm or cancel) |
| Instructor photos / certificates | `public` | `instructors/photos/`, `instructors/certificates/` |
| Participant pictures | `public` | `participant-pictures/` |
| TNA attachments | `public` | `tna/lgu/team-fact-sheets/`, `tna/volunteer/…` |

Certificates are the deliberate exception: codes are sequential and guessable,
so a public file would let anyone enumerate every participant's certificate.
`CertificateController::download()` checks the viewer, then streams from
whichever disk holds the file — it probes `local` then `public`, because
certificates issued before the move still sit on the public disk.

Replacing an uploaded file deletes the one it replaced, so unused files don't
accumulate.

## Dashboard

`Admin\DashboardController` is the largest controller (~880 lines) and returns
one of two views depending on role: `admin.dashboard-regional` or
`admin.super-admin.dashboard`.

Its shape is worth knowing before editing:

- Completed trainings are loaded **once** (`withCount('participants')`) and
  every chart filters that collection in memory rather than re-querying.
- One `chart_region` query parameter drives both the overview charts and the
  Regional Performance / Graduates Map section — a single control, not two.
- Only the graduates-by-training chart is year-scoped (defaulting to the
  current year); everything else is all-time.
- `buildInsights()` turns the chart data into the written observations shown
  above the charts.
- The Super Admin dashboard embeds `MonitoringController::mapPoints()` inline;
  the standalone `/admin/monitoring/map` page still exists for Regional Admins.
