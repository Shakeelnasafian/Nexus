<?php

use App\Domain\Vendor\Actions\ActivateContract;
use App\Domain\Vendor\Actions\ActivateVendor;
use App\Domain\Vendor\Actions\CreateContract;
use App\Domain\Vendor\Actions\CreateVendor;
use App\Domain\Vendor\Actions\ExpireContract;
use App\Domain\Vendor\Actions\TerminateContract;
use App\Domain\Vendor\Events\ContractActivated;
use App\Domain\Vendor\Models\Contract;
use App\Domain\Vendor\States\Contract\Active;
use App\Domain\Vendor\States\Contract\Draft;
use App\Domain\Vendor\States\Contract\Expired;
use App\Domain\Vendor\States\Contract\Terminated;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

beforeEach(function () {
    $this->tenant = Tenant::create([
        'name' => 'Acme',
        'slug' => 'acme',
    ]);

    app(CurrentTenant::class)->set($this->tenant);

    $createVendor = app(CreateVendor::class);
    $activateVendor = app(ActivateVendor::class);

    $this->vendor = $createVendor->execute(name: 'Acme Supplies', code: 'acme-supplies');
    $activateVendor->execute($this->vendor);
    $this->vendor->refresh();

    $this->createContract = app(CreateContract::class);
    $this->activateContract = app(ActivateContract::class);
    $this->expireContract = app(ExpireContract::class);
    $this->terminateContract = app(TerminateContract::class);

    $this->startsAt = CarbonImmutable::parse('2026-03-08');
    $this->endsAt = CarbonImmutable::parse('2027-03-07');
});

it('creates a contract in the draft state', function () {
    $contract = $this->createContract->execute(
        vendor: $this->vendor,
        title: 'Annual Supply Agreement',
        startsAt: $this->startsAt,
        endsAt: $this->endsAt,
        valueAmount: 120000,
        currency: 'USD',
    );

    expect($contract->state)->toBeInstanceOf(Draft::class)
        ->and($contract->title)->toBe('Annual Supply Agreement')
        ->and($contract->vendor_id)->toBe($this->vendor->id)
        ->and($contract->tenant_id)->toBe($this->tenant->id)
        ->and($contract->value_amount)->toBe(120000);
});

it('transitions a contract from draft to active', function () {
    $contract = $this->createContract->execute(
        vendor: $this->vendor,
        title: 'Annual Supply Agreement',
        startsAt: $this->startsAt,
    );

    Event::fake([ContractActivated::class]);

    $this->activateContract->execute($contract);
    $contract->refresh();

    expect($contract->state)->toBeInstanceOf(Active::class)
        ->and($contract->activated_at)->not->toBeNull();

    Event::assertDispatched(ContractActivated::class, function (ContractActivated $event) use ($contract) {
        return $event->tenantId === $this->tenant->id
            && $event->vendorId === $this->vendor->id
            && $event->contractId === $contract->id
            && $event->contractTitle === $contract->title;
    });
});

it('transitions an active contract to expired', function () {
    $contract = $this->createContract->execute(
        vendor: $this->vendor,
        title: 'Annual Supply Agreement',
        startsAt: $this->startsAt,
        endsAt: $this->endsAt,
    );

    $this->activateContract->execute($contract);
    $contract->refresh();

    $this->expireContract->execute($contract);
    $contract->refresh();

    expect($contract->state)->toBeInstanceOf(Expired::class)
        ->and($contract->expired_at)->not->toBeNull();
});

it('transitions an active contract to terminated', function () {
    $contract = $this->createContract->execute(
        vendor: $this->vendor,
        title: 'Annual Supply Agreement',
        startsAt: $this->startsAt,
    );

    $this->activateContract->execute($contract);
    $contract->refresh();

    $this->terminateContract->execute($contract);
    $contract->refresh();

    expect($contract->state)->toBeInstanceOf(Terminated::class)
        ->and($contract->terminated_at)->not->toBeNull();
});

it('prevents transitioning a draft contract to expired', function () {
    $contract = $this->createContract->execute(
        vendor: $this->vendor,
        title: 'Annual Supply Agreement',
        startsAt: $this->startsAt,
    );

    expect(fn () => $this->expireContract->execute($contract))
        ->toThrow(CouldNotPerformTransition::class);
});

it('prevents transitioning a draft contract to terminated', function () {
    $contract = $this->createContract->execute(
        vendor: $this->vendor,
        title: 'Annual Supply Agreement',
        startsAt: $this->startsAt,
    );

    expect(fn () => $this->terminateContract->execute($contract))
        ->toThrow(CouldNotPerformTransition::class);
});

it('prevents re-activating an expired contract', function () {
    $contract = $this->createContract->execute(
        vendor: $this->vendor,
        title: 'Annual Supply Agreement',
        startsAt: $this->startsAt,
    );

    $this->activateContract->execute($contract);
    $contract->refresh();
    $this->expireContract->execute($contract);
    $contract->refresh();

    expect(fn () => $this->activateContract->execute($contract))
        ->toThrow(CouldNotPerformTransition::class);
});

it('scopes contracts to the current tenant', function () {
    $this->createContract->execute(
        vendor: $this->vendor,
        title: 'Acme Contract',
        startsAt: $this->startsAt,
    );

    $otherTenant = Tenant::create(['name' => 'Other Co', 'slug' => 'other-co']);
    app(CurrentTenant::class)->set($otherTenant);

    $otherVendor = app(CreateVendor::class)->execute(name: 'Other Vendor', code: 'other-vendor');
    app(ActivateVendor::class)->execute($otherVendor);
    $otherVendor->refresh();

    $this->createContract->execute(
        vendor: $otherVendor,
        title: 'Other Contract',
        startsAt: $this->startsAt,
    );

    app(CurrentTenant::class)->set($this->tenant);

    expect(Contract::query()->pluck('title')->all())->toBe(['Acme Contract']);
});
