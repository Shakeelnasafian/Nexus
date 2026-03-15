<?php

use App\Domain\Workflow\Actions\CreateWorkflow;
use App\Domain\Workflow\Enums\WorkflowTrigger;
use App\Domain\Workflow\Models\Workflow;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;

beforeEach(function () {
    $this->withoutVite();
    $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
    app(CurrentTenant::class)->set($this->tenant);
});

it('renders the workflow index with all triggers available', function () {
    app(CreateWorkflow::class)->execute(name: 'On Vendor Activated', trigger: WorkflowTrigger::VendorActivated);

    $this->get('/workflows')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Workflows/Index')
            ->has('workflows', 1)
            ->has('workflows.0.name')
            ->has('workflows.0.trigger_event')
        );
});

it('renders the create page with available trigger values', function () {
    $this->get('/workflows/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Workflows/Create')
            ->has('triggers', count(WorkflowTrigger::cases()))
            ->where('triggers.0', WorkflowTrigger::VendorActivated->value)
        );
});

it('creates a workflow and redirects to its show page', function () {
    $this->post('/workflows', [
        'name'          => 'My Workflow',
        'trigger_event' => WorkflowTrigger::VendorActivated->value,
        'is_active'     => true,
    ])->assertRedirect();

    expect(Workflow::count())->toBe(1)
        ->and(Workflow::first()->name)->toBe('My Workflow');
});

it('validates required fields when storing a workflow', function () {
    $this->post('/workflows', [])->assertSessionHasErrors(['name', 'trigger_event']);
});

it('rejects an invalid trigger_event value', function () {
    $this->post('/workflows', [
        'name'          => 'Bad Workflow',
        'trigger_event' => 'not.a.real.trigger',
    ])->assertSessionHasErrors('trigger_event');
});

it('renders the workflow show page with its steps and runs', function () {
    $workflow = app(CreateWorkflow::class)->execute(name: 'On Vendor Activated', trigger: WorkflowTrigger::VendorActivated);
    $workflow->steps()->create(['sort_order' => 1, 'action_type' => 'log', 'payload' => ['message' => 'Hello']]);

    $this->get("/workflows/{$workflow->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Workflows/Show')
            ->where('workflow.name', 'On Vendor Activated')
            ->has('workflow.steps', 1)
            ->has('runs', 0)
        );
});
