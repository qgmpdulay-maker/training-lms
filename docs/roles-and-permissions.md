# Roles and Permissions

Three roles live on `users.role` (`App\Models\User`):

```php
User::ROLE_PARTICIPANT  // 'participant'
User::ROLE_ADMIN        // 'admin'       — Regional Admin
User::ROLE_SUPER_ADMIN  // 'super_admin'
```

Use `$user->isParticipant()`, `->isAdmin()`, `->isSuperAdmin()`. Note that
`isAdmin()` is **false** for a Super Admin — the two are distinct roles, not
nested levels, and several controllers rely on that (`isAdmin()` is the
"region-locked" check).

## How it's enforced

Route-level, via the `role` middleware (`App\Http\Middleware\EnsureUserHasRole`),
which 403s anyone whose role isn't in the list:

```php
Route::middleware(['auth', 'role:admin,super_admin'])   // both admin kinds
Route::middleware(['auth', 'role:super_admin'])         // Super Admin only
```

`routes/web.php` has exactly two admin groups, and which group a route sits in
*is* the permission model. Adding a write route to the shared group grants it
to Regional Admins — that is almost always wrong (see below).

Controller-level, region scoping is re-checked rather than trusted from the
request. Two patterns recur:

```php
// Reject a cross-region write outright
abort_if($user->isAdmin() && $trainingRequest->region !== $user->region, 403);

// Force-override a filter so it can't be widened via the query string
if ($user->isAdmin()) {
    $filters['regions'] = [$user->region];
}
```

`MonitoringController::map()` uses the second; `SummaryController::update()`
and `ToolsController::uploadFiles()` use the first.

## Regional Admin is read-only

This is a deliberate design rule, not an accident of the current routes, and
`tests/Feature/RegionalAdminIsReadOnlyTest.php` guards it.

A Regional Admin **reads** their own region's dashboard, calendar, summary,
tools, instructors, TNAs, and graduates map. They **write** exactly one thing:

- `POST /admin/deployments/{user}` and `DELETE /admin/deployments/{deployment}` —
  recording where one of their graduates has been deployed.

The reasoning (documented in `ParticipantDeploymentController`): regional staff
are the ones who actually know which of their graduates got sent to an
operation, so they maintain that for their own region. Everything else about
trainings and certificates is a Super Admin decision.

Writes that were deliberately moved *out* of the shared group and into the
Super Admin group: approving a request, marking one Completed, editing
evaluations, uploading training files, and managing instructors.

## Super Admin

Nationwide, no region lock. Additionally holds:

- Account approval and rejection (`UserManagementController::approve/reject`)
- Promote a participant to Regional Admin for a named region, and demote back
- Password resets, individually and in bulk
- Organization CRUD and membership curation
- Scheduling trainings and choosing their participants
- Marking a training Completed — the act that issues certificates
- ATAR CSV import and narrative ATAR reports

Super Admins are excluded from participant pickers (they're purely
administrative), while **Regional Admins are eligible to attend trainings** —
see `TrainingController::participants()`, which queries
`whereIn('role', [ROLE_PARTICIPANT, ROLE_ADMIN])`.

## Participant

Can only reach their own data. Two places worth knowing:

- **Certificates are private files.** `Certificate` PDFs are written to the
  `local` (non-public) disk because codes are sequential and guessable. Every
  download goes through `Participant\CertificateController::download()`, which
  checks the viewer is the owner, a Super Admin, or that region's admin.
- **"Trainings involving me"** is not just `user_id`. Use
  `TrainingRequest::involvingUser($user)` — it covers both requests the user
  submitted and ones where an admin selected them as a participant.
