<?php

namespace App\Domain\Workflow\Enums;

enum WorkflowTrigger: string
{
    case VendorActivated = 'vendor.activated';
    case VendorSuspended = 'vendor.suspended';
    case VendorTerminated = 'vendor.terminated';
    case ContractActivated = 'contract.activated';
    case SubscriptionCancelled = 'subscription.cancelled';
    case PaymentFailed = 'subscription.payment_failed';
    case SlaBreached = 'sla.breached';
    case ObjectiveCompleted = 'objective.completed';
}
