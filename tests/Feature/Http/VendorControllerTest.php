<?php

use App\Domain\Vendor\Actions\ActivateVendor;
use App\Domain\Vendor\Actions\CreateVendor;
use App\Domain\Vendor\Actions\CreateContract;
use App\Domain\Vendor\Models\Vendor;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    $this->withoutVite();
    $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
    app(CurrentTenant::class)->set($this->tenant);
});

// ── index ─────────────────────────────────────────────────────────────────────

it('renders the vendor index with a list of vendors', function () {
    app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');
    app(CreateVendor::class)->execute(name: 'Beta Parts',   code: 'beta-parts');

    $this->get('/vendors')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendors/Index')
            ->has('vendors', 2)
            ->has('vendors.0.name')
            ->has('vendors.0.state')
        );
});

// ── create ────────────────────────────────────────────────────────────────────

it('renders the vendor create page', function () {
    $this->get('/vendors/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Vendors/Create'));
});

// ── store ─────────────────────────────────────────────────────────────────────

it('creates a vendor and redirects to its show page', function () {
    $response = $this->post('/vendors', [
        'name' => 'NewCo',
        'code' => 'newco',
    ]);

    $vendor = Vendor::first();
    $response->assertRedirect("/vendors/{$vendor->id}");
    expect($vendor->name)->toBe('NewCo')->and($vendor->code)->toBe('newco');
});

it('validates required fields when storing a vendor', function () {
    $this->post('/vendors', [])->assertSessionHasErrors(['name', 'code']);
});

it('validates that vendor code must be unique', function () {
    app(CreateVendor::class)->execute(name: 'Existing', code: 'taken');

    $this->post('/vendors', ['name' => 'New', 'code' => 'taken'])
        ->assertSessionHasErrors('code');
});

// ── show ──────────────────────────────────────────────────────────────────────

it('renders the vendor show page with its contracts', function () {
    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');
    app(ActivateVendor::class)->execute($vendor);
    app(CreateContract::class)->execute(vendor: $vendor, title: 'Supply Agreement', startsAt: now());

    $this->get("/vendors/{$vendor->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendors/Show')
            ->where('vendor.id', $vendor->id)
            ->where('vendor.name', 'Acme Supplies')
            ->has('contracts', 1)
            ->has('contracts.0.title')
        );
});

// ── actions ───────────────────────────────────────────────────────────────────

it('activates a vendor and redirects back', function () {
    Bus::fake();
    $vendor = app(CreateVendor::class)->execute(name: 'Acme', code: 'acme');

    $this->post("/vendors/{$vendor->id}/activate")->assertRedirect();
    expect($vendor->fresh()->state->getValue())->toBe('active');
});

it('suspends an active vendor', function () {
    Bus::fake();
    $vendor = app(CreateVendor::class)->execute(name: 'Acme', code: 'acme');
    app(ActivateVendor::class)->execute($vendor);

    $this->post("/vendors/{$vendor->id}/suspend")->assertRedirect();
    expect($vendor->fresh()->state->getValue())->toBe('suspended');
});
