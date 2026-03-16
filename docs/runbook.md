# Nexus Operational Runbook

## Local Development

```bash
# Install dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate

# Start dev servers (in two terminals)
php artisan serve
npm run dev
```

---

## Queue Worker

Nexus uses Laravel queues for:

- `RunWorkflowJob` — executes a workflow run
- `SendWorkflowNotification` — marks notification records as sent (delivery TBD)
- `SendDunningNotification` — billing dunning (Billing domain)

```bash
# Start the worker
php artisan queue:work --queue=default

# For production, use a process manager (Supervisor recommended)
```

**Important:** If the queue worker is not running, workflow runs will pile up as `pending` and never execute. Monitor the `workflow_runs` table for stale `pending` records.

---

## Scheduled Commands

Register the scheduler in cron (once per server):

```
* * * * * cd /path/to/nexus && php artisan schedule:run >> /dev/null 2>&1
```

Registered schedules:

| Command | Schedule | Purpose |
| --- | --- | --- |
| `sla:mark-overdue --days=30` | Daily 01:00 | Breach active SLA records open > 30 days |

To run the command manually:

```bash
php artisan sla:mark-overdue          # default: 30 days
php artisan sla:mark-overdue --days=7  # custom threshold
```

---

## Tenancy

Tenants are records in the `tenants` table. `CurrentTenant` is a singleton set at the start of each request (not yet wired to an HTTP middleware — add this in the auth session).

To create a tenant in production:

```bash
php artisan tinker
>>> App\Tenancy\Models\Tenant::create(['name' => 'Acme Corp', 'slug' => 'acme']);
```

---

## Event Sourcing (Billing)

The Billing domain uses `spatie/laravel-event-sourcing`. Stored events live in the `stored_events` table.

**Replay all projectors** (e.g., after adding a new projector):

```bash
php artisan event-sourcing:replay App\\Domain\\Billing\\Projectors\\SubscriptionProjector
```

**Replay all:**

```bash
php artisan event-sourcing:replay
```

Note: Replaying fires projector handlers but not reactors (by design — reactors have side effects like sending emails).

---

## Database

```bash
# Run all pending migrations
php artisan migrate

# Fresh install with seeds (development only)
php artisan migrate:fresh
```

Key tables and owners:

| Table | Domain | Notes |
| --- | --- | --- |
| `tenants` | Tenancy | All other tables reference this |
| `billing_plans` | Billing | Plan catalogue |
| `subscriptions` | Billing | Projector read model |
| `stored_events` | Billing | Event sourcing log |
| `vendors` | Vendor | |
| `contracts` | Vendor | |
| `workflows` | Workflow | Tenant-configurable automation rules |
| `workflow_steps` | Workflow | |
| `workflow_runs` | Workflow | Execution history |
| `workflow_step_runs` | Workflow | Per-step execution history |
| `workflow_notifications` | Workflow | Notification delivery records |
| `objectives` | Accountability | |
| `sla_records` | Accountability | Auto-created on contract activation |

---

## Monitoring

### Stale Workflow Runs

Active workflow runs that have not completed indicate a queue problem:

```sql
SELECT * FROM workflow_runs WHERE status IN ('pending','running') AND created_at < NOW() - INTERVAL 1 HOUR;
```

### SLA Breach Backlog

```sql
SELECT COUNT(*) FROM sla_records WHERE status = 'active' AND started_at < NOW() - INTERVAL 30 DAY;
```

### Failed Workflow Runs

```sql
SELECT id, trigger_event, failure_reason, failed_at FROM workflow_runs WHERE status = 'failed' ORDER BY failed_at DESC LIMIT 20;
```

---

## Adding a New Workflow Step Type

1. Create a handler class in `app/Domain/Workflow/StepHandlers/` implementing `StepHandler`.
2. Register it in `WorkflowServiceProvider::register()`:
   ```php
   $registry->register('my_action', MyActionStepHandler::class);
   ```
3. Tenants can now create workflow steps with `action_type = 'my_action'`.

---

## Adding a New Workflow Trigger

1. Add a case to `WorkflowTrigger` enum (`app/Domain/Workflow/Enums/WorkflowTrigger.php`).
2. Add a listener in `WorkflowServiceProvider::boot()` mapping the domain event to the new trigger value.
3. The trigger is immediately available for tenants to use when configuring workflows.
