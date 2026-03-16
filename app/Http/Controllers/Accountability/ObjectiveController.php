<?php

namespace App\Http\Controllers\Accountability;

use App\Domain\Accountability\Actions\CompleteObjective;
use App\Domain\Accountability\Actions\CreateObjective;
use App\Domain\Accountability\Actions\MissObjective;
use App\Domain\Accountability\Models\Objective;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreObjectiveRequest;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class ObjectiveController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Objectives/Index', [
            'objectives' => Objective::latest()->get(['id', 'title', 'due_date', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Objectives/Create');
    }

    public function store(StoreObjectiveRequest $request, CreateObjective $action): \Illuminate\Http\RedirectResponse
    {
        $action->execute(
            title: $request->validated('title'),
            description: $request->validated('description'),
            dueDate: $request->filled('due_date')
                ? CarbonImmutable::parse($request->validated('due_date'))
                : null,
        );

        return redirect()->route('objectives.index');
    }

    public function complete(Objective $objective, CompleteObjective $action): \Illuminate\Http\RedirectResponse
    {
        $action->execute($objective);
        return back();
    }

    public function miss(Objective $objective, MissObjective $action): \Illuminate\Http\RedirectResponse
    {
        $action->execute($objective);
        return back();
    }
}
