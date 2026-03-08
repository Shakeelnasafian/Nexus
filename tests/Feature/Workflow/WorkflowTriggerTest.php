<?php

use App\Domain\Vendor\Actions\ActivateVendor;
use App\Domain\Vendor\Actions\CreateVendor;
use App\Domain\Vendor\Events\VendorActivated;
use App\Domain\Workflow\Actions\CreateWorkflow;
use App\Domain\Workflow\Actions\TriggerWorkflow;
use App\Domain\Workflow\Enums\WorkflowRunStatus;
use App\Domain\Workflow\Enums\WorkflowTrigger;
use App\Domain\Workflow\Jobs\RunWorkflowJob;
use App\Domain\Workflow\Models\WorkflowRun;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
    app(CurrentTenant::class)->set($this->tenant);

    $this->createWorkflow = app(CreateWorkflow::class);
    $this->triggerWorkflow = app(TriggerWorkflow::class);
});

it('creates a workflow run and dispatches a job when a matching trigger fires', function () {
    Bus::fake();

    $workflow = $this->createWorkflow->execute(
        name: 'On Vendor Activated',
        trigger: WorkflowTrigger::VendorActivated,
    );

    $this->triggerWorkflow->execute(
        tenantId: $this->tenant->id,
        triggerEvent: WorkflowTrigger::VendorActivated->value,
        triggerPayload: ['vendor_id' => 1, 'vendor_name' => 'Acme Supplies'],
    );

    $run = WorkflowRun::withoutGlobalScopes()
        ->where('workflow_id', $workflow->id)
        ->firstOrFail();

    expect($run->status)->toBe(WorkflowRunStatus::Pending)
        ->and($run->tenant_id)->toBe($this->tenant->id)
        ->and($run->trigger_event)->toBe(WorkflowTrigger::VendorActivated->value)
        ->and($run->trigger_payload)->toBe(['vendor_id' => 1, 'vendor_name' => 'Acme Supplies']);

    Bus::assertDispatched(RunWorkflowJob::class, fn (RunWorkflowJob $job) => $job->workflowRunId === $run->id);
});

it('does not create a run for a non-matching trigger', function () {
    Bus::fake();

    $this->createWorkflow->execute(
        name: 'On Vendor Activated',
        trigger: WorkflowTrigger::VendorActivated,
    );

    $this->triggerWorkflow->execute(
        tenantId: $this->tenant->id,
        triggerEvent: WorkflowTrigger::VendorSuspended->value,
    );

    expect(WorkflowRun::withoutGlobalScopes()->count())->toBe(0);
    Bus::assertNothingDispatched();
});

it('does not create a run for an inactive workflow', function () {
    Bus::fake();

    $this->createWorkflow->execute(
        name: 'On Vendor Activated (disabled)',
        trigger: WorkflowTrigger::VendorActivated,
        isActive: false,
    );

    $this->triggerWorkflow->execute(
        tenantId: $this->tenant->id,
        triggerEvent: WorkflowTrigger::VendorActivated->value,
    );

    expect(WorkflowRun::withoutGlobalScopes()->count())->toBe(0);
    Bus::assertNothingDispatched();
});

it('dispatches a run for each matching active workflow', function () {
    Bus::fake();

    $workflowA = $this->createWorkflow->execute(name: 'Workflow A', trigger: WorkflowTrigger::VendorActivated);
    $workflowB = $this->createWorkflow->execute(name: 'Workflow B', trigger: WorkflowTrigger::VendorActivated);

    $this->triggerWorkflow->execute(
        tenantId: $this->tenant->id,
        triggerEvent: WorkflowTrigger::VendorActivated->value,
    );

    expect(WorkflowRun::withoutGlobalScopes()->count())->toBe(2);
    Bus::assertDispatchedTimes(RunWorkflowJob::class, 2);
});

it('does not trigger workflows for a different tenant', function () {
    Bus::fake();

    $this->createWorkflow->execute(name: 'Acme Workflow', trigger: WorkflowTrigger::VendorActivated);

    $otherTenant = Tenant::create(['name' => 'Other Co', 'slug' => 'other-co']);

    $this->triggerWorkflow->execute(
        tenantId: $otherTenant->id,
        triggerEvent: WorkflowTrigger::VendorActivated->value,
    );

    expect(WorkflowRun::withoutGlobalScopes()->count())->toBe(0);
    Bus::assertNothingDispatched();
});

it('triggers a workflow run when a VendorActivated domain event fires', function () {
    Bus::fake();

    $this->createWorkflow->execute(name: 'On Vendor Activated', trigger: WorkflowTrigger::VendorActivated);

    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');
    app(ActivateVendor::class)->execute($vendor);

    Bus::assertDispatched(RunWorkflowJob::class);
    expect(WorkflowRun::withoutGlobalScopes()->count())->toBe(1);
});
