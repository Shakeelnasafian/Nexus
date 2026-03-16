<?php

use App\Domain\Accountability\Actions\BreachSlaRecord;
use App\Domain\Accountability\Actions\CompleteObjective;
use App\Domain\Accountability\Actions\CreateObjective;
use App\Domain\Accountability\Actions\OpenSlaRecord;
use App\Domain\Accountability\Enums\SlaStatus;
use App\Domain\Accountability\Models\SlaRecord;
use App\Domain\Vendor\Actions\ActivateContract;
use App\Domain\Vendor\Actions\ActivateVendor;
use App\Domain\Vendor\Actions\CreateContract;
use App\Domain\Vendor\Actions\CreateVendor;
use App\Domain\Vendor\Actions\TerminateVendor;
use App\Domain\Workflow\Actions\CreateWorkflow;
use App\Domain\Workflow\Enums\WorkflowRunStatus;
use App\Domain\Workflow\Enums\WorkflowTrigger;
use App\Domain\Workflow\Jobs\RunWorkflowJob;
use App\Domain\Workflow\Models\WorkflowRun;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
    app(CurrentTenant::class)->set($this->tenant);
});

// ── ContractActivated ─────────────────────────────────────────────────────────

it('opening a contract creates an SLA record and triggers matching workflows', function () {
    Bus::fake();

    app(CreateWorkflow::class)->execute(
        name: 'On Contract Activated',
        trigger: WorkflowTrigger::ContractActivated,
    );

    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');
    app(ActivateVendor::class)->execute($vendor);
    $contract = app(CreateContract::class)->execute(vendor: $vendor, title: 'Supply Agreement', startsAt: now());

    app(ActivateContract::class)->execute($contract);

    // Accountability side: SLA record created
    $sla = SlaRecord::withoutGlobalScopes()
        ->where('contract_id', $contract->id)
        ->firstOrFail();
    expect($sla->status)->toBe(SlaStatus::Active);

    // Workflow side: run dispatched
    Bus::assertDispatched(RunWorkflowJob::class);
    expect(WorkflowRun::withoutGlobalScopes()->count())->toBe(1);
});

// ── VendorTerminated ──────────────────────────────────────────────────────────

it('terminating a vendor closes its SLA records and triggers matching workflows', function () {
    Bus::fake();

    app(CreateWorkflow::class)->execute(
        name: 'On Vendor Terminated',
        trigger: WorkflowTrigger::VendorTerminated,
    );

    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');
    app(ActivateVendor::class)->execute($vendor);
    app(OpenSlaRecord::class)->execute(
        tenantId: $this->tenant->id,
        vendorId: $vendor->id,
        title: 'Manual SLA',
    );

    app(TerminateVendor::class)->execute($vendor);

    // Accountability side: SLA record closed
    $sla = SlaRecord::withoutGlobalScopes()->where('vendor_id', $vendor->id)->firstOrFail();
    expect($sla->status)->toBe(SlaStatus::Closed);

    // Workflow side: run dispatched
    Bus::assertDispatched(RunWorkflowJob::class);
});

// ── SlaBreached ───────────────────────────────────────────────────────────────

it('breaching an SLA record triggers matching workflows', function () {
    Bus::fake();

    app(CreateWorkflow::class)->execute(
        name: 'On SLA Breached',
        trigger: WorkflowTrigger::SlaBreached,
    );

    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');
    $sla = app(OpenSlaRecord::class)->execute(
        tenantId: $this->tenant->id,
        vendorId: $vendor->id,
        title: 'Q1 SLA',
    );

    app(BreachSlaRecord::class)->execute($sla);

    Bus::assertDispatched(RunWorkflowJob::class);
    $run = WorkflowRun::withoutGlobalScopes()->firstOrFail();
    expect($run->trigger_event)->toBe(WorkflowTrigger::SlaBreached->value)
        ->and($run->trigger_payload['sla_record_id'])->toBe($sla->id);
});

// ── ObjectiveCompleted ────────────────────────────────────────────────────────

it('completing an objective triggers matching workflows', function () {
    Bus::fake();

    app(CreateWorkflow::class)->execute(
        name: 'On Objective Completed',
        trigger: WorkflowTrigger::ObjectiveCompleted,
    );

    $objective = app(CreateObjective::class)->execute(title: 'Reduce onboarding time');
    app(CompleteObjective::class)->execute($objective);

    Bus::assertDispatched(RunWorkflowJob::class);
    $run = WorkflowRun::withoutGlobalScopes()->firstOrFail();
    expect($run->trigger_event)->toBe(WorkflowTrigger::ObjectiveCompleted->value)
        ->and($run->trigger_payload['title'])->toBe('Reduce onboarding time');
});
