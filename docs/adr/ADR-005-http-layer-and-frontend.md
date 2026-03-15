## ADR-005: HTTP Layer — Inertia.js + Vue 3 with Thin Controllers
**Date**: 2026-03-15
**Status**: Accepted

**Context**: The platform needed a UI layer. Options considered were a traditional Blade/AJAX approach, a full SPA with a separate API, or Inertia.js. The architecture contract specified Inertia.js + Vue 3 with no `api.php`.

**Decision**: All HTTP responses use Inertia (`Inertia::render()`). Controllers are thin — they resolve the current tenant's data and pass it as Inertia props. All application logic stays in domain Action classes. Form Requests handle validation at the HTTP boundary. No `api.php` routes exist.

Controllers are namespaced by domain (e.g., `App\Http\Controllers\Vendor\VendorController`) and live in `app/Http/Controllers/{Domain}/`. Action-style mutations (activate, suspend, terminate) use dedicated `{Model}ActionController` classes with POST-only routes rather than overloading resource controllers.

Vue pages use a default `AppLayout` applied automatically via the Inertia resolve callback in `app.js`, so individual page components don't need to declare their layout.

**Consequences**: The frontend is a conventional SSR-like experience with Inertia's SPA navigation. Adding an API later would require `api.php` routes and authentication (Sanctum). The thin-controller pattern keeps HTTP concerns separated from domain logic and makes controllers trivially testable with `assertInertia()`.

## ADR-006: Cross-Domain Integration — Event-Driven with No Direct Domain Calls
**Date**: 2026-03-15
**Status**: Accepted

**Context**: Multiple domains need to react to each other's state changes (e.g., Accountability must create SLA records when a contract activates; Workflow must trigger automation when any domain event fires). The question was how to wire these connections without creating tight coupling.

**Decision**: All cross-domain integration uses plain Laravel events (readonly final classes). No domain calls another domain's action classes directly. Each domain's service provider listens to the events it cares about and calls its own actions in response.

The Workflow domain acts as a universal integration hub — any domain event can be mapped to a `WorkflowTrigger` string, allowing tenants to configure automation without code changes. New triggers are added by: (1) adding an enum case, (2) adding a listener in `WorkflowServiceProvider::boot()`.

The `MarkOverdueSlaRecords` scheduled command completes the SLA breach loop: it calls `BreachSlaRecord` which dispatches `SlaBreached`, which triggers any `sla.breached` workflows.

**Consequences**: The event contract between domains is the only coupling point. Adding a new domain requires only adding listeners — no changes to existing domains. The trade-off is that the event flow can be harder to trace end-to-end; the runbook's monitoring queries and integration test suite (`tests/Feature/Integration/`) compensate for this.
