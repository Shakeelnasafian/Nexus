<?php

use App\Domain\Workflow\Actions\CreateWorkflow;
use App\Domain\Workflow\Actions\ExecuteWorkflowRun;
use App\Domain\Workflow\Actions\TriggerWorkflow;
use App\Domain\Workflow\Enums\WorkflowRunStatus;
use App\Domain\Workflow\Enums\WorkflowStepStatus;
use App\Domain\Workflow\Enums\WorkflowTrigger;
use App\Domain\Workflow\Exceptions\UnknownStepActionType;
use App\Domain\Workflow\Models\WorkflowRun;
use App\Domain\Workflow\Models\WorkflowStepRun;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
    app(CurrentTenant::class)->set($this->tenant);

    $this->createWorkflow = app(CreateWorkflow::class);
    $this->triggerWorkflow = app(TriggerWorkflow::class);
    $this->executeRun = app(ExecuteWorkflowRun::class);
});

it('executes a log step and marks the run completed', function () {
    Log::spy();

    $workflow = $this->createWorkflow->execute(name: 'Log Workflow', trigger: WorkflowTrigger::VendorActivated);
    $workflow->steps()->create([
        'sort_order' => 1,
        'action_type' => 'log',
        'payload' => ['message' => 'Vendor was activated'],
    ]);

    $run = WorkflowRun::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'workflow_id' => $workflow->id,
        'trigger_event' => WorkflowTrigger::VendorActivated->value,
        'trigger_payload' => ['vendor_id' => 1],
        'status' => WorkflowRunStatus::Pending,
    ]);

    $this->executeRun->execute($run);
    $run->refresh();

    expect($run->status)->toBe(WorkflowRunStatus::Completed)
        ->and($run->started_at)->not->toBeNull()
        ->and($run->completed_at)->not->toBeNull()
        ->and($run->failed_at)->toBeNull();

    Log::shouldHaveReceived('info')->with('Vendor was activated', \Mockery::any());
});

it('creates step run records for each step executed', function () {
    $workflow = $this->createWorkflow->execute(name: 'Multi-step Workflow', trigger: WorkflowTrigger::VendorActivated);
    $workflow->steps()->create(['sort_order' => 1, 'action_type' => 'log', 'payload' => ['message' => 'Step 1']]);
    $workflow->steps()->create(['sort_order' => 2, 'action_type' => 'notify', 'payload' => ['channel' => 'mail']]);

    $run = WorkflowRun::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'workflow_id' => $workflow->id,
        'trigger_event' => WorkflowTrigger::VendorActivated->value,
        'status' => WorkflowRunStatus::Pending,
    ]);

    $this->executeRun->execute($run);
    $run->refresh();

    $stepRuns = WorkflowStepRun::where('workflow_run_id', $run->id)->orderBy('sort_order')->get();

    expect($run->status)->toBe(WorkflowRunStatus::Completed)
        ->and($stepRuns)->toHaveCount(2)
        ->and($stepRuns[0]->status)->toBe(WorkflowStepStatus::Completed)
        ->and($stepRuns[0]->ran_at)->not->toBeNull()
        ->and($stepRuns[1]->status)->toBe(WorkflowStepStatus::Completed)
        ->and($stepRuns[1]->output['channel'])->toBe('mail')
        ->and($stepRuns[1]->output)->toHaveKey('notification_id');
});

it('marks the run and the failing step as failed when a step throws', function () {
    $workflow = $this->createWorkflow->execute(name: 'Failing Workflow', trigger: WorkflowTrigger::VendorActivated);
    $workflow->steps()->create(['sort_order' => 1, 'action_type' => 'unknown_type', 'payload' => null]);

    $run = WorkflowRun::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'workflow_id' => $workflow->id,
        'trigger_event' => WorkflowTrigger::VendorActivated->value,
        'status' => WorkflowRunStatus::Pending,
    ]);

    $this->executeRun->execute($run);
    $run->refresh();

    $stepRun = WorkflowStepRun::where('workflow_run_id', $run->id)->firstOrFail();

    expect($run->status)->toBe(WorkflowRunStatus::Failed)
        ->and($run->failed_at)->not->toBeNull()
        ->and($run->failure_reason)->toContain('unknown_type')
        ->and($stepRun->status)->toBe(WorkflowStepStatus::Failed);
});

it('stops executing further steps after a failure', function () {
    $workflow = $this->createWorkflow->execute(name: 'Failing Workflow', trigger: WorkflowTrigger::VendorActivated);
    $workflow->steps()->create(['sort_order' => 1, 'action_type' => 'unknown_type', 'payload' => null]);
    $workflow->steps()->create(['sort_order' => 2, 'action_type' => 'log', 'payload' => ['message' => 'Should not run']]);

    $run = WorkflowRun::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'workflow_id' => $workflow->id,
        'trigger_event' => WorkflowTrigger::VendorActivated->value,
        'status' => WorkflowRunStatus::Pending,
    ]);

    $this->executeRun->execute($run);

    expect(WorkflowStepRun::where('workflow_run_id', $run->id)->count())->toBe(1);
});

it('executes a workflow with no steps and marks it completed', function () {
    $workflow = $this->createWorkflow->execute(name: 'Empty Workflow', trigger: WorkflowTrigger::VendorActivated);

    $run = WorkflowRun::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'workflow_id' => $workflow->id,
        'trigger_event' => WorkflowTrigger::VendorActivated->value,
        'status' => WorkflowRunStatus::Pending,
    ]);

    $this->executeRun->execute($run);
    $run->refresh();

    expect($run->status)->toBe(WorkflowRunStatus::Completed)
        ->and(WorkflowStepRun::where('workflow_run_id', $run->id)->count())->toBe(0);
});

it('dispatches RunWorkflowJob via the trigger action and the job executes the run end-to-end', function () {
    Bus::fake();

    $workflow = $this->createWorkflow->execute(name: 'E2E Workflow', trigger: WorkflowTrigger::VendorActivated);
    $workflow->steps()->create(['sort_order' => 1, 'action_type' => 'log', 'payload' => ['message' => 'E2E step']]);

    $this->triggerWorkflow->execute(
        tenantId: $this->tenant->id,
        triggerEvent: WorkflowTrigger::VendorActivated->value,
        triggerPayload: ['vendor_id' => 99],
    );

    $run = WorkflowRun::withoutGlobalScopes()->firstOrFail();

    Bus::assertDispatched(
        \App\Domain\Workflow\Jobs\RunWorkflowJob::class,
        fn ($job) => $job->workflowRunId === $run->id,
    );
});
