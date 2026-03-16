<?php

use App\Domain\Accountability\Actions\OpenSlaRecord;
use App\Domain\Vendor\Actions\CreateVendor;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;

beforeEach(function () {
    $this->withoutVite();
    $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
    app(CurrentTenant::class)->set($this->tenant);
});

it('renders the SLA records index with vendor eager loaded', function () {
    $vendor = app(CreateVendor::class)->execute(name: 'Acme Supplies', code: 'acme-supplies');
    app(OpenSlaRecord::class)->execute(
        tenantId: $this->tenant->id,
        vendorId: $vendor->id,
        title: 'Q1 SLA',
    );

    $this->get('/sla-records')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('SlaRecords/Index')
            ->has('records', 1)
            ->has('records.0.title')
            ->has('records.0.status')
            ->has('records.0.vendor')
            ->where('records.0.vendor.name', 'Acme Supplies')
        );
});

it('returns an empty list when no records exist', function () {
    $this->get('/sla-records')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('SlaRecords/Index')
            ->has('records', 0)
        );
});
