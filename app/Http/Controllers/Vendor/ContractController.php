<?php

namespace App\Http\Controllers\Vendor;

use App\Domain\Vendor\Actions\ActivateContract;
use App\Domain\Vendor\Actions\CreateContract;
use App\Domain\Vendor\Actions\TerminateContract;
use App\Domain\Vendor\Models\Contract;
use App\Domain\Vendor\Models\Vendor;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractRequest;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    public function create(Vendor $vendor): Response
    {
        return Inertia::render('Contracts/Create', [
            'vendor' => $vendor->only('id', 'name'),
        ]);
    }

    public function store(StoreContractRequest $request, Vendor $vendor, CreateContract $action): \Illuminate\Http\RedirectResponse
    {
        $contract = $action->execute(
            vendor: $vendor,
            title: $request->validated('title'),
            startsAt: Carbon::parse($request->validated('starts_at')),
            endsAt: $request->filled('ends_at') ? Carbon::parse($request->validated('ends_at')) : null,
        );

        return redirect()->route('contracts.show', $contract);
    }

    public function show(Contract $contract): Response
    {
        return Inertia::render('Contracts/Show', [
            'contract' => $contract->only('id', 'vendor_id', 'title', 'state', 'starts_at', 'ends_at', 'created_at'),
        ]);
    }

    public function activate(Contract $contract, ActivateContract $action): \Illuminate\Http\RedirectResponse
    {
        $action->execute($contract);
        return back();
    }

    public function terminate(Contract $contract, TerminateContract $action): \Illuminate\Http\RedirectResponse
    {
        $action->execute($contract);
        return back();
    }
}
