<?php

namespace App\Domain\Billing\Aggregates;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Events\PaymentFailed;
use App\Domain\Billing\Events\SubscriptionCancelled;
use App\Domain\Billing\Events\SubscriptionRenewed;
use App\Domain\Billing\Events\SubscriptionStarted;
use App\Domain\Billing\Exceptions\InvalidSubscriptionTransition;
use Carbon\CarbonImmutable;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class SubscriptionAggregate extends AggregateRoot
{
    protected ?int $tenantId = null;

    protected ?int $planId = null;

    protected ?SubscriptionStatus $status = null;

    protected ?CarbonImmutable $startedAt = null;

    protected ?CarbonImmutable $renewedAt = null;

    protected ?CarbonImmutable $cancelledAt = null;

    protected ?CarbonImmutable $paymentFailedAt = null;

    protected ?CarbonImmutable $currentPeriodEndsAt = null;

    public function startSubscription(
        int $tenantId,
        int $planId,
        CarbonImmutable $startedAt,
        CarbonImmutable $currentPeriodEndsAt,
    ): self {
        if ($this->status !== null) {
            throw InvalidSubscriptionTransition::alreadyStarted();
        }

        $this->recordThat(new SubscriptionStarted(
            tenantId: $tenantId,
            planId: $planId,
            startedAt: $startedAt,
            currentPeriodEndsAt: $currentPeriodEndsAt,
        ));

        return $this;
    }

    public function renew(CarbonImmutable $renewedAt, CarbonImmutable $currentPeriodEndsAt): self
    {
        if ($this->status === null) {
            throw InvalidSubscriptionTransition::notStarted('renewed');
        }

        if ($this->status === SubscriptionStatus::Cancelled) {
            throw InvalidSubscriptionTransition::cancelled('renewed');
        }

        $this->recordThat(new SubscriptionRenewed(
            tenantId: $this->tenantId,
            renewedAt: $renewedAt,
            currentPeriodEndsAt: $currentPeriodEndsAt,
        ));

        return $this;
    }

    public function cancel(CarbonImmutable $cancelledAt): self
    {
        if ($this->status === null) {
            throw InvalidSubscriptionTransition::notStarted('cancelled');
        }

        if ($this->status === SubscriptionStatus::Cancelled) {
            throw InvalidSubscriptionTransition::alreadyCancelled();
        }

        $this->recordThat(new SubscriptionCancelled(
            tenantId: $this->tenantId,
            cancelledAt: $cancelledAt,
        ));

        return $this;
    }

    public function failPayment(CarbonImmutable $failedAt, ?string $reason = null): self
    {
        if ($this->status === null) {
            throw InvalidSubscriptionTransition::notStarted('marked as payment failed');
        }

        if ($this->status === SubscriptionStatus::Cancelled) {
            throw InvalidSubscriptionTransition::cancelled('marked as payment failed');
        }

        $this->recordThat(new PaymentFailed(
            tenantId: $this->tenantId,
            failedAt: $failedAt,
            reason: $reason,
        ));

        return $this;
    }

    public function status(): ?SubscriptionStatus
    {
        return $this->status;
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    public function planId(): ?int
    {
        return $this->planId;
    }

    public function startedAt(): ?CarbonImmutable
    {
        return $this->startedAt;
    }

    public function renewedAt(): ?CarbonImmutable
    {
        return $this->renewedAt;
    }

    public function cancelledAt(): ?CarbonImmutable
    {
        return $this->cancelledAt;
    }

    public function paymentFailedAt(): ?CarbonImmutable
    {
        return $this->paymentFailedAt;
    }

    public function currentPeriodEndsAt(): ?CarbonImmutable
    {
        return $this->currentPeriodEndsAt;
    }

    protected function applySubscriptionStarted(SubscriptionStarted $event): void
    {
        $this->tenantId = $event->tenantId;
        $this->planId = $event->planId;
        $this->status = SubscriptionStatus::Active;
        $this->startedAt = CarbonImmutable::instance($event->startedAt);
        $this->renewedAt = null;
        $this->cancelledAt = null;
        $this->paymentFailedAt = null;
        $this->currentPeriodEndsAt = CarbonImmutable::instance($event->currentPeriodEndsAt);
    }

    protected function applySubscriptionRenewed(SubscriptionRenewed $event): void
    {
        $this->status = SubscriptionStatus::Active;
        $this->renewedAt = CarbonImmutable::instance($event->renewedAt);
        $this->paymentFailedAt = null;
        $this->currentPeriodEndsAt = CarbonImmutable::instance($event->currentPeriodEndsAt);
    }

    protected function applySubscriptionCancelled(SubscriptionCancelled $event): void
    {
        $this->status = SubscriptionStatus::Cancelled;
        $this->cancelledAt = CarbonImmutable::instance($event->cancelledAt);
    }

    protected function applyPaymentFailed(PaymentFailed $event): void
    {
        $this->status = SubscriptionStatus::PastDue;
        $this->paymentFailedAt = CarbonImmutable::instance($event->failedAt);
    }
}