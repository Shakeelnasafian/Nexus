## ADR-003: Database-Driven Workflow Automation Engine
**Date**: 2026-03-08
**Status**: Accepted

**Context**: Nexus needs a cross-domain automation layer that reacts to events from Billing and Vendor and executes configurable sequences of actions. The requirements are: workflows must be tenant-configurable (stored as data, not code), execution must be observable (per-step run history), failures must not be silent, and the engine must consume domain events from other bounded contexts without introducing direct cross-domain calls.

**Decision**: We implemented a data-driven workflow engine with four tables: `workflows` (trigger + active flag), `workflow_steps` (ordered action sequence), `workflow_runs` (per-trigger execution record), and `workflow_step_runs` (per-step execution record with output and status). The execution pipeline is:

1. A domain event fires (`VendorActivated`, `PaymentFailed`, etc.)
2. `WorkflowServiceProvider` maps the event class to a `WorkflowTrigger` string and calls `TriggerWorkflow::execute()`
3. `TriggerWorkflow` finds all active workflows for that tenant and trigger, creates a `WorkflowRun` record per workflow, and dispatches a `RunWorkflowJob` for each
4. `RunWorkflowJob` calls `ExecuteWorkflowRun`, which iterates steps in `sort_order`, resolves each step's handler from `StepHandlerRegistry`, calls `handle()`, and records the result in `workflow_step_runs`
5. On any step failure, the run is marked `Failed`, the reason is recorded, and execution stops

`StepHandlerRegistry` maps `action_type` strings (stored in `workflow_steps.action_type`) to handler classes. Two handlers ship: `LogStepHandler` (writes to the application log) and `NotifyStepHandler` (placeholder for Accountability-side notification delivery). New step types are registered in `WorkflowServiceProvider::register()` without touching other code.

`TriggerWorkflow` and `RunWorkflowJob` both bypass the global tenant scope via `withoutGlobalScopes()` because they run outside of a tenant-scoped HTTP request context. Tenant identity is carried in the domain event payload and stored explicitly on `workflow_runs.tenant_id`.

**Alternatives considered**: We could have hard-coded workflow reactions directly in each domain's service provider (e.g., listening to `VendorActivated` and immediately sending a notification). This would be simpler but would not satisfy the "stored as database data" requirement and would make automation non-configurable at runtime. We could also have used a dedicated workflow package (Temporal, Laravel Workflow) but that adds infrastructure dependencies that are disproportionate to the current scope.

**Consequences**: Workflows are fully tenant-configurable and extensible without code changes. The four-table schema gives complete execution history. The `StepHandlerRegistry` singleton makes it easy to add step types in future sessions. The main cost is that the Accountability domain must hook into `NotifyStepHandler` (or register its own action type) to deliver notifications — this will be done in the cross-domain integration session.

**Contracts established (events consumed)**:
- `VendorActivated` → trigger `vendor.activated`
- `VendorSuspended` → trigger `vendor.suspended`
- `VendorTerminated` → trigger `vendor.terminated`
- `ContractActivated` → trigger `contract.activated`
- `SubscriptionCancelled` → trigger `subscription.cancelled`
- `PaymentFailed` → trigger `subscription.payment_failed`

**Trade-offs**: The queued job model means workflow execution is eventually consistent with the triggering event. This is acceptable for automation use cases and avoids blocking the original event handler. The synchronous step loop inside the job means long-running workflows can tie up a worker, but this is a known trade-off that can be addressed with step-level queuing in a future session if needed.
