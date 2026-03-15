# Nexus Platform — Architecture Reference

## Overview

Nexus is a B2B enterprise SaaS platform for vendor relationship management. It is built on **Laravel 12**, **Inertia.js**, and **Vue 3**. The codebase is organised into bounded domain contexts under `app/Domain/`, each with its own models, actions, events, and service provider.

---

## Tenancy

Every model that belongs to a tenant uses the `BelongsToTenant` trait (`app/Tenancy/Concerns/BelongsToTenant.php`). This trait:

1. Listens to the Eloquent `creating` event and auto-sets `tenant_id` from the `CurrentTenant` singleton.
2. Boots a `TenantScope` global scope that restricts all queries to the current tenant.

**Critical rule:** Never call `Event::fake()` before creating a tenant-aware model in tests — it intercepts Eloquent model events and `tenant_id` will not be set. Use `Event::fake([SpecificEvent::class])` after model creation.

In queued jobs and event listeners that run outside HTTP context, bypass the global scope with `withoutGlobalScopes()` and pass `tenant_id` explicitly.

---

## Domain Map

### Billing — `app/Domain/Billing/`

Event-sourced subscription lifecycle using `spatie/laravel-event-sourcing`.

| Component | Purpose |
| --- | --- |
| `SubscriptionAggregate` | Records storable events: `SubscriptionStarted`, `SubscriptionRenewed`, `SubscriptionCancelled`, `PaymentFailed` |
| `SubscriptionProjector` | Maintains the `subscriptions` read model from stored events |
| `SubscriptionPaymentFailureReactor` | Reacts to `PaymentFailed` → dispatches `SendDunningNotification` job |
| `billing_plans` table | Plan catalogue; referenced by subscriptions |

Domain events published (as Laravel events): `SubscriptionCancelled`, `PaymentFailed` (storable events also act as Laravel events via the Spatie event bus).

### Vendor — `app/Domain/Vendor/`

State-machine-driven vendor and contract lifecycle using `spatie/laravel-model-states`.

| State machine | Transitions |
| --- | --- |
| `VendorState` | `Pending → Active ↔ Suspended → Terminated`; `Active → Terminated` |
| `ContractState` | `Draft → Active → Expired | Terminated` |

Invalid transitions throw `Spatie\ModelStates\Exceptions\CouldNotPerformTransition`.

Domain events published: `VendorActivated`, `VendorSuspended`, `VendorTerminated`, `ContractActivated`.

### Workflow — `app/Domain/Workflow/`

Data-driven automation engine. Workflows are stored as database records (not code), making them tenant-configurable at runtime.

**Pipeline:** domain event → `WorkflowServiceProvider` listener → `TriggerWorkflow` action → `WorkflowRun` record created → `RunWorkflowJob` dispatched → `ExecuteWorkflowRun` action → `StepHandlerRegistry` → `StepHandler::handle()`.

| Table | Purpose |
| --- | --- |
| `workflows` | Trigger + active flag per tenant |
| `workflow_steps` | Ordered action steps with `action_type` and `payload` |
| `workflow_runs` | Per-trigger execution record with status and trigger payload |
| `workflow_step_runs` | Per-step execution record with output and status |
| `workflow_notifications` | Notification delivery records created by `NotifyStepHandler` |

**Registered step handlers:**

| `action_type` | Handler | Behaviour |
| --- | --- | --- |
| `log` | `LogStepHandler` | Writes to the application log |
| `notify` | `NotifyStepHandler` | Creates a `WorkflowNotification` record and dispatches `SendWorkflowNotification` job |

New step handlers are registered in `WorkflowServiceProvider::register()` via `StepHandlerRegistry::register()`.

**Triggers consumed:**

| Trigger string | Source event |
| --- | --- |
| `vendor.activated` | `VendorActivated` |
| `vendor.suspended` | `VendorSuspended` |
| `vendor.terminated` | `VendorTerminated` |
| `contract.activated` | `ContractActivated` |
| `subscription.cancelled` | `SubscriptionCancelled` |
| `subscription.payment_failed` | `PaymentFailed` |
| `sla.breached` | `SlaBreached` |
| `objective.completed` | `ObjectiveCompleted` |

### Accountability — `app/Domain/Accountability/`

Plain Eloquent + actions. No state machines or event sourcing.

| Model | Lifecycle |
| --- | --- |
| `Objective` | `Active → Completed | Missed` |
| `SlaRecord` | `Active → Breached | Closed` |

**Auto-integrations:**

- `ContractActivated` → `OpenSlaRecord` (creates an SLA record for the contract)
- `VendorTerminated` → `CloseSlaRecordsForVendor` (closes all active SLA records for the vendor)
- `BreachSlaRecord` → dispatches `SlaBreached` → triggers any matching `sla.breached` workflows
- `CompleteObjective` → dispatches `ObjectiveCompleted` → triggers any matching `objective.completed` workflows

**Scheduled command:** `sla:mark-overdue --days=30` — breaches all active SLA records open longer than the configured number of days. Runs daily at 01:00 (configured in `routes/console.php`).

---

## HTTP Layer

- Routes: `routes/web.php` — Inertia-only, no `api.php`
- Controllers: `app/Http/Controllers/{Domain}/` — thin, call action classes, return Inertia responses
- Form Requests: `app/Http/Requests/` — validation only
- Vue pages: `resources/js/Pages/{Domain}/` — one component per page
- Layout: `resources/js/Layouts/AppLayout.vue`

---

## Inter-Domain Event Contracts

All cross-domain communication uses plain Laravel events (readonly final classes). No domain may call another domain's actions directly.

| Publisher | Event | Consumers |
| --- | --- | --- |
| Vendor | `VendorActivated` | Workflow |
| Vendor | `VendorSuspended` | Workflow |
| Vendor | `VendorTerminated` | Workflow, Accountability |
| Vendor | `ContractActivated` | Workflow, Accountability |
| Billing | `SubscriptionCancelled` | Workflow |
| Billing | `PaymentFailed` | Workflow |
| Accountability | `SlaBreached` | Workflow |
| Accountability | `ObjectiveCompleted` | Workflow |

---

## Key Packages

| Package | Version | Purpose |
| --- | --- | --- |
| `spatie/laravel-event-sourcing` | ^7.15 | Billing aggregate, projectors, reactors |
| `spatie/laravel-model-states` | ^2.12.1 | Vendor/Contract state machines |
| `inertiajs/inertia-laravel` | ^2.0 | Server-side Inertia adapter |
| `@inertiajs/vue3` | latest | Client-side Inertia + Vue 3 |

---

## Testing

- Framework: **Pest** with `RefreshDatabase` per domain folder
- Domain tests: `tests/Feature/{Billing,Vendor,Workflow,Accountability}/`
- Integration tests: `tests/Feature/Integration/`
- Current count: **62 tests, 152 assertions** — all passing
- Factories: none — test data created via action classes directly
