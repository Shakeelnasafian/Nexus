<?php

use App\Domain\Workflow\Actions\CreateWorkflow;
use App\Domain\Workflow\Enums\WorkflowRunStatus;
use App\Domain\Workflow\Enums\WorkflowRunStatus as RunStatus;
use App\Domain\Workflow\Enums\WorkflowStepStatus;
use App\Domain\Workflow\Enums\WorkflowTrigger;
use App\Domain\Workflow\Jobs\SendWorkflowNotification;
use App\Domain\Workflow\Models\WorkflowNotification;
use App\Domain\Workflow\Models\WorkflowRun;
use App\Domain\Workflow\Actions\ExecuteWorkflowRun;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
    app(CurrentTenant::class)->set($this->tenant);

    $this->executeRun = app(ExecuteWorkflowRun::class);
});

it('creates a WorkflowNotification record when a notify step runs', function () {
    Bus::fake([SendWorkflowNotification::class]);

    $workflow = app(CreateWorkflow::class)->execute(
        name: 'Notify on Vendor Activated',
        trigger: WorkflowTrigger::VendorActivated,
    );
    $workflow->steps()->create([
        'sort_order' => 1,
        'action_type' => 'notify',
        'payload' => ['channel' => 'mail', 'message' => 'A vendor was activated.'],
    ]);

    $run = WorkflowRun::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'workflow_id' => $workflow->id,
        'trigger_event' => WorkflowTrigger::VendorActivated->value,
        'status' => RunStatus::Pending,
    ]);

    $this->executeRun->execute($run);
    $run->refresh();

    $notification = WorkflowNotification::withoutGlobalScopes()->firstOrFail();

    expect($run->status)->toBe(RunStatus::Completed)
        ->and($notification->tenant_id)->toBe($this->tenant->id)
        ->and($notification->workflow_run_id)->toBe($run->id)
        ->and($notification->channel)->toBe('mail')
        ->and($notification->message)->toBe('A vendor was activated.');

    Bus::assertDispatched(SendWorkflowNotification::class,
        fn ($job) => $job->notificationId === $notification->id
    );
});

it('marks the notification as sent when SendWorkflowNotification job runs', function () {
    Bus::fake([SendWorkflowNotification::class]);

    $workflow = app(CreateWorkflow::class)->execute(
        name: 'Notify',
        trigger: WorkflowTrigger::VendorActivated,
    );
    $workflow->steps()->create([
        'sort_order' => 1,
        'action_type' => 'notify',
        'payload' => ['channel' => 'slack'],
    ]);

    $run = WorkflowRun::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'workflow_id' => $workflow->id,
        'trigger_event' => WorkflowTrigger::VendorActivated->value,
        'status' => RunStatus::Pending,
    ]);

    $this->executeRun->execute($run);

    // Run the job directly (bypassing the queue)
    $notification = WorkflowNotification::withoutGlobalScopes()->firstOrFail();
    (new \App\Domain\Workflow\Jobs\SendWorkflowNotification($notification->id))->handle();
    $notification->refresh();

    expect($notification->sent_at)->not->toBeNull()
        ->and($notification->failed_at)->toBeNull();
});

it('records notification_id and channel in the step run output', function () {
    Bus::fake([SendWorkflowNotification::class]);

    $workflow = app(CreateWorkflow::class)->execute(
        name: 'Notify',
        trigger: WorkflowTrigger::VendorActivated,
    );
    $workflow->steps()->create([
        'sort_order' => 1,
        'action_type' => 'notify',
        'payload' => ['channel' => 'teams'],
    ]);

    $run = WorkflowRun::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'workflow_id' => $workflow->id,
        'trigger_event' => WorkflowTrigger::VendorActivated->value,
        'status' => RunStatus::Pending,
    ]);

    $this->executeRun->execute($run);

    $stepRun = $run->stepRuns()->firstOrFail();

    expect($stepRun->status)->toBe(WorkflowStepStatus::Completed)
        ->and($stepRun->output['channel'])->toBe('teams')
        ->and($stepRun->output)->toHaveKey('notification_id');
});
