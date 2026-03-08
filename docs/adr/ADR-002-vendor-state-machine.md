## ADR-002: State-Machine-Driven Vendor and Contract Lifecycle
**Date**: 2026-03-08
**Status**: Accepted

**Context**: The Vendor domain manages external supplier relationships and the contracts attached to them. Both vendors and contracts pass through well-defined lifecycle stages, and invalid transitions (e.g. activating a terminated vendor, expiring a draft contract) must be rejected at the domain level, not in controller logic. The wider architecture also requires inter-domain communication via events only, so any state change that downstream domains (Workflow, Accountability) must react to must be published as an explicit domain event.

**Decision**: We modelled vendor and contract lifecycles as state machines using Spatie Model States. Each state is a concrete subclass of an abstract base (`VendorState`, `ContractState`) that declares the full transition graph in a central `config()` method. Allowed transitions are:

- Vendor: `Pending → Active`, `Active → Suspended`, `Active → Terminated`, `Suspended → Active`, `Suspended → Terminated`
- Contract: `Draft → Active`, `Active → Expired`, `Active → Terminated`

State is stored as a named string column (`state`) on the model and cast back to the concrete state class on read. Action classes (`ActivateVendor`, `SuspendVendor`, `TerminateVendor`, `ActivateContract`, `ExpireContract`, `TerminateContract`) drive transitions, stamp audit timestamps, and dispatch domain events (`VendorActivated`, `VendorSuspended`, `VendorTerminated`, `ContractActivated`) for downstream consumption. Attempting any transition not listed above throws `CouldNotPerformTransition`, enforcing the lifecycle invariants without ad-hoc guard clauses.

Both `Vendor` and `Contract` are tenant-scoped via `BelongsToTenant`. The `VendorServiceProvider` binds all Action classes; cross-domain event listeners will be wired in the integration session.

**Alternatives considered**: We could have used a simple `status` enum column with PHP guard clauses in service methods instead of a state machine. We could also have used the Billing domain's event-sourcing approach for Vendor, recording every state change as a stored event. Both were rejected: bare enum columns push invariant enforcement into callers and scatter transition logic, while full event sourcing would add stored-event infrastructure overhead for a domain whose history is not required to be replayable.

**Consequences**: Invalid transitions are caught immediately by Spatie Model States, keeping controllers and action classes free of manual guards. The state column is human-readable in the database. Domain events give Workflow and Accountability clean integration points without direct cross-domain calls. The main cost is the added package dependency and the discipline of extending the correct abstract state class for each hierarchy.

**Trade-offs**: A state machine is heavier than a plain enum but lighter than event sourcing. It is the right fit for Vendor because the lifecycle rules are strict and the domain requires cross-domain event publication, but full auditability of the event stream is not a product requirement at this stage. If vendor history replay becomes necessary in a future session, the domain can be migrated to event sourcing without affecting consuming domains, since the public interface is already expressed as named domain events.
