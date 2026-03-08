<?php

namespace App\Domain\Workflow\Providers;

use App\Domain\Billing\Events\PaymentFailed;
use App\Domain\Billing\Events\SubscriptionCancelled;
use App\Domain\Vendor\Events\ContractActivated;
use App\Domain\Vendor\Events\VendorActivated;
use App\Domain\Vendor\Events\VendorSuspended;
use App\Domain\Vendor\Events\VendorTerminated;
use App\Domain\Workflow\Actions\TriggerWorkflow;
use App\Domain\Workflow\Enums\WorkflowTrigger;
use App\Domain\Workflow\StepHandlerRegistry;
use App\Domain\Workflow\StepHandlers\LogStepHandler;
use App\Domain\Workflow\StepHandlers\NotifyStepHandler;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StepHandlerRegistry::class, function (): StepHandlerRegistry {
            $registry = new StepHandlerRegistry();
            $registry->register('log', LogStepHandler::class);
            $registry->register('notify', NotifyStepHandler::class);
            return $registry;
        });
    }

    public function boot(): void
    {
        Event::listen(VendorActivated::class, function (VendorActivated $event): void {
            app(TriggerWorkflow::class)->execute(
                tenantId: $event->tenantId,
                triggerEvent: WorkflowTrigger::VendorActivated->value,
                triggerPayload: [
                    'vendor_id' => $event->vendorId,
                    'vendor_name' => $event->vendorName,
                ],
            );
        });

        Event::listen(VendorSuspended::class, function (VendorSuspended $event): void {
            app(TriggerWorkflow::class)->execute(
                tenantId: $event->tenantId,
                triggerEvent: WorkflowTrigger::VendorSuspended->value,
                triggerPayload: [
                    'vendor_id' => $event->vendorId,
                    'vendor_name' => $event->vendorName,
                ],
            );
        });

        Event::listen(VendorTerminated::class, function (VendorTerminated $event): void {
            app(TriggerWorkflow::class)->execute(
                tenantId: $event->tenantId,
                triggerEvent: WorkflowTrigger::VendorTerminated->value,
                triggerPayload: [
                    'vendor_id' => $event->vendorId,
                    'vendor_name' => $event->vendorName,
                ],
            );
        });

        Event::listen(ContractActivated::class, function (ContractActivated $event): void {
            app(TriggerWorkflow::class)->execute(
                tenantId: $event->tenantId,
                triggerEvent: WorkflowTrigger::ContractActivated->value,
                triggerPayload: [
                    'vendor_id' => $event->vendorId,
                    'contract_id' => $event->contractId,
                    'contract_title' => $event->contractTitle,
                ],
            );
        });

        Event::listen(SubscriptionCancelled::class, function (SubscriptionCancelled $event): void {
            app(TriggerWorkflow::class)->execute(
                tenantId: $event->tenantId,
                triggerEvent: WorkflowTrigger::SubscriptionCancelled->value,
                triggerPayload: [
                    'cancelled_at' => $event->cancelledAt->toIso8601String(),
                ],
            );
        });

        Event::listen(PaymentFailed::class, function (PaymentFailed $event): void {
            app(TriggerWorkflow::class)->execute(
                tenantId: $event->tenantId,
                triggerEvent: WorkflowTrigger::PaymentFailed->value,
                triggerPayload: [
                    'failed_at' => $event->failedAt->toIso8601String(),
                    'reason' => $event->reason,
                ],
            );
        });
    }
}
