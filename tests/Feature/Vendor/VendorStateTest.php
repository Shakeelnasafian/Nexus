<?php

use App\Domain\Vendor\Actions\ActivateVendor;
use App\Domain\Vendor\Actions\CreateVendor;
use App\Domain\Vendor\Actions\SuspendVendor;
use App\Domain\Vendor\Actions\TerminateVendor;
use App\Domain\Vendor\Events\VendorActivated;
use App\Domain\Vendor\Events\VendorSuspended;
use App\Domain\Vendor\Events\VendorTerminated;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Vendor\States\Vendor\Active;
use App\Domain\Vendor\States\Vendor\Pending;
use App\Domain\Vendor\States\Vendor\Suspended;
use App\Domain\Vendor\States\Vendor\Terminated;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Event;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

beforeEach(function () {
    $this->tenant = Tenant::create([
        'name' => 'Acme',
        'slug' => 'acme',
    ]);

    app(CurrentTenant::class)->set($this->tenant);

    $this->createVendor = app(CreateVendor::class);
    $this->activateVendor = app(ActivateVendor::class);
    $this->suspendVendor = app(SuspendVendor::class);
    $this->terminateVendor = app(TerminateVendor::class);
});

it('creates a vendor in the pending state', function () {
    $vendor = $this->createVendor->execute(
        name: 'Acme Supplies',
        code: 'acme-supplies',
        contactName: 'Jane Doe',
        contactEmail: 'jane@acme-supplies.example',
    );

    expect($vendor->state)->toBeInstanceOf(Pending::class)
        ->and($vendor->name)->toBe('Acme Supplies')
        ->and($vendor->code)->toBe('acme-supplies')
        ->and($vendor->tenant_id)->toBe($this->tenant->id);
});

it('transitions a vendor from pending to active', function () {
    $vendor = $this->createVendor->execute(name: 'Acme Supplies', code: 'acme-supplies');

    Event::fake([VendorActivated::class]);

    $this->activateVendor->execute($vendor);
    $vendor->refresh();

    expect($vendor->state)->toBeInstanceOf(Active::class)
        ->and($vendor->activated_at)->not->toBeNull();

    Event::assertDispatched(VendorActivated::class, function (VendorActivated $event) use ($vendor) {
        return $event->tenantId === $this->tenant->id
            && $event->vendorId === $vendor->id
            && $event->vendorName === $vendor->name;
    });
});

it('transitions an active vendor to suspended', function () {
    $vendor = $this->createVendor->execute(name: 'Acme Supplies', code: 'acme-supplies');
    $this->activateVendor->execute($vendor);
    $vendor->refresh();

    Event::fake([VendorSuspended::class]);

    $this->suspendVendor->execute($vendor);
    $vendor->refresh();

    expect($vendor->state)->toBeInstanceOf(Suspended::class)
        ->and($vendor->suspended_at)->not->toBeNull();

    Event::assertDispatched(VendorSuspended::class, fn (VendorSuspended $event) => $event->vendorId === $vendor->id);
});

it('reinstates a suspended vendor to active', function () {
    $vendor = $this->createVendor->execute(name: 'Acme Supplies', code: 'acme-supplies');
    $this->activateVendor->execute($vendor);
    $vendor->refresh();
    $this->suspendVendor->execute($vendor);
    $vendor->refresh();

    Event::fake([VendorActivated::class]);

    $this->activateVendor->execute($vendor);
    $vendor->refresh();

    expect($vendor->state)->toBeInstanceOf(Active::class);

    Event::assertDispatched(VendorActivated::class, 1);
});

it('terminates an active vendor', function () {
    $vendor = $this->createVendor->execute(name: 'Acme Supplies', code: 'acme-supplies');
    $this->activateVendor->execute($vendor);
    $vendor->refresh();

    Event::fake([VendorTerminated::class]);

    $this->terminateVendor->execute($vendor);
    $vendor->refresh();

    expect($vendor->state)->toBeInstanceOf(Terminated::class)
        ->and($vendor->terminated_at)->not->toBeNull();

    Event::assertDispatched(VendorTerminated::class, fn (VendorTerminated $event) => $event->vendorId === $vendor->id);
});

it('terminates a suspended vendor', function () {
    $vendor = $this->createVendor->execute(name: 'Acme Supplies', code: 'acme-supplies');
    $this->activateVendor->execute($vendor);
    $vendor->refresh();
    $this->suspendVendor->execute($vendor);
    $vendor->refresh();

    Event::fake([VendorTerminated::class]);

    $this->terminateVendor->execute($vendor);
    $vendor->refresh();

    expect($vendor->state)->toBeInstanceOf(Terminated::class);

    Event::assertDispatched(VendorTerminated::class);
});

it('prevents transitioning a pending vendor directly to suspended', function () {
    $vendor = $this->createVendor->execute(name: 'Acme Supplies', code: 'acme-supplies');

    expect(fn () => $this->suspendVendor->execute($vendor))
        ->toThrow(CouldNotPerformTransition::class);
});

it('prevents transitioning a terminated vendor to any state', function () {
    $vendor = $this->createVendor->execute(name: 'Acme Supplies', code: 'acme-supplies');
    $this->activateVendor->execute($vendor);
    $vendor->refresh();
    $this->terminateVendor->execute($vendor);
    $vendor->refresh();

    expect(fn () => $this->activateVendor->execute($vendor))
        ->toThrow(CouldNotPerformTransition::class);
});

it('prevents transitioning a pending vendor directly to terminated', function () {
    $vendor = $this->createVendor->execute(name: 'Acme Supplies', code: 'acme-supplies');

    expect(fn () => $this->terminateVendor->execute($vendor))
        ->toThrow(CouldNotPerformTransition::class);
});

it('scopes vendors to the current tenant', function () {
    $this->createVendor->execute(name: 'Acme Supplies', code: 'acme-supplies');

    $otherTenant = Tenant::create(['name' => 'Other Co', 'slug' => 'other-co']);
    app(CurrentTenant::class)->set($otherTenant);

    $this->createVendor->execute(name: 'Other Vendor', code: 'other-vendor');

    app(CurrentTenant::class)->set($this->tenant);

    expect(Vendor::query()->pluck('code')->all())->toBe(['acme-supplies']);
});
