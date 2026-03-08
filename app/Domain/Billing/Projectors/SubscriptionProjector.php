<?php

namespace App\Domain\Billing\Projectors;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Events\PaymentFailed;
use App\Domain\Billing\Events\SubscriptionCancelled;
use App\Domain\Billing\Events\SubscriptionRenewed;
use App\Domain\Billing\Events\SubscriptionStarted;
use App\Domain\Billing\Models\Subscription;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class SubscriptionProjector extends Projector
{
    public function onSubscriptionStarted(SubscriptionStarted $event): void
    {
        Subscription::withoutGlobalScopes()->create([
            'uuid' => $this->aggregateUuid($event),
            'tenant_id' => $event->tenantId,
            'plan_id' => $event->planId,
            'status' => SubscriptionStatus::Active,
            'started_at' => $event->startedAt,
            'current_period_ends_at' => $event->currentPeriodEndsAt,
        ]);
    }

    public function onSubscriptionRenewed(SubscriptionRenewed $event): void
    {
        $subscription = $this->subscription($event);
        $subscription->fill([
            'status' => SubscriptionStatus::Active,
            'renewed_at' => $event->renewedAt,
            'payment_failed_at' => null,
            'current_period_ends_at' => $event->currentPeriodEndsAt,
        ])->save();
    }

    public function onSubscriptionCancelled(SubscriptionCancelled $event): void
    {
        $subscription = $this->subscription($event);
        $subscription->fill([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => $event->cancelledAt,
        ])->save();
    }

    public function onPaymentFailed(PaymentFailed $event): void
    {
        $subscription = $this->subscription($event);
        $subscription->fill([
            'status' => SubscriptionStatus::PastDue,
            'payment_failed_at' => $event->failedAt,
        ])->save();
    }

    private function subscription(object $event): Subscription
    {
        return Subscription::withoutGlobalScopes()
            ->where('uuid', $this->aggregateUuid($event))
            ->firstOr(function (): never {
                throw new ModelNotFoundException('Subscription projection not found.');
            });
    }

    private function aggregateUuid(object $event): string
    {
        $uuid = $event->aggregateRootUuid();

        if ($uuid === null) {
            throw new ModelNotFoundException('Stored Billing events must include an aggregate UUID.');
        }

        return $uuid;
    }
}