# Overview

## What this system is

The **ICTS-SDIMD Training IMS** is the Office of Civil Defense's internal
system for managing DRRM (Disaster Risk Reduction and Management) trainings
nationwide. It covers the full life of a training:

1. A training is scheduled for a region, with a roster of participants.
2. It runs; admins record evaluations and upload supporting files.
3. It is marked Completed — which issues every participant's certificate PDF.
4. Its results feed the dashboards, the graduates map, and the narrative
   After Training Activity Report (ATAR).

Alongside that, it keeps the supporting registers: participants and their
accounts, instructors, the LGUs / agencies / academe bodies OCD trains,
training needs assessments, and where graduates have since been deployed.

## Who uses it

| User | What they do here |
| --- | --- |
| **Participant** | Sees trainings they attended, takes the Training Needs Assessment, submits their own evaluation of a training, downloads certificates and their ID card |
| **Regional Admin** | Reads everything for their own region — dashboard, calendar, summary, tools, instructors, graduates map. Their only write is recording graduate deployments |
| **Super Admin** | Everything, nationwide: schedules trainings, approves accounts, marks trainings Completed, manages instructors and organizations, imports ATAR data, writes ATAR reports |

Accounts are not self-service. Registering creates a *pending* registration;
it becomes a real account only after the applicant verifies their email by OTP
**and** a Super Admin approves it. See
[workflows.md](workflows.md#1-registration-and-account-approval).

## The screens

### Public (no login)

| Route | Page |
| --- | --- |
| `/` | Landing page with the training catalog (`config/trainings.php`) |
| `/about` | Static about page |
| `/login`, `/register`, `/verify-otp` | Auth pages |

### Participant

| Route | Page |
| --- | --- |
| `/dashboard` | Trainings taken, plus the training recommended by their TNA |
| `/trainings` | The catalog |
| `/training-needs-assessment` | TNA — form shape depends on participant type |
| `/training-requests` | "My Trainings": every training involving this user |
| `/training-requests/{id}/evaluation` | Their own evaluation of a training |
| `/certificates` | Their issued certificates (PDF served through the app) |
| `/profile`, `/profile/id-card`, `/settings` | Profile, printable ID card, theme/locale |

### Admin (Regional Admin and Super Admin)

| Route | Page |
| --- | --- |
| `/admin/dashboard` | Charts and insights. Regional Admins get a region-scoped variant |
| `/admin/summary` | The training list — filter by status/region, open one to edit (edit is Super Admin only) |
| `/admin/calendar` | Trainings by month |
| `/admin/tools` | Per-training file uploads and the L1/L2 Evaluation Computation list; blank TNA / ATAR / certificate template downloads |
| `/admin/instructors` | Instructor roster (detail page and all writes are Super Admin only) |
| `/admin/deployments` | Record where graduates have been deployed — **the Regional Admin's one write** |
| `/admin/training-needs-assessment` | Submitted TNAs |
| `/admin/monitoring/map` | Graduates map, force-scoped to the admin's own region |

### Super Admin only

| Route | Page |
| --- | --- |
| `/admin/trainings/create` | Schedule a training and pick its participants |
| `/admin/users` | Approve/reject registrations, promote/demote admins, assign organizations, reset passwords |
| `/admin/organizations` | LGUs / NGAs / academe bodies / teams and their curated membership |
| `/admin/atar-records` | Import and browse CDTI Training Database CSV rows |
| `/admin/atar-reports` | Create, edit, and export narrative ATAR report PDFs |

## Vocabulary

| Term | Meaning here |
| --- | --- |
| **TrainingRequest** | A training session. The name is historical — these are now scheduled by Super Admin, not requested by the public |
| **TA / APB** | Training category. Catalog trainings are tagged `ta` (Technical Assistance); custom-titled and CSV-imported ones are tagged `apb` |
| **L1 / L2** | Kirkpatrick evaluation levels. L1 = module and trainer ratings (reaction); L2 = pretest/posttest scores (learning) |
| **ATAR** | After Training Activity Report. Two distinct things in this codebase — see [workflows.md](workflows.md#5-atar-two-different-things) |
| **TNA** | Training Needs Assessment |
| **Region** | One of the 18 entries in `config/regions.php` (17 regional offices plus OCD Central). Scopes admins and most queries |
