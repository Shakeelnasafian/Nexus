<?php

namespace App\Http\Controllers\Workflow;

use App\Domain\Workflow\Actions\CreateWorkflow;
use App\Domain\Workflow\Enums\WorkflowTrigger;
use App\Domain\Workflow\Models\Workflow;
use App\Domain\Workflow\Models\WorkflowRun;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkflowRequest;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Workflows/Index', [
            'workflows' => Workflow::withCount('steps')->get(['id', 'name', 'trigger_event', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Workflows/Create', [
            'triggers' => array_column(WorkflowTrigger::cases(), 'value'),
        ]);
    }

    public function store(StoreWorkflowRequest $request, CreateWorkflow $action): \Illuminate\Http\RedirectResponse
    {
        $workflow = $action->execute(
            name: $request->validated('name'),
            trigger: WorkflowTrigger::from($request->validated('trigger_event')),
            isActive: (bool) $request->validated('is_active', true),
        );

        return redirect()->route('workflows.show', $workflow);
    }

    public function show(Workflow $workflow): Response
    {
        return Inertia::render('Workflows/Show', [
            'workflow' => array_merge(
                $workflow->only('id', 'name', 'trigger_event', 'is_active'),
                ['steps' => $workflow->steps()->get(['id', 'sort_order', 'action_type', 'payload'])]
            ),
            'runs' => WorkflowRun::withoutGlobalScopes()
                ->where('workflow_id', $workflow->id)
                ->latest()
                ->limit(20)
                ->get(['id', 'status', 'started_at', 'completed_at', 'failed_at']),
        ]);
    }
}
