<?php

namespace App\Domain\Workflow\Jobs;

use App\Domain\Workflow\Models\WorkflowNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWorkflowNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $notificationId) {}

    public function handle(): void
    {
        $notification = WorkflowNotification::withoutGlobalScopes()->findOrFail($this->notificationId);

        // Delivery mechanism (email, Slack, etc.) will be wired in the Inertia/integration session.
        // For now, mark the notification as sent so the workflow run history is complete.
        $notification->sent_at = now();
        $notification->save();
    }
}
