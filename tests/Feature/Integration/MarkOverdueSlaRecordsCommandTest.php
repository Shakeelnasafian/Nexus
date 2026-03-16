<?php

use App\Domain\Accountability\Actions\OpenSlaRecord;
use App\Domain\Accountability\Enums\SlaStatus;
use App\Domain\Accountability\Models\SlaRecord;
use App\Domain\Vendor\Actions\CreateVendor;
use App\Domain\Workflow\Actions\CreateWorkflow;
use App\Domain\Workflow\Enums\WorkflowTrigger;
use App\Domain\Workflow\Jobs\RunWorkflowJob;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
    app(CurrentTenant::class)->set($this->tenant);
});

it('breaches SLA records that have been active longer than the configured days', function () {
    Bus::fake();

    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');

    // One overdue (started 31 days ago)
    SlaRecord::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'vendor_id' => $vendor->id,
        'title' => 'Overdue SLA',
        'status' => SlaStatus::Active,
        'started_at' => now()->subDays(31),
    ]);

    // One still within limit (started today)
    SlaRecord::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'vendor_id' => $vendor->id,
        'title' => 'Recent SLA',
        'status' => SlaStatus::Active,
        'started_at' => now(),
    ]);

    $this->artisan('sla:mark-overdue', ['--days' => 30])
        ->assertSuccessful()
        ->expectsOutputToContain('1 SLA record(s) breached');

    $records = SlaRecord::withoutGlobalScopes()->get();

    expect($records->firstWhere('title', 'Overdue SLA')->status)->toBe(SlaStatus::Breached)
        ->and($records->firstWhere('title', 'Recent SLA')->status)->toBe(SlaStatus::Active);
});

it('does not breach SLA records that are already closed or breached', function () {
    Bus::fake();

    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');

    SlaRecord::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'vendor_id' => $vendor->id,
        'title' => 'Already Closed',
        'status' => SlaStatus::Closed,
        'started_at' => now()->subDays(60),
        'closed_at' => now()->subDays(10),
    ]);

    $this->artisan('sla:mark-overdue', ['--days' => 30])
        ->assertSuccessful()
        ->expectsOutputToContain('0 SLA record(s) breached');
});

it('dispatches a workflow run when a record is breached by the command', function () {
    Bus::fake();

    app(CreateWorkflow::class)->execute(
        name: 'On SLA Breached',
        trigger: WorkflowTrigger::SlaBreached,
    );

    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');

    SlaRecord::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'vendor_id' => $vendor->id,
        'title' => 'Overdue SLA',
        'status' => SlaStatus::Active,
        'started_at' => now()->subDays(60),
    ]);

    $this->artisan('sla:mark-overdue')->assertSuccessful();

    Bus::assertDispatched(RunWorkflowJob::class);
});
