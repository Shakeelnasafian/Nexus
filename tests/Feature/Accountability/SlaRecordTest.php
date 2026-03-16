<?php

use App\Domain\Accountability\Actions\BreachSlaRecord;
use App\Domain\Accountability\Actions\CloseSlaRecordsForVendor;
use App\Domain\Accountability\Actions\OpenSlaRecord;
use App\Domain\Accountability\Enums\SlaStatus;
use App\Domain\Accountability\Events\SlaBreached;
use App\Domain\Accountability\Models\SlaRecord;
use App\Domain\Vendor\Actions\ActivateVendor;
use App\Domain\Vendor\Actions\ActivateContract;
use App\Domain\Vendor\Actions\CreateContract;
use App\Domain\Vendor\Actions\CreateVendor;
use App\Domain\Vendor\Actions\TerminateVendor;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
    app(CurrentTenant::class)->set($this->tenant);

    $this->open = app(OpenSlaRecord::class);
    $this->breach = app(BreachSlaRecord::class);
    $this->close = app(CloseSlaRecordsForVendor::class);
});

it('opens an SLA record in active status', function () {
    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');

    $record = $this->open->execute(
        tenantId: $this->tenant->id,
        vendorId: $vendor->id,
        title: 'Contract SLA: Supply Agreement',
    );

    expect($record->status)->toBe(SlaStatus::Active)
        ->and($record->tenant_id)->toBe($this->tenant->id)
        ->and($record->vendor_id)->toBe($vendor->id)
        ->and($record->started_at)->not->toBeNull()
        ->and($record->closed_at)->toBeNull();
});

it('breaches an SLA record and dispatches SlaBreached event', function () {
    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');
    $record = $this->open->execute(
        tenantId: $this->tenant->id,
        vendorId: $vendor->id,
        title: 'Q1 SLA',
    );

    Event::fake([SlaBreached::class]);

    $this->breach->execute($record);
    $record->refresh();

    expect($record->status)->toBe(SlaStatus::Breached)
        ->and($record->breached_at)->not->toBeNull();

    Event::assertDispatched(SlaBreached::class, fn (SlaBreached $e) =>
        $e->tenantId === $this->tenant->id &&
        $e->slaRecordId === $record->id &&
        $e->vendorId === $vendor->id
    );
});

it('closes all active SLA records for a vendor', function () {
    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');
    $this->open->execute(tenantId: $this->tenant->id, vendorId: $vendor->id, title: 'SLA A');
    $this->open->execute(tenantId: $this->tenant->id, vendorId: $vendor->id, title: 'SLA B');

    $this->close->execute(tenantId: $this->tenant->id, vendorId: $vendor->id);

    $records = SlaRecord::withoutGlobalScopes()->where('vendor_id', $vendor->id)->get();

    expect($records)->toHaveCount(2)
        ->and($records->every(fn ($r) => $r->status === SlaStatus::Closed))->toBeTrue()
        ->and($records->every(fn ($r) => $r->closed_at !== null))->toBeTrue();
});

it('does not close SLA records that are already breached or closed', function () {
    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');
    $active = $this->open->execute(tenantId: $this->tenant->id, vendorId: $vendor->id, title: 'Active SLA');
    $breached = $this->open->execute(tenantId: $this->tenant->id, vendorId: $vendor->id, title: 'Breached SLA');
    $this->breach->execute($breached);

    $this->close->execute(tenantId: $this->tenant->id, vendorId: $vendor->id);

    $active->refresh();
    $breached->refresh();

    // Only the active one should be closed; the already-breached one is untouched by close action
    expect($active->status)->toBe(SlaStatus::Closed)
        ->and($breached->status)->toBe(SlaStatus::Breached);
});

it('opens an SLA record automatically when a contract is activated', function () {
    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');
    app(ActivateVendor::class)->execute($vendor);

    $contract = app(CreateContract::class)->execute(vendor: $vendor, title: 'Supply Agreement 2026', startsAt: now());
    app(ActivateContract::class)->execute($contract);

    $record = SlaRecord::withoutGlobalScopes()
        ->where('vendor_id', $vendor->id)
        ->where('contract_id', $contract->id)
        ->firstOrFail();

    expect($record->status)->toBe(SlaStatus::Active)
        ->and($record->title)->toBe('Contract SLA: Supply Agreement 2026');
});

it('closes active SLA records when a vendor is terminated', function () {
    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');
    app(ActivateVendor::class)->execute($vendor);
    $this->open->execute(tenantId: $this->tenant->id, vendorId: $vendor->id, title: 'Manual SLA');

    app(TerminateVendor::class)->execute($vendor);

    $records = SlaRecord::withoutGlobalScopes()->where('vendor_id', $vendor->id)->get();
    expect($records->every(fn ($r) => $r->status === SlaStatus::Closed))->toBeTrue();
});
