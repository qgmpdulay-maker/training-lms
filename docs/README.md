# ICTS-SDIMD Training IMS — Documentation

Internal documentation for the OCD Training Information Management System: the
Laravel application that tracks DRRM trainings from scheduling through
certificates, evaluations, and after-training reports.

## Contents

| Document | What's in it |
| --- | --- |
| [overview.md](overview.md) | What the system is for, who uses it, what each screen does |
| [roles-and-permissions.md](roles-and-permissions.md) | The three roles, what each may read and write, how scoping is enforced |
| [workflows.md](workflows.md) | End-to-end flows: registration, training lifecycle, certificates, evaluations, ATAR, deployments, TNA |
| [data-model.md](data-model.md) | Tables, relationships, and the fields that carry meaning |
| [architecture.md](architecture.md) | Stack, directory map, services, file storage, front-end conventions |
| [setup.md](setup.md) | Local setup, seeding, tests, configuration, deployment notes |

A single Word version of all six documents, with a cover page and table of
contents, is generated at
[Training-IMS-Documentation.docx](Training-IMS-Documentation.docx) — use that
for sharing or printing; the Markdown files above stay the source of truth.

## Quick orientation

- Three roles: **Participant**, **Regional Admin**, **Super Admin** — see
  [roles-and-permissions.md](roles-and-permissions.md).
- The central record is `TrainingRequest`. Despite the name it is the
  *training session* itself: scheduled by Super Admin, moved through statuses,
  and the anchor for participants, instructors, evaluations, certificates, and
  ATAR reports.
- There is no public training-request portal. The only unauthenticated pages
  are the landing catalog (`/`) and `/about`.
- "ATAR" means two unrelated things in this codebase. `AtarRecord` is a row
  imported from CDTI's Training Database CSV; `AtarReport` is a narrative
  After Training Activity Report document. See
  [workflows.md](workflows.md#5-atar-two-different-things).
