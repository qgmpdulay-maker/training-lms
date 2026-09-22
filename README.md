# ICTS-SDIMD Training IMS

Internal training information management system for the **Office of Civil
Defense**. It tracks DRRM trainings end to end — scheduling, participant
rosters, evaluations, certificates, after-training reports, and where graduates
have since been deployed — across all 18 OCD regions.

Built on Laravel 13 (PHP 8.3), Blade + Alpine, Tailwind, MySQL.

## Documentation

Full documentation lives in [`docs/`](docs/README.md):

| Document | What's in it |
| --- | --- |
| [Overview](docs/overview.md) | What the system does, who uses it, what each screen is |
| [Roles & Permissions](docs/roles-and-permissions.md) | The three roles and how scoping is enforced |
| [Workflows](docs/workflows.md) | Registration, training lifecycle, certificates, evaluations, ATAR, deployments, TNA |
| [Data Model](docs/data-model.md) | Tables, relationships, meaningful fields |
| [Architecture](docs/architecture.md) | Stack, directory map, services, storage, front-end conventions |
| [Setup](docs/setup.md) | Local setup, seeding, tests, configuration, deployment |

A combined Word version for sharing or printing:
[docs/Training-IMS-Documentation.docx](docs/Training-IMS-Documentation.docx).

## Quick start

```bash
composer setup
```

Point `.env` at a MySQL database, then:

```bash
php artisan migrate:fresh --seed
```

Run the full development stack (server, queue, logs, Vite):

```bash
composer dev
```

Seeded accounts — password comes from `UserFactory` (`password`):

| Email | Role |
| --- | --- |
| `superadmin@ocd.gov.ph` | Super Admin |
| `admin@ocd.gov.ph` | Regional Admin (Region III) |
| `test@example.com` | Participant |

For realistic nationwide data:

```bash
php artisan db:seed --class=NationwideDemoSeeder
```

## Tests

```bash
composer test
```

## Three things to know before editing

1. **Regional Admin is read-only.** Their single write is recording graduate
   deployments. A write route placed in the shared `role:admin,super_admin`
   group grants it to them — almost always a mistake. See
   [roles-and-permissions.md](docs/roles-and-permissions.md).
2. **`TrainingRequest` is the training session**, not a request form. It is the
   anchor for participants, instructors, evaluations, certificates, and ATAR
   reports.
3. **"ATAR" means two unrelated things.** `AtarRecord` is an imported CSV
   tracker row; `AtarReport` is the narrative document. Separate tables,
   separate controllers, don't merge them.

---

Framework reference: [Laravel documentation](https://laravel.com/docs).
