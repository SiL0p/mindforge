# JobAlertBundle — Specification

## Overview

The `JobAlertBundle` is a Symfony 6.2 bundle scoped to the **Carriere module** of the MindForge platform.
It allows students to subscribe to job alert criteria and automatically notifies them when a matching `OpportuniteCarriere` is created by a company.

The bundle integrates passively into the existing Carriere module — it does **not** modify existing entities or controllers. It hooks into the job creation flow via a Doctrine event listener.

---

## Project Context

### Existing Carriere entities (do not modify)

- `App\Entity\Carriere\OpportuniteCarriere` — job listings with fields: `title`, `description`, `type`, `location`, `duration`, `deadline`, `status` (`active`/`closed`/`filled`), linked to an `Entreprise`
- `App\Entity\Carriere\Entreprise` — company profiles
- `App\Entity\Carriere\Demande` — student applications linked to a `User` and an `OpportuniteCarriere`

### Roles

- `ROLE_USER` — standard student, can subscribe to alerts and view notifications
- `ROLE_COMPANY` — company user, not concerned by this bundle

### Routing prefix

All existing Carriere routes are under `/carriere`. This bundle follows the same convention.

---

## Bundle Structure

```
src/
└── JobAlertBundle/
    ├── JobAlertBundle.php
    ├── Controller/
    │   ├── JobAlertSubscriptionController.php
    │   └── JobAlertNotificationController.php
    ├── Entity/
    │   ├── JobAlertSubscription.php
    │   └── JobAlertNotification.php
    ├── Form/
    │   └── JobAlertSubscriptionType.php
    ├── Service/
    │   └── AlertMatchingService.php
    ├── EventListener/
    │   └── OpportuniteCreatedListener.php
    ├── Repository/
    │   ├── JobAlertSubscriptionRepository.php
    │   └── JobAlertNotificationRepository.php
    └── Resources/
        └── views/
            ├── subscription/
            │   ├── index.html.twig       # manage subscriptions
            │   └── form.html.twig        # create/edit subscription form
            └── notification/
                └── index.html.twig       # notifications inbox
```

---

## Entities

### `JobAlertSubscription`

Stores a student's alert filter criteria.

| Field | Type | Details |
|---|---|---|
| `id` | int | Primary key |
| `user` | ManyToOne → User | The subscribed student |
| `keywords` | string\|null | Matched against job `title` and `description` (case-insensitive LIKE) |
| `type` | string\|null | Must match job `type` exactly if set |
| `location` | string\|null | Matched against job `location` (case-insensitive LIKE) |
| `isActive` | bool | Default `true`. Inactive subscriptions are skipped during matching |
| `createdAt` | DateTimeImmutable | Set on persist |

---

### `JobAlertNotification`

A log entry created each time a subscription is matched to a new job.

| Field | Type | Details |
|---|---|---|
| `id` | int | Primary key |
| `subscription` | ManyToOne → JobAlertSubscription | The subscription that was matched |
| `opportunite` | ManyToOne → OpportuniteCarriere | The job that triggered the alert |
| `sentAt` | DateTimeImmutable | Set on persist |
| `isRead` | bool | Default `false`. Set to `true` when student views it |

---

## Form

### `JobAlertSubscriptionType`

Fields:
- `keywords` — TextType, not required, label "Keywords"
- `type` — ChoiceType, not required, same choices as `OpportuniteCarriere::type` field (e.g. internship, full-time, part-time, freelance)
- `location` — TextType, not required, label "Location"
- `isActive` — CheckboxType, not required, label "Active", default checked

---

## Service

### `AlertMatchingService`

**Method:** `matchAndNotify(OpportuniteCarriere $opportunite): void`

Logic:
1. Fetch all `JobAlertSubscription` where `isActive = true`
2. For each subscription, check:
   - If `keywords` is set: at least one of `title` or `description` of the job contains the keyword (case-insensitive)
   - If `type` is set: job `type` equals subscription `type`
   - If `location` is set: job `location` contains subscription `location` (case-insensitive)
   - All set criteria must match (AND logic)
3. For each matching subscription, create a `JobAlertNotification` and persist it
4. Flush once after all notifications are created

---

## Event Listener

### `OpportuniteCreatedListener`

- Listens to Doctrine's `postPersist` event
- Checks if the persisted entity is an instance of `OpportuniteCarriere`
- Calls `AlertMatchingService::matchAndNotify($opportunite)`

Register as a service with the `doctrine.event_listener` tag and event `postPersist`.

---

## Controllers & Routes

### `JobAlertSubscriptionController`

> Access: `ROLE_USER` only (students). Add `#[IsGranted('ROLE_USER')]` at class level.

| Method | Route | Name | Description |
|---|---|---|---|
| GET | `/carriere/alerts` | `job_alert_subscription_index` | List all subscriptions for the logged-in user |
| GET/POST | `/carriere/alerts/new` | `job_alert_subscription_new` | Create a new subscription |
| GET/POST | `/carriere/alerts/{id}/edit` | `job_alert_subscription_edit` | Edit a subscription (owner only) |
| POST | `/carriere/alerts/{id}/toggle` | `job_alert_subscription_toggle` | Toggle `isActive` on/off |
| POST | `/carriere/alerts/{id}/delete` | `job_alert_subscription_delete` | Delete a subscription (owner only) |

---

### `JobAlertNotificationController`

> Access: `ROLE_USER` only.

| Method | Route | Name | Description |
|---|---|---|---|
| GET | `/carriere/alerts/notifications` | `job_alert_notification_index` | Inbox — all notifications for the logged-in user, ordered by `sentAt` DESC |
| POST | `/carriere/alerts/notifications/{id}/read` | `job_alert_notification_read` | Mark a single notification as read |
| POST | `/carriere/alerts/notifications/read-all` | `job_alert_notification_read_all` | Mark all unread notifications as read |

---

## Templates

### `subscription/index.html.twig`
- Table listing the user's subscriptions (keywords, type, location, active status, created date)
- Toggle active button per row
- Edit and Delete buttons per row
- "Create new alert" button linking to the new form

### `subscription/form.html.twig`
- Renders `JobAlertSubscriptionType`
- Used for both create and edit

### `notification/index.html.twig`
- List of notifications ordered newest first
- Each row shows: job title (linked to `/carriere/opportunite/{id}`), company name, sentAt date, read/unread indicator
- "Mark all as read" button at the top
- Unread notifications visually highlighted

---

## Security Rules

- A student can only view, edit, toggle, or delete **their own** subscriptions. Enforce with a manual `$subscription->getUser() !== $this->getUser()` check and throw a 403 if violated.
- A student can only mark **their own** notifications as read. Same check on the `subscription->getUser()`.
- `ROLE_COMPANY` users should not access any of these routes. The `ROLE_USER` grant is sufficient since company users hold `ROLE_COMPANY`, not `ROLE_USER`.

---

## Registration

### `JobAlertBundle.php`

```php
namespace App\JobAlertBundle;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class JobAlertBundle extends AbstractBundle {}
```

Register in `config/bundles.php`:

```php
App\JobAlertBundle\JobAlertBundle::class => ['all' => true],
```

---

## Migration

After creating the entities, generate the migration with:

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

Expected new tables: `job_alert_subscription`, `job_alert_notification`

---

## Constraints & Validation

- `keywords`, `type`, and `location` are all optional — a subscription with no criteria set matches **every** new job (acts as a catch-all alert)
- No duplicate notification guard is strictly required but recommended: before creating a `JobAlertNotification`, optionally check that no existing notification already links the same `subscription` + `opportunite` pair

---

## Out of Scope

- Email/SMS delivery — notifications are in-app only
- Real-time push (WebSocket) — a simple database-backed inbox is sufficient
- Modifying any existing Carriere entity, controller, or route