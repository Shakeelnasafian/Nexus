## ADR-004: Accountability Domain — Objectives, SLA Tracking, and Workflow Notification Delivery
**Date**: 2026-03-15
**Status**: Accepted

**Context**: The platform needed a domain to track team-level accountability (objectives), vendor SLA compliance, and a concrete notification delivery mechanism for the Workflow engine's `notify` step handler. All three concerns are pure read/write operations with no complex state machine or event sourcing requirements.

**Decision**: The Accountability domain is implemented as plain Eloquent + Action classes following the same patterns as the Vendor domain, minus state machines. Three concerns are covered:

**1. Objectives** — tenant-scoped records with `Active → Completed | Missed` status transitions. `CompleteObjective` dispatches `ObjectiveCompleted` as a plain Laravel event so other domains can react. `MissObjective` is a simple write with no outbound event (no downstream consumers at this stage).

**2. SLA Records** — tenant-scoped records tied to a vendor and optionally a contract. `OpenSlaRecord` is called by `AccountabilityServiceProvider` when a `ContractActivated` event arrives. `CloseSlaRecordsForVendor` is called when `VendorTerminated` arrives and closes all active records for that vendor. `BreachSlaRecord` is manually invoked (e.g., from a future scheduled command or UI action) and dispatches `SlaBreached`. The SLA record deliberately stores no response/resolution time targets — these will be added in the cross-domain integration session once reporting requirements are clearer.

**3. Workflow notification delivery** — `NotifyStepHandler` was completed by adding a `workflow_notifications` table (Workflow domain) and a `SendWorkflowNotification` queued job. The handler creates a `WorkflowNotification` record with channel and message context, then dispatches the job. The job marks the notification `sent_at`. Actual channel delivery (email, Slack, etc.) is deferred to the Inertia session when user/contact models exist. This approach gives observable, testable notification history without requiring a user model today.

**Alternatives considered**: The SLA record could have been modelled as a state machine (Active → Breached → Closed). Given the simple linear lifecycle and the absence of side effects on most transitions, plain Eloquent status columns are proportionate. A state machine would add complexity without benefit at this stage. Notification delivery could have been wired to Laravel's built-in Notification system now, but it requires a `notifiable` target (a User model) which is not yet present in the codebase.

**Contracts established (events consumed)**:
- `ContractActivated` → `OpenSlaRecord`
- `VendorTerminated` → `CloseSlaRecordsForVendor`

**Events published**:
- `ObjectiveCompleted` — available for future cross-domain consumers
- `SlaBreached` — available for Workflow triggers in a future session

**Consequences**: SLA breach detection is currently manual. A future scheduled command (`MarkOverdueSlaRecords`) should query active records past their deadline and call `BreachSlaRecord`. The `workflow_notifications` table is in the Workflow domain (not Accountability) to avoid a circular dependency — Accountability listens to Workflow events, so Workflow cannot in turn depend on Accountability models.
