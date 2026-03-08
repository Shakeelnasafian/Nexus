# Nexus Architecture Contract

## Product Context
- Application: `Nexus`
- Stack: Laravel + Inertia.js + Vue 3
- Product shape: B2B enterprise SaaS

## Bounded Domains
1. Billing
   - Event sourced
   - Uses Spatie Event Sourcing
   - Handles subscriptions, plans, and charges
2. Vendor
   - Lifecycle driven by state machines
   - Uses Spatie Model States
   - Handles vendors and contract management
3. Accountability
   - Handles team objectives, SLA tracking, and client reporting
4. Workflow
   - Cross-domain automation
   - Workflows are stored as database data

## Global Rules
- Multi-tenant from day one
- Every tenant-aware query is scoped to the current tenant via a global scope
- Inter-domain communication happens through domain events only
- Controllers stay thin
- Use Form Requests for validation
- Use Action classes for application logic
- Use web routes only
- Do not introduce `api.php`
- Use Pest for tests
- Use `assertInertia()` for Inertia responses
- Use Spatie Permissions with roles scoped per tenant

## Session Discipline
- Work one domain per session
- Scaffold first, logic second
- Write tests for domain logic before controllers or Inertia pages
- If implementation drifts into fat controllers, direct cross-domain calls, or `api.php`, stop and refactor before continuing
- End each session with:
  - what was built
  - which contracts were established through published and consumed events
  - what the next session should start with

## Suggested Build Order
1. Tenant scaffolding, global scope, and domain service providers
2. Billing domain
3. Vendor domain
4. Workflow domain
5. Accountability domain
6. Cross-domain integration
7. Inertia pages
8. ADRs, `PLATFORM.md`, and runbook

## Bootstrap Scope
- Start by scaffolding the folder structure and the tenant plus domain service providers only
- Do not write business logic during bootstrap