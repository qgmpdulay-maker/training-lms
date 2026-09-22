# Data Model

## Relationship map

```
User ──┬── hasMany     TrainingRequest        (requests they filed)
       ├── belongsToMany TrainingRequest      (trainings they attend — pivot: training_request_user)
       ├── hasMany     Certificate
       ├── hasMany     ParticipantEvaluation
       ├── hasMany     ParticipantDeployment
       ├── hasMany     TrainingNeedsAssessment
       └── belongsTo   Organization           (users.organization_id — curated membership)

TrainingRequest ──┬── belongsTo     User                    (submitter)
                  ├── belongsToMany User                    (participants)
                  ├── belongsToMany Instructor              (pivot: instructor_training_request)
                  ├── hasOne        TrainingEvaluation      (admin aggregate)
                  ├── hasMany       ParticipantEvaluation   (one per participant)
                  ├── hasMany       Certificate
                  └── hasMany       AtarReport / AtarRecord (optional back-links)

Organization ── hasMany User
PendingRegistration ── belongsTo User (approved_user_id, null until approved)
CalendarEvent ── belongsTo User (created_by)
```

## Tables

### `users`

Identity plus participant profile. Key fields:

| Field | Notes |
| --- | --- |
| `role` | `participant` \| `admin` \| `super_admin` |
| `region` | One of `config('regions.list')`. Scopes admins; also used for certificate ordering |
| `participant_type` | Drives which TNA form they get |
| `organization` | Freetext, typed at signup — a hint only |
| `organization_id` | The real curated membership (FK to `organizations`) |
| `agency`, `city`, `age`, `sex`, `position`, `picture` | Profile |
| `recommended_training_slug` | Written by the generic TNA |
| `theme`, `locale` | Per-user settings |

### `training_requests`

The training session itself — the central record.

| Group | Fields |
| --- | --- |
| Identity | `reference_number` (`TR-YYYY-NNNNN`), `training_slug`, `training_title`, `category` (`ta` \| `apb`) |
| Requester | `requesting_agency`, `agency_type` (`lgu` \| `nga`), `lgu`, `contact_person`, `contact_number`, `contact_email` |
| Scheduling | `preferred_date` (weekdays only), `venue`, `region`, `number_of_participants`, `purpose` |
| Lifecycle | `status`, `source` (`admin_scheduled` \| `public_portal` \| `atar_import`) |
| Certificates | `certificate_remarks` (`completion` \| `participation`) |
| Files | `tna_file_path`, `signed_letter_path`, `atar_file_path` |
| Monitoring | `latitude`, `longitude`, `teams_organized` |
| Graduate counts | `graduates_male`, `graduates_female`, `graduates_age_18_30`, `_31_45`, `_46_59`, `_60_up` |

Useful members:

```php
$request->graduates            // male + female
$request->non_completers       // number_of_participants - graduates, floored at 0
$request->map_coordinates      // lat/lng, else the region's centre, else PH centre
$request->effectiveParticipants()   // roster if any, else the submitter
TrainingRequest::completed()
TrainingRequest::involvingUser($user)
TrainingRequest::filteredBy(['regions' => [...], 'category' => ..., 'from' => ..., 'until' => ...])
```

Graduate counts are **derived, never hand-typed** —
`syncGraduateCountsFromParticipants()` recomputes them from the roster on every
save where status is `completed`.

### `certificates`

`training_request_id`, `user_id`, `code`, `type` (`completion` / `participation`),
`file_path`, `issued_on`. Files live on the private `local` disk. See
[workflows.md](workflows.md#3-certificates) for the code format.

### `training_evaluations` / `participant_evaluations`

`training_evaluations` — one row per training, written by the admin:
`module_ratings` (JSON), `participant_scores` (JSON, per-participant
pretest/posttest), and legacy scalar `pretest_score` / `posttest_score`.

`participant_evaluations` — one row per participant: `module_ratings`,
`instructor_ratings`, `overall_comments`.

### `instructors`

`name`, `email`, `phone`, `sex`, `position`, `training_type`, `specialization`,
`certification`, `certificate_code`, `certificate_file_path`, `photo_path`,
`deployment`, `deployment_date`, `deployment_role`, `agency_organization`,
`lgu`, `region`, `rating`, `complaints`.

`rating` is auto-derived from pooled L1 data when exactly one instructor
teaches a training type — see `Instructor::reflectRatingForTraining()`.

### `organizations`

`name`, `type` (`lgu` \| `nga` \| `academe` \| `team`), `region`, `city`,
`contact_person`, `contact_email`, `contact_number`, `notes`. Members are
`users` rows pointing back via `organization_id`.

### `participant_deployments`

`user_id`, `deployment`, `deployment_date`, `deployment_role`, `notes`,
`recorded_by`. A history, not a status flag.

### `pending_registrations`

`name`, `age`, `sex`, `participant_type`, `agency`, `city`, `region`, `email`,
`password` (hashed), `otp_code` (hashed), `otp_expires_at`,
`email_verified_at`, `status`, `approved_user_id`.

### `atar_records`

One imported CSV row: `region`, `atar_tracker_code`, `training_type_code`,
`training_title`, `mode_of_implementation`, `month`, `date_conducted`, `venue`,
`issues_and_concerns`, `ways_forward`, `overall_rating`, `signed`, `dropouts`,
`participation`, `graduates`, graduates split by sector
(`_rdrrmc`, `_lgu`, `_ldrrmo`, `_academe`, `_cso`, `_ngo`, `_volunteer`,
`_private_sector`, `_others`) and by demographic (`_male`, `_female`, `_pwd`,
`_youth`), `source_of_funds`, `budget`, `actual`, `variance`, `l1_completed`,
`l2_completed`, `date_atar_submitted`, `verified_by`, `remarks`,
`source_file_path`, `imported_by`, `training_request_id`.

Known deliberate gaps in the import: agency/LGU and age brackets are not
mapped.

### `atar_reports`

Narrative document. Text sections (`background`, `objectives`,
`attendees_narrative`, `highlights`, `issues_and_concerns`, `ways_forward`,
`graduates_summary`) plus JSON repeatable-row sections (`photos`,
`graduates_list`, `dropouts_list`, `lecturers_list`, `signatories`,
`l1_modules`, `l2_stats`). `status` is `draft` or `final`.
`training_request_id` is null when written from scratch.

### `training_needs_assessments`

`user_id`, `type` (`generic` \| `academe` \| `nrdrrmc` \| `lgu` \| `volunteer`),
`answers` (JSON), `category_scores` (JSON), `top_category`, `max_hours`,
`recommended_training_slug`, `recommended_training_title`,
`recommended_training_category`, plus a JSON `profile` for the typed forms.

### `calendar_events`

`title`, `type` (`holiday` \| `suspension` \| `other`), `date`, `end_date`,
`region` (null = nationwide), `description`, `created_by`.

**Unused by the application.** The table, model, and demo seeding exist, but
nothing reads `CalendarEvent` — `/admin/calendar` renders trainings only.
Holidays and suspensions were dropped from scope; treat this as dormant
scaffolding, not a feature.
