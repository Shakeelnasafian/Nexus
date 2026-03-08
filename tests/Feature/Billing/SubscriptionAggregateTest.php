<?php

use App\Domain\Billing\Aggregates\SubscriptionAggregate;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Events\PaymentFailed;
use App\Domain\Billing\Events\SubscriptionCancelled;
use App\Domain\Billing\Events\SubscriptionRenewed;
use App\Domain\Billing\Events\SubscriptionStarted;
use App\Domain\Billing\Exceptions\InvalidSubscriptionTransition;
use App\Domain\Billing\Jobs\SendDunningNotification;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->tenant = Tenant::create([
        'name' => 'Acme',
        'slug' => 'acme',
    ]);

    app(CurrentTenant::class)->set($this->tenant);

    $this->plan = Plan::create([
        'name' => 'Growth',
        'code' => 'growth',
        'price_amount' => 15000,
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'is_active' => true,
    ]);

    $this->subscriptionUuid = (string) Str::uuid();
    $this->startedAt = CarbonImmutable::parse('2026-03-07 00:00:00');
    $this->firstPeriodEndsAt = CarbonImmutable::parse('2026-04-07 00:00:00');
    $this->renewedAt = CarbonImmutable::parse('2026-04-07 00:00:00');
    $this->secondPeriodEndsAt = CarbonImmutable::parse('2026-05-07 00:00:00');
    $this->failedAt = CarbonImmutable::parse('2026-04-10 00:00:00');
    $this->cancelledAt = CarbonImmutable::parse('2026-04-15 00:00:00');
});

it('records subscription started for a new aggregate', function () {
    SubscriptionAggregate::fake($this->subscriptionUuid)
        ->when(fn (SubscriptionAggregate $aggregate) => $aggregate->startSubscription(
            tenantId: $this->tenant->id,
            planId: $this->plan->id,
            startedAt: $this->startedAt,
            currentPeriodEndsAt: $this->firstPeriodEndsAt,
        ))
        ->assertRecorded(new SubscriptionStarted(
            tenantId: $this->tenant->id,
            planId: $this->plan->id,
            startedAt: $this->startedAt,
            currentPeriodEndsAt: $this->firstPeriodEndsAt,
        ));
});

it('records subscription renewed for an active subscription', function () {
    SubscriptionAggregate::fake($this->subscriptionUuid)
        ->given(new SubscriptionStarted(
            tenantId: $this->tenant->id,
            planId: $this->plan->id,
            startedAt: $this->startedAt,
            currentPeriodEndsAt: $this->firstPeriodEndsAt,
        ))
        ->when(fn (SubscriptionAggregate $aggregate) => $aggregate->renew(
            renewedAt: $this->renewedAt,
            currentPeriodEndsAt: $this->secondPeriodEndsAt,
        ))
        ->assertRecorded(new SubscriptionRenewed(
            tenantId: $this->tenant->id,
            renewedAt: $this->renewedAt,
            currentPeriodEndsAt: $this->secondPeriodEndsAt,
        ));
});

it('records payment failed for an active subscription', function () {
    SubscriptionAggregate::fake($this->subscriptionUuid)
        ->given(new SubscriptionStarted(
            tenantId: $this->tenant->id,
            planId: $this->plan->id,
            startedAt: $this->startedAt,
            currentPeriodEndsAt: $this->firstPeriodEndsAt,
        ))
        ->when(fn (SubscriptionAggregate $aggregate) => $aggregate->failPayment(
            failedAt: $this->failedAt,
            reason: 'card_declined',
        ))
        ->assertRecorded(new PaymentFailed(
            tenantId: $this->tenant->id,
            failedAt: $this->failedAt,
            reason: 'card_declined',
        ));
});

it('records subscription cancelled for an active subscription', function () {
    SubscriptionAggregate::fake($this->subscriptionUuid)
        ->given(new SubscriptionStarted(
            tenantId: $this->tenant->id,
            planId: $this->plan->id,
            startedAt: $this->startedAt,
            currentPeriodEndsAt: $this->firstPeriodEndsAt,
        ))
        ->when(fn (SubscriptionAggregate $aggregate) => $aggregate->cancel($this->cancelledAt))
        ->assertRecorded(new SubscriptionCancelled(
            tenantId: $this->tenant->id,
            cancelledAt: $this->cancelledAt,
        ));
});

it('projects persisted subscription lifecycle changes into the read model', function () {
    SubscriptionAggregate::retrieve($this->subscriptionUuid)
        ->startSubscription(
            tenantId: $this->tenant->id,
            planId: $this->plan->id,
            startedAt: $this->startedAt,
            currentPeriodEndsAt: $this->firstPeriodEndsAt,
        )
        ->persist();

    SubscriptionAggregate::retrieve($this->subscriptionUuid)
        ->renew(
            renewedAt: $this->renewedAt,
            currentPeriodEndsAt: $this->secondPeriodEndsAt,
        )
        ->persist();

    $subscription = Subscription::query()->where('uuid', $this->subscriptionUuid)->firstOrFail();

    expect($subscription->tenant_id)->toBe($this->tenant->id)
        ->and($subscription->plan_id)->toBe($this->plan->id)
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->started_at?->equalTo($this->startedAt))->toBeTrue()
        ->and($subscription->renewed_at?->equalTo($this->renewedAt))->toBeTrue()
        ->and($subscription->current_period_ends_at?->equalTo($this->secondPeriodEndsAt))->toBeTrue();
});

it('dispatches dunning work and marks the subscription past due after payment failure', function () {
    Bus::fake();

    SubscriptionAggregate::retrieve($this->subscriptionUuid)
        ->startSubscription(
            tenantId: $this->tenant->id,
            planId: $this->plan->id,
            startedAt: $this->startedAt,
            currentPeriodEndsAt: $this->firstPeriodEndsAt,
        )
        ->persist();

    SubscriptionAggregate::retrieve($this->subscriptionUuid)
        ->failPayment(
            failedAt: $this->failedAt,
            reason: 'card_declined',
        )
        ->persist();

    $subscription = Subscription::query()->where('uuid', $this->subscriptionUuid)->firstOrFail();

    expect($subscription->status)->toBe(SubscriptionStatus::PastDue)
        ->and($subscription->payment_failed_at?->equalTo($this->failedAt))->toBeTrue();

    Bus::assertDispatched(SendDunningNotification::class, function (SendDunningNotification $job) {
        return $job->subscriptionUuid === $this->subscriptionUuid
            && $job->tenantId === $this->tenant->id
            && $job->reason === 'card_declined';
    });
});

it('marks the read model cancelled when a subscription is cancelled', function () {
    SubscriptionAggregate::retrieve($this->subscriptionUuid)
        ->startSubscription(
            tenantId: $this->tenant->id,
            planId: $this->plan->id,
            startedAt: $this->startedAt,
            currentPeriodEndsAt: $this->firstPeriodEndsAt,
        )
        ->persist();

    SubscriptionAggregate::retrieve($this->subscriptionUuid)
        ->cancel($this->cancelledAt)
        ->persist();

    $subscription = Subscription::query()->where('uuid', $this->subscriptionUuid)->firstOrFail();

    expect($subscription->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($subscription->cancelled_at?->equalTo($this->cancelledAt))->toBeTrue();
});

it('prevents renewal after cancellation', function () {
    SubscriptionAggregate::retrieve($this->subscriptionUuid)
        ->startSubscription(
            tenantId: $this->tenant->id,
            planId: $this->plan->id,
            startedAt: $this->startedAt,
            currentPeriodEndsAt: $this->firstPeriodEndsAt,
        )
        ->cancel($this->cancelledAt)
        ->persist();

    expect(fn () => SubscriptionAggregate::retrieve($this->subscriptionUuid)
        ->renew(
            renewedAt: $this->renewedAt,
            currentPeriodEndsAt: $this->secondPeriodEndsAt,
        ))
        ->toThrow(InvalidSubscriptionTransition::class, 'Cancelled subscriptions cannot be renewed.');
});

it('scopes billing models to the current tenant', function () {
    $otherTenant = Tenant::create([
        'name' => 'Other Co',
        'slug' => 'other-co',
    ]);

    app(CurrentTenant::class)->set($otherTenant);

    Plan::create([
        'name' => 'Other',
        'code' => 'other',
        'price_amount' => 20000,
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'is_active' => true,
    ]);

    Subscription::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'tenant_id' => $otherTenant->id,
        'plan_id' => Plan::withoutGlobalScopes()->where('tenant_id', $otherTenant->id)->firstOrFail()->id,
        'status' => SubscriptionStatus::Active,
        'started_at' => $this->startedAt,
        'current_period_ends_at' => $this->firstPeriodEndsAt,
    ]);

    app(CurrentTenant::class)->set($this->tenant);

    expect(Plan::query()->pluck('code')->all())->toBe(['growth'])
        ->and(Subscription::query()->count())->toBe(0);
});