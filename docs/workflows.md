# Workflows

## 1. Registration and account approval

Registering does **not** create an account or grant access. It creates a
`pending_registrations` row.

```
/register  ──▶  PendingRegistration (status=pending, email_verified_at=null)
                      │
                      ▼  6-digit OTP emailed, hashed, 10-minute expiry
                /verify-otp  ──▶  email_verified_at set
                      │
                      ▼  appears in Super Admin's approval queue
      /admin/users  ──▶ approve()  ──▶  users row created, AccountApproved email
                    └─▶ reject()   ──▶  status=rejected, AccountRejected email
```

Details that matter:

- `RegisteredUserController::store()` lets a **previous abandoned or rejected**
  attempt under the same email be replaced (`updateOrCreate`), but refuses to
  overwrite one that is already verified and sitting in the approval queue —
  otherwise anyone knowing the address could knock the real applicant out.
- `UserManagementController::approve()` re-checks `status === pending && email_verified_at !== null`
  and 403s otherwise, so a hand-crafted request can't approve an unverified one.
- `PendingRegistration::sendOtpEmail()` returns whether the mail actually went
  out. The OTP is stored either way, so "Resend it" still works once SMTP is
  fixed — callers use the return value to warn the applicant instead of
  pointing them at an inbox that will never receive anything.
- **Region**: OCD Personnel pick an OCD Regional Office as their agency, and
  their region is derived from it via `config('regions.agency_map')`. Everyone
  else picks a region directly and gives a city.
- **Participant type** is one of ~22 options listed in
  `resources/views/auth/register.blade.php` (Academe, LGU, N&RDRRMC,
  Volunteers, Barangay, CSOs/NGOs, OCD Personnel, …). It determines which TNA
  form the user gets.

## 2. Training lifecycle

```
Super Admin schedules            Admin edits on Summary             Completion
──────────────────────           ──────────────────────             ──────────
/admin/trainings/create          /admin/summary/{id}/edit           status=completed
  pick catalog or custom title     status, date, venue, LGU,          │
  pick region + agency             category, agency type,             ├─▶ syncGraduateCountsFromParticipants()
  pick participants (searched      certificate remarks,               │
  on demand, paginated)            instructors, participants          └─▶ CertificateService
  weekday-only date                                                       ::generateForTrainingRequest()
        │
        ▼
  TrainingRequest
  status = approved              ← Super-Admin-scheduled trainings skip the
  source = admin_scheduled         submitted → under_review → approved pipeline
  reference_number = TR-YYYY-NNNNN
  category = ta (catalog) | apb (custom title)
```

Statuses (`TrainingRequest::$statusLabels`) — the labels differ from the
constants, and the labels are what admins see:

| Constant | Label shown |
| --- | --- |
| `submitted` | Received |
| `under_review` | Being Reviewed |
| `approved` | Approved |
| `declined` | Not Approved |
| `completed` | Completed |

`source` records where the record came from, so two similarly-named trainings
never get confused: `admin_scheduled`, `public_portal` (historical — the portal
was removed), `atar_import`.

### Marking a training Completed

`SummaryController::update()` does two extra things when the saved status is
`completed`:

1. `syncGraduateCountsFromParticipants()` recomputes the sex and age-bracket
   graduate counts straight from the roster, so the dashboards and graduates
   map always match who actually attended. These numbers are never hand-typed.
2. `CertificateService::generateForTrainingRequest()` issues one PDF per
   participant. **Safe to re-run** — participants who already have a
   certificate are skipped, never regenerated.

## 3. Certificates

`App\Services\CertificateService`.

Code format: `ABBREV-REGION-BATCH-YEAR-SEQUENCE`, e.g. `ICS-REGION III-2-2026-7`.

- **ABBREV** — a trailing all-caps parenthetical if the title has one
  (`Incident Command System (ICS)` → `ICS`); otherwise first letters of
  significant words (`Community-Based Disaster Risk Reduction and Management`
  → `CBDRRM`), dropping connector words.
- **BATCH** — how many times this exact `training_slug` has been completed
  before this one, plus one. Counts ATAR-imported historical rows too, since
  those are genuinely earlier runs.
- **SEQUENCE** — the graduate's position in the roster sorted by home region in
  the canonical Luzon → Visayas → Mindanao → Central order, then by name. When
  everyone shares a region this collapses to plain alphabetical.

Storage: `certificates/{slugified-code}.pdf` on the **`local` (private)** disk.
The stored `code` keeps its human-readable spaces; the filename doesn't.
Served only through `Participant\CertificateController::download()`.

## 4. Evaluations (L1 and L2)

Two independent sources feed the same training:

| | Who writes it | Table | Holds |
| --- | --- | --- | --- |
| Admin aggregate | Super Admin, `/admin/evaluations/{id}/edit` | `training_evaluations` (one row) | `module_ratings` (one aggregate value per module), `participant_scores` (per-participant pretest/posttest) |
| Participant | The participant, `/training-requests/{id}/evaluation` | `participant_evaluations` (one row per participant) | `module_ratings`, `instructor_ratings`, `overall_comments` |

- **L1** = module and trainer ratings. Only *participant* ratings can build a
  1–5 distribution; the admin's entry is a single aggregate value, so it can't
  contribute to a histogram.
- **L2** = pretest/posttest scores. Newer records keep one pair per participant
  in `participant_scores`; older ones only had a single session-wide pair on
  the evaluation row, which is still read as a one-person sample.

After either side saves, `Instructor::reflectRatingForTraining()` runs. It
updates the instructor's overall rating only when **exactly one** instructor
teaches that training type — otherwise there's no unambiguous person to
attribute it to. It pools admin and participant trainer ratings so the result
doesn't depend on whichever source saved last.

The Tools page's Evaluation Computation list loads each session's breakdown
lazily (`GET /admin/tools/evaluations/{id}` returns an HTML fragment) — it used
to render every session up front, which was ~26 MB of HTML on demo data.

## 5. ATAR: two different things

This is the most common source of confusion in the codebase.

| | `AtarRecord` | `AtarReport` |
| --- | --- | --- |
| What | A flat tracker row from CDTI's "Training Database" CSV export | The actual multi-page narrative ATAR document |
| Created by | CSV import at `/admin/atar-records/import` | `/admin/atar-reports/create` |
| Controller | `SuperAdmin\AtarRecordController` | `SuperAdmin\AtarReportController` |
| Contains | Per-training totals: graduates by sector/sex/PWD/youth, budget vs actual, L1/L2 completion flags, remarks | Background, Objectives, Highlights, Issues & Concerns, Ways Forward, plus Declaration of Graduates and L1/L2 annexes |
| Output | Dashboard charts | A PDF (`resources/views/pdf/atar-report.blade.php`) |

They are unrelated tables. Don't merge them.

### CSV import

`App\Services\AtarImportParser` skips two header rows, then reads real data
rows until the training-title column goes blank — that blank is the boundary
before the "DO NOT INPUT (CDTI ONLY)" pivot-summary block, which must never be
imported.

Import is a two-step preview: `store()` parses and shows a preview with
warnings, then `confirmImport()` commits (or `cancelImport()` discards and
deletes the uploaded file). Each imported row creates a linked
`AtarRecord` + `TrainingRequest` pair, with the TrainingRequest tagged
`source = atar_import` and `category = apb`.

### Narrative reports

`/admin/atar-reports/create` offers two paths:

- **Generate from a completed training** — `App\Services\AtarReportGenerator`
  pre-fills attendees, graduates/dropouts, lecturers, the L1/L2 annexes, and
  draft narrative text patterned after real OCD/CDTI ATARs, so the admin edits
  a worded draft rather than a blank form. Background and Objectives use OCD's
  standing RA 10121 mandate framing (true of every training in the catalog).
  Issues & Concerns and Ways Forward are left as instructional placeholders on
  purpose — inventing plausible-sounding "issues" that never happened would be
  actively misleading if the admin forgets to edit them.
- **Write from scratch** — `training_request_id` is null and every field is
  hand-typed.

`recompute()` re-runs the generator against the linked training to refresh the
computed annexes. Status is `draft` or `final`.

ATARs need wet-ink signatures, so there is no e-signature step — the point of
the PDF is that it arrives already populated and only needs printing and
signing.

## 6. Graduate deployments

`/admin/deployments`. A graduate is a user who has completed at least one
training; `ParticipantDeploymentController` 404s for anyone else.

A Regional Admin sees and records only their own region's graduates; a Super
Admin can record for any region. Each `participant_deployments` row keeps the
deployment name, date, role, free-text notes, and who recorded it — so a
graduate accumulates a deployment *history* rather than a single label.

## 7. Training Needs Assessment

`Participant\TrainingNeedsAssessmentController::index()` branches on
`users.participant_type`:

| Participant type | Form |
| --- | --- |
| `Academe` | Academe TNA (`config/tna_academe.php`) |
| `N&RDRRMC` | N&RDRRMC TNA (`config/tna_nrdrrmc.php`) |
| `LGU` | LGU TNA (`config/tna_lgu.php`) — includes team fact-sheet uploads |
| `Volunteers` | Volunteer TNA (`config/tna_volunteer.php`) — includes asset/equipment uploads |
| everything else | Generic scored questionnaire that recommends a catalog training |

The generic path scores answers by category and writes the winning
recommendation onto both the `training_needs_assessments` row and the user's
`recommended_training_slug`, which the participant dashboard then surfaces.
`config/tna_common.php` holds the "Current Role and Responsibilities"
checklist, which is word-for-word identical across the typed forms.

The remaining participant types don't have their own mapped TNA forms yet;
that mapping is pending CDTI.

## 8. Organizations

`/admin/organizations` — the LGUs, national government agencies, academe
bodies, and teams OCD trains (`organizations.type` is one of `lgu`, `nga`,
`academe`, `team`; kept distinct on purpose).

Membership is curated by Super Admin only. Nobody self-joins — that's what
makes it safe to treat a confirmed member as speaking for their body. The
freetext `users.organization` a participant typed at signup is only ever a
*hint* shown to the admin doing the assigning; the real link is
`users.organization_id`.

> **Naming trap.** Three different things are spelled similarly:
> `organizations.*` (the curated body), `users.organization` (freetext hint),
> and `training_requests.requesting_agency` / `agency_type` (who asked for a
> training). Check which one you mean before touching a query.
