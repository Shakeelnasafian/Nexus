<?php

use App\Domain\Accountability\Actions\CompleteObjective;
use App\Domain\Accountability\Actions\CreateObjective;
use App\Domain\Accountability\Actions\MissObjective;
use App\Domain\Accountability\Enums\ObjectiveStatus;
use App\Domain\Accountability\Events\ObjectiveCompleted;
use App\Domain\Accountability\Models\Objective;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
    app(CurrentTenant::class)->set($this->tenant);

    $this->create = app(CreateObjective::class);
    $this->complete = app(CompleteObjective::class);
    $this->miss = app(MissObjective::class);
});

it('creates an objective in the active status', function () {
    $objective = $this->create->execute(
        title: 'Reduce vendor onboarding time by 50%',
        description: 'Target Q2 2026',
        dueDate: CarbonImmutable::parse('2026-06-30'),
    );

    expect($objective->status)->toBe(ObjectiveStatus::Active)
        ->and($objective->title)->toBe('Reduce vendor onboarding time by 50%')
        ->and($objective->tenant_id)->toBe($this->tenant->id)
        ->and($objective->completed_at)->toBeNull();
});

it('completes an objective and records the timestamp', function () {
    $objective = $this->create->execute(title: 'Ship Accountability module');

    $this->complete->execute($objective);
    $objective->refresh();

    expect($objective->status)->toBe(ObjectiveStatus::Completed)
        ->and($objective->completed_at)->not->toBeNull()
        ->and($objective->missed_at)->toBeNull();
});

it('dispatches ObjectiveCompleted when an objective is completed', function () {
    $objective = $this->create->execute(title: 'Ship Accountability module');

    Event::fake([ObjectiveCompleted::class]);

    $this->complete->execute($objective);

    Event::assertDispatched(ObjectiveCompleted::class, fn (ObjectiveCompleted $e) =>
        $e->tenantId === $this->tenant->id &&
        $e->objectiveId === $objective->id &&
        $e->title === $objective->title
    );
});

it('marks an objective as missed', function () {
    $objective = $this->create->execute(title: 'Reduce churn by 10%');

    $this->miss->execute($objective);
    $objective->refresh();

    expect($objective->status)->toBe(ObjectiveStatus::Missed)
        ->and($objective->missed_at)->not->toBeNull()
        ->and($objective->completed_at)->toBeNull();
});

it('scopes objectives to the current tenant', function () {
    $this->create->execute(title: 'Acme Objective');

    $other = Tenant::create(['name' => 'Other', 'slug' => 'other']);
    app(CurrentTenant::class)->set($other);

    expect(Objective::all())->toHaveCount(0);
});
