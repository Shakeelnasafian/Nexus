<?php

use App\Domain\Accountability\Actions\CreateObjective;
use App\Domain\Accountability\Enums\ObjectiveStatus;
use App\Domain\Accountability\Models\Objective;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;

beforeEach(function () {
    $this->withoutVite();
    $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
    app(CurrentTenant::class)->set($this->tenant);
});

it('renders the objectives index', function () {
    app(CreateObjective::class)->execute(title: 'Reduce onboarding time');
    app(CreateObjective::class)->execute(title: 'Improve SLA compliance');

    $this->get('/objectives')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Objectives/Index')
            ->has('objectives', 2)
            ->has('objectives.0.title')
            ->has('objectives.0.status')
        );
});

it('renders the create page', function () {
    $this->get('/objectives/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Objectives/Create'));
});

it('creates an objective and redirects to the index', function () {
    $this->post('/objectives', [
        'title'       => 'Reduce ticket response time',
        'description' => 'Target: < 2h average',
        'due_date'    => now()->addMonth()->toDateString(),
    ])->assertRedirect('/objectives');

    expect(Objective::count())->toBe(1)
        ->and(Objective::first()->title)->toBe('Reduce ticket response time');
});

it('validates that title is required', function () {
    $this->post('/objectives', [])->assertSessionHasErrors('title');
});

it('marks an objective as completed via POST', function () {
    $objective = app(CreateObjective::class)->execute(title: 'Close 10 vendors');

    $this->post("/objectives/{$objective->id}/complete")->assertRedirect();
    expect($objective->fresh()->status)->toBe(ObjectiveStatus::Completed);
});

it('marks an objective as missed via POST', function () {
    $objective = app(CreateObjective::class)->execute(title: 'Close 10 vendors');

    $this->post("/objectives/{$objective->id}/miss")->assertRedirect();
    expect($objective->fresh()->status)->toBe(ObjectiveStatus::Missed);
});
